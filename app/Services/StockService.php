<?php

namespace App\Services;

use App\Models\ConfirmStock;
use App\Models\CurrentOrder;
use App\Models\CurrentReserveProduct;
use App\Models\Installment;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\StockHistory;
use App\Models\StockOrder;
use App\Models\Store;
use App\Models\Transection;
use App\Repositories\StockRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Stock rules for the seller-facing endpoints.
 *
 * The settlement in confirm() moves a van seller's day into history: what they
 * sold becomes a confirm_stock + stock_history record, unsold units go back to
 * warehouse quantity, and the working tables are cleared for the next run.
 */
class StockService
{
    public function __construct(private StockRepository $stocks)
    {
    }

    /**
     * The seller's stock list (type 4) or their sellable catalogue.
     * Seller-specific prices replace the catalogue price where one exists.
     */
    public function listing(int $sellerId, array $filters): array
    {
        $limit    = (int) ($filters['limit'] ?? 10);
        $page     = (int) ($filters['offset'] ?? 1);
        $category = (int) ($filters['category_id'] ?? 0);
        $type     = $filters['type'] ?? null;
        $search   = $filters['search'] ?? null;

        $customerId = (int) ($filters['customer_id'] ?? 0);

        if ((int) $type === 4) {
            $stocks = $this->stocks->forSeller($sellerId, $category, $limit, $page);
            $this->applySellerPrices($sellerId, $stocks, fn ($row) => $row->product);
            $this->applyCustomerPrices($customerId, $stocks, fn ($row) => $row->product);

            return ['type' => 'stocks', 'paginator' => $stocks];
        }

        $products = $this->stocks->catalogueForSeller($sellerId, $category, $search, $limit, $page);
        $this->applySellerPrices($sellerId, $products, fn ($row) => $row);
        $this->applyCustomerPrices($customerId, $products, fn ($row) => $row);

        return ['type' => 'products', 'paginator' => $products];
    }

    /**
     * Close out the seller's stock and return the settlement summary.
     *
     * Wrapped in a transaction: the original wrote history, mutated product
     * quantities and then deleted the working rows without one, so a failure
     * part-way through could leave stock counted twice or lost entirely.
     */
    public function confirm(int $sellerId, ?string $vehicleCode): array
    {
        return DB::transaction(function () use ($sellerId, $vehicleCode) {
            $moved     = $this->stocks->movedForSeller($sellerId);
            $untouched = $this->stocks->untouchedForSeller($sellerId);

            $settlementId = $this->stocks->nextSettlementId();
            $settlement = new StockOrder();
            $settlement->id          = $settlementId;
            $settlement->seller_id   = $sellerId;
            $settlement->update_flag = 0; // NOT NULL with no default
            $settlement->save();

            $totalSold      = 0;
            $totalRemaining = 0;
            $remainProducts = [];

            if ($moved->isNotEmpty() || $untouched->isNotEmpty()) {
                ConfirmStock::where('seller_id', $sellerId)->delete();

                foreach ($moved->concat($untouched) as $item) {
                    $sold = $item->main_stock - $item->stock;

                    ConfirmStock::create([
                        'seller_id'  => $sellerId,
                        'product_id' => $item->product_id,
                        'main_stock' => $item->main_stock,
                        'stock'      => $sold,
                    ]);

                    StockHistory::create([
                        'seller_id'  => $sellerId,
                        'order_id'   => $settlementId,
                        'product_id' => $item->product_id,
                        'main_stock' => $item->main_stock,
                        'stock'      => $sold,
                    ]);

                    $totalSold      += $sold;
                    $totalRemaining += $item->stock;

                    // Unsold units return to warehouse quantity.
                    if ($product = Product::find($item->product_id)) {
                        $product->quantity += $item->stock;
                        $product->save();
                    }

                    if ($item->stock != 0 && $item->product) {
                        $remainProducts[] = [
                            'name'         => $item->product->name,
                            'name_en'      => $item->product->name_en,
                            'quantity'     => $item->stock,
                            'product_code' => $item->product->product_code,
                            'price'        => $item->product->selling_price * $item->stock,
                        ];
                    }
                }
            }

            $orders = CurrentOrder::where('owner_id', $sellerId);

            $summary = [
                'vehicle_code'     => $this->vehicleCode($vehicleCode),
                'vehicle_name'     => optional(Store::where('store_id', $vehicleCode)->first())->store_name1,
                'product_count'    => $moved->count(),
                'total_stock'      => $totalSold,
                'order_count'      => $orders->count(),
                'remain_stock'     => $totalRemaining,
                'total_cash'       => $this->transactionTotal($sellerId, 4, 1),
                'total_credit'     => $this->transactionTotal($sellerId, 4, 2),
                'installment_total'=> Installment::where('seller_id', $sellerId)->sum('total_price'),
                'refund_total'     => Transection::where('tran_type', 7)->where('seller_id', $sellerId)->sum('amount'),
                'products'         => $this->soldBreakdown($sellerId, $orders->pluck('id')->all()),
                'remain_products'  => $remainProducts,
            ];

            $settlement->statistcs = json_encode($summary);
            $settlement->save();

            $this->stocks->clearForSeller($sellerId);
            CurrentOrder::where('owner_id', $sellerId)->delete();
            CurrentReserveProduct::where('seller_id', $sellerId)->delete();
            Transection::where('seller_id', $sellerId)->update(['active' => 0]);

            return $summary;
        });
    }

    /** Past settlements with their stored summary decoded. */
    public function history(int $sellerId): array
    {
        return $this->stocks->settlements($sellerId)
            ->map(function ($settlement) {
                $settlement->statistcs = json_decode($settlement->statistcs);
                if ($settlement->seller) {
                    $settlement->seller->vehicle_code = $this->vehicleCode($settlement->seller->vehicle_code);
                }
                return $settlement;
            })
            ->all();
    }

    private function soldBreakdown(int $sellerId, array $orderIds): array
    {
        $products = [];

        $sold = ConfirmStock::where('seller_id', $sellerId)->where('stock', '!=', 0)->get();

        // One query for every line rather than one per product per order.
        $prices = $orderIds
            ? OrderDetail::whereIn('order_id', $orderIds)->pluck('price', 'product_id')->all()
            : [];

        foreach ($sold as $item) {
            if (!$item->product) continue;

            $row = [
                'name'         => $item->product->name,
                'name_en'      => $item->product->name_en,
                'product_code' => $item->product->product_code,
                'quantity'     => $item->stock,
            ];
            if (isset($prices[$item->product_id])) {
                $row['price'] = $prices[$item->product_id];
            }
            $products[] = $row;
        }

        return $products;
    }

    private function transactionTotal(int $sellerId, int $type, int $cash): float
    {
        return (float) Transection::where('tran_type', $type)
            ->where('cash', $cash)
            ->where('seller_id', $sellerId)
            ->sum('amount');
    }

    /** Vehicle codes are stored as a store id; show the store's code. */
    private function vehicleCode($code): ?string
    {
        if (!$code) return null;

        return optional(Store::where('store_id', $code)->first())->store_code ?? (string) $code;
    }

    /** Overwrite catalogue prices with the seller's negotiated ones. */
    /**
     * سعر العميل المحدد على كل منتج، حين يُمرَّر customer_id.
     *
     * يوفّر على التطبيق نداءً إضافيًا لـ products/customer-prices بعد اختيار
     * كل عميل. يُعرض في حقل مستقل customer_price ولا يُستبدل به
     * selling_price حتى يبقى السعر الأساسي ظاهرًا للمقارنة.
     */
    private function applyCustomerPrices(int $customerId, LengthAwarePaginator $rows, callable $productOf): void
    {
        $products = collect($rows->items())->map($productOf)->filter();

        if ($customerId <= 0 || $products->isEmpty()) {
            return;
        }

        $prices = \App\Models\CustomerPrice::where('customer_id', $customerId)
            ->whereIn('product_id', $products->pluck('id')->all())
            ->pluck('price', 'product_id');

        foreach ($products as $product) {
            $price = $prices[$product->id] ?? null;

            $product->customer_price = ($price !== null && (float) $price > 0)
                ? (float) $price
                : null;
        }
    }

    private function applySellerPrices(int $sellerId, LengthAwarePaginator $rows, callable $productOf): void
    {
        $products = collect($rows->items())->map($productOf)->filter();
        if ($products->isEmpty()) return;

        $overrides = $this->stocks->priceOverrides($sellerId, $products->pluck('id')->all());

        foreach ($products as $product) {
            // سعر بديل بقيمة صفر أو فارغة لا يُعتد به: كان يدوس على السعر
            // الصحيح فيظهر المنتج بسعر صفر في التطبيق.
            $override = $overrides[$product->id] ?? null;

            if ($override !== null && (float) $override > 0) {
                $product->selling_price = $override;
            }
        }
    }
}
