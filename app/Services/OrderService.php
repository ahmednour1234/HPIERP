<?php

namespace App\Services;

use App\CPU\Helpers;
use App\Models\Account;
use App\Models\Customer;
use App\Models\CustomerPrice;
use App\Models\CurrentOrder;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\SellerPrice;
use App\Models\Stock;
use App\Models\Transection;
use App\Repositories\CustomerRepository;
use App\Repositories\OrderRepository;
use App\Services\Exceptions\InsufficientStockException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Point-of-sale orders for a van seller.
 *
 * Order types, as used throughout the schema:
 *   4  sale        7  return        12, 24  installment variants
 */
class OrderService
{
    public const TYPE_SALE   = 4;
    public const TYPE_RETURN = 7;
    public const STOCK_CONSUMING = [4, 12, 24];

    public function __construct(
        private OrderRepository $orders,
        private CustomerRepository $customers
    ) {
    }

    public function list(int $sellerId, array $filters): LengthAwarePaginator
    {
        return $this->orders->forSeller(
            $sellerId, $filters,
            (int) ($filters['limit'] ?? 25),
            (int) ($filters['offset'] ?? 1)
        );
    }

    /** Totals for the same filters the listing used. */
    public function totals(int $sellerId, array $filters): array
    {
        return $this->orders->totalsForSeller($sellerId, $filters);
    }

    public function invoice(int $orderId, int $sellerId): Order
    {
        $order = $this->orders->withDetails($orderId);

        if (!$order) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())
                ->setModel(Order::class, $orderId);
        }

        // A seller may only read their own invoices; v1 returned any order by id.
        if ((int) $order->owner_id !== $sellerId) {
            throw new AuthorizationException('This order does not belong to you');
        }

        return $order;
    }

    public function forCustomer(int $customerId, int $sellerId, array $filters): LengthAwarePaginator
    {
        if (!$this->customers->belongsToSeller($customerId, $sellerId)) {
            throw new AuthorizationException('This customer is not assigned to you');
        }

        return $this->orders->forCustomer(
            $customerId,
            (int) ($filters['limit'] ?? 25),
            (int) ($filters['offset'] ?? 1)
        );
    }

    /**
     * What is still returnable on an invoice.
     *
     * A return is always against an original sale: the client asks for the
     * invoice, gets each line with how much has already come back, and can
     * only send quantities within what is left.
     */
    public function returnableLines(int $orderId, int $sellerId): array
    {
        $order = $this->invoice($orderId, $sellerId);

        $this->assertReturnable($order);

        $lines = $order->details->map(function ($line) {
            $snapshot = json_decode($line->product_details, true);
            $sold     = (float) $line->quantity;
            $returned = (float) ($line->quantity_returned ?? 0);

            return [
                'order_detail_id'     => $line->id,
                'product_id'          => (int) $line->product_id,
                'product_name'        => $snapshot['name'] ?? null,
                'product_code'        => $snapshot['product_code'] ?? null,
                'unit_value'          => (float) ($snapshot['unit_value'] ?? 1),
                'price'               => (float) $line->price,
                'tax_amount'          => (float) $line->tax_amount,
                'discount_on_product' => (float) $line->discount_on_product,
                'quantity_sold'       => $sold,
                'quantity_returned'   => $returned,
                'quantity_returnable' => max($sold - $returned, 0),
            ];
        })->values()->all();

        return [
            'order' => [
                'id'             => $order->id,
                'type'           => (int) $order->type,
                'order_amount'   => (float) $order->order_amount,
                'collected_cash' => (float) $order->collected_cash,
                'created_at'     => optional($order->created_at)->toIso8601String(),
                'customer'       => $order->customer ? [
                    'id'     => $order->customer->id,
                    'name'   => $order->customer->name,
                    'mobile' => $order->customer->mobile,
                ] : null,
            ],
            'lines'          => $lines,
            'fully_returned' => collect($lines)->every(fn ($l) => $l['quantity_returnable'] <= 0),
        ];
    }

    /**
     * Return part or all of an invoice.
     *
     * The refund is written as its own order of type 7 carrying `parent_id`,
     * so the original invoice stays intact and the two can be reconciled.
     * Each line's `quantity_returned` is bumped on the original, which is what
     * stops the same units being returned twice.
     */
    public function returnAgainstInvoice(int $sellerId, array $data): Order
    {
        $original = $this->invoice((int) $data['order_id'], $sellerId);

        $this->assertReturnable($original);

        return DB::transaction(function () use ($sellerId, $original, $data) {
            // Lock the lines so two concurrent returns cannot both pass the
            // "how much is left" check.
            $details = OrderDetail::where('order_id', $original->id)
                ->lockForUpdate()->get()->keyBy('product_id');

            $stocks = Stock::where('seller_id', $sellerId)
                ->whereIn('product_id', array_column($data['items'], 'product_id'))
                ->lockForUpdate()->get()->keyBy('product_id');

            $lines    = [];
            $refund   = 0.0;
            $taxTotal = 0.0;

            foreach ($data['items'] as $item) {
                $productId = (int) $item['product_id'];
                $quantity  = (float) $item['quantity'];

                $line = $details->get($productId);
                if (!$line) {
                    throw new \InvalidArgumentException(
                        'Product ' . $productId . ' is not on invoice ' . $original->id . '.'
                    );
                }

                $alreadyReturned = (float) ($line->quantity_returned ?? 0);
                $returnable      = (float) $line->quantity - $alreadyReturned;

                if ($quantity > $returnable) {
                    $snapshot = json_decode($line->product_details, true);
                    throw new \InvalidArgumentException(sprintf(
                        'Only %s of %s can still be returned (%s already returned).',
                        $this->trimNumber($returnable),
                        $snapshot['name'] ?? ('product ' . $productId),
                        $this->trimNumber($alreadyReturned)
                    ));
                }

                // Refund at the price actually charged on the invoice, not the
                // catalogue price, which may have moved since.
                $price        = (float) $line->price;
                $lineTax      = (float) $line->tax_amount;
                $lineDiscount = (float) $line->discount_on_product;

                $refund   += ($price - $lineDiscount) * $quantity;
                $taxTotal += $lineTax * $quantity;

                $line->quantity_returned = $alreadyReturned + $quantity;
                $line->save();

                // The units come back onto the van.
                $stock = $stocks->get($productId);
                if ($stock) {
                    $stock->stock += $quantity;
                    $stock->save();
                }

                $lines[] = [
                    'product_id'          => $productId,
                    'product_details'     => $line->product_details,
                    'quantity'            => $quantity,
                    'price'               => $price,
                    'tax_amount'          => $lineTax,
                    'discount_on_product' => $lineDiscount,
                    'discount_type'       => $line->discount_type,
                    'update_flag'         => 0,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ];
            }

            $grandTotal = round($refund + $taxTotal, 2);

            $return = Order::create([
                'owner_id'       => $sellerId,
                'user_id'        => $original->user_id,
                'parent_id'      => $original->id,
                'type'           => self::TYPE_RETURN,
                'cash'           => $data['cash'] ?? $original->cash,
                'payment_id'     => $data['type'] ?? $original->payment_id,
                'total_tax'      => $taxTotal,
                'order_amount'   => $grandTotal,
                'collected_cash' => $grandTotal,
                'update_flag'    => 0,
            ]);

            foreach ($lines as &$line) {
                $line['order_id'] = $return->id;
            }
            unset($line);
            OrderDetail::insert($lines);

            Transection::create([
                'tran_type'   => (string) self::TYPE_RETURN,
                'seller_id'   => $sellerId,
                'account_id'  => $data['type'] ?? $original->payment_id,
                'customer_id' => $original->user_id,
                'order_id'    => $return->id,
                'amount'      => $grandTotal,
                'cash'        => $data['cash'] ?? $original->cash,
                'description' => $data['note'] ?? 'مرتجع مبيعات',
                'debit'       => 0,
                'credit'      => 1,
                'balance'     => $grandTotal,
                'date'        => now()->toDateString(),
            ]);

            // The refund comes off what the customer owes.
            $customer = Customer::find($original->user_id);
            if ($customer) {
                $customer->balance = (float) $customer->balance + $grandTotal;
                $customer->save();
            }

            return $return->load(['details', 'customer']);
        });
    }

    /** Returns are only meaningful against a sale. */
    private function assertReturnable(Order $order): void
    {
        if (!in_array((int) $order->type, [self::TYPE_SALE, 12, 24], true)) {
            throw new \InvalidArgumentException('Only a sale invoice can be returned against.');
        }
    }

    /** 3.00 -> "3", 2.50 -> "2.5" — for readable error messages. */
    private function trimNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.') ?: '0';
    }

    /**
     * Record a payment against an invoice.
     *
     * Distinct from `customers/add-balance`, which settles a customer's overall
     * balance without reference to a document: this one is tied to a specific
     * invoice, so `collected_cash` on that invoice moves and its derived
     * payment_status follows.
     *
     * The invoice, the account and the customer are all locked for the
     * duration, so two collections filed at once cannot both read the same
     * remaining figure and together overpay the invoice.
     */
    public function collectPayment(int $orderId, int $sellerId, array $data, ?UploadedFile $image = null): array
    {
        // Ownership is checked before anything is written or uploaded.
        $this->invoice($orderId, $sellerId);

        // A file write cannot be rolled back, so it happens outside.
        $imagePath = $image ? Helpers::upload('shop/', 'png', $image) : null;

        return DB::transaction(function () use ($orderId, $sellerId, $data, $imagePath) {
            $order = Order::lockForUpdate()->findOrFail($orderId);

            if ((int) $order->type === self::TYPE_RETURN) {
                throw new \InvalidArgumentException('لا يمكن تحصيل فاتورة مرتجع');
            }

            $amount    = round((float) $data['amount'], 2);
            $total     = round((float) $order->order_amount, 2);
            $collected = round((float) $order->collected_cash, 2);
            $remaining = round($total - $collected, 2);

            if ($amount > $remaining) {
                throw new \InvalidArgumentException('المبلغ يتجاوز المتبقّي على الفاتورة');
            }

            $account = Account::lockForUpdate()->findOrFail($data['account_id']);

            Transection::create([
                'tran_type'   => 'Receivable',
                'account_id'  => $account->id,
                'seller_id'   => $sellerId,
                'customer_id' => $order->user_id,
                'order_id'    => $order->id,
                'amount'      => $amount,
                'description' => $data['note'] ?? 'تحصيل فاتورة',
                'debit'       => 1,
                'credit'      => 0,
                'balance'     => $account->balance + $amount,
                'date'        => $data['date'],
                'cash'        => $data['cash'] ?? $order->cash,
                'img'         => $imagePath,
            ]);

            $account->balance  = $account->balance + $amount;
            $account->total_in = $account->total_in + $amount;
            $account->save();

            // What the customer owes comes down by what was collected.
            $customer = Customer::lockForUpdate()->find($order->user_id);
            if ($customer) {
                $customer->balance = (float) $customer->balance - $amount;
                $customer->save();
            }

            $order->collected_cash = $collected + $amount;
            $order->save();

            $nowCollected = round((float) $order->collected_cash, 2);
            $nowRemaining = round(max($total - $nowCollected, 0), 2);

            return [
                'order_id'       => $order->id,
                'order_amount'   => $total,
                'collected_cash' => $nowCollected,
                'remaining'      => $nowRemaining,
                // Mirrors the payment_status filter on GET /orders.
                'payment_status' => $nowCollected >= $total
                    ? 'paid'
                    : ($nowCollected > 0 ? 'partial' : 'unpaid'),
            ];
        });
    }
    /**
     * Place an order.
     *
     * Everything - the order, its lines, the stock movements and the ledger
     * entry - is written in one database transaction. The v1 version mutated
     * stock *before* its try block, so a later failure left stock decremented
     * with no order to show for it.
     */
    public function place(int $sellerId, array $data, ?UploadedFile $image = null): Order
    {
        $customer = Customer::find($data['user_id']);
        if (!$customer) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())
                ->setModel(Customer::class, $data['user_id']);
        }

        $type = (int) ($data['order_type'] ?? self::TYPE_SALE);
        $cart = $data['cart'];

        // Stored outside the transaction: a file write cannot be rolled back,
        // and an orphaned upload is cheaper than a failed order.
        $imagePath = $image ? Helpers::upload('shop/', 'png', $image) : null;

        return DB::transaction(function () use ($sellerId, $customer, $type, $cart, $data, $imagePath) {
            $productIds = array_column($cart, 'id');

            // Three lookups for the whole cart rather than three per line.
            $products   = Product::whereIn('id', $productIds)->get()->keyBy('id');
            $custPrices = CustomerPrice::where('customer_id', $customer->id)
                ->whereIn('product_id', $productIds)->pluck('price', 'product_id');
            $sellPrices = SellerPrice::where('seller_id', $sellerId)
                ->whereIn('product_id', $productIds)->pluck('price', 'product_id');

            $stocks = Stock::where('seller_id', $sellerId)
                ->whereIn('product_id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');

            $lines = [];
            $subTotal = 0.0;
            $discountTotal = 0.0;
            $taxTotal = 0.0;

            foreach ($cart as $item) {
                $product = $products->get($item['id']);
                if (!$product) {
                    throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())
                        ->setModel(Product::class, $item['id']);
                }

                $quantity = (float) $item['quantity'];
                $price    = (float) ($custPrices[$item['id']]
                            ?? $sellPrices[$item['id']]
                            ?? $item['price']
                            ?? $product->selling_price);

                $stock = $stocks->get($item['id']);

                if (in_array($type, self::STOCK_CONSUMING, true)) {
                    if (!$stock || $stock->stock < $quantity) {
                        throw new InsufficientStockException(
                            'Not enough stock for ' . $product->name .
                            ' (have ' . (int) ($stock->stock ?? 0) . ', need ' . (int) $quantity . ').'
                        );
                    }
                    $stock->stock -= $quantity;
                    $stock->save();
                } elseif ($type === self::TYPE_RETURN && $stock) {
                    // A return puts the units back on the van.
                    $stock->stock += $quantity;
                    $stock->save();
                }

                // Per-line tax and discount: use what the client sent when it
                // sent something, otherwise fall back to the product's own
                // configured values.
                //
                // v1 stored Helpers::discount_calculate() on the line but added
                // $cartItem['discount'] to the order total, so the invoice and
                // its lines could disagree whenever the two differed. Here one
                // figure is used for both.
                $lineTax = isset($item['tax'])
                    ? (float) $item['tax']
                    : (float) Helpers::tax_calculate($product, $price);

                $lineDiscount = $this->lineDiscount($item, $product, $price);

                $lines[] = [
                    'product_id'          => $product->id,
                    'product_details'     => $product->toJson(),
                    'quantity'            => $quantity,
                    'price'               => $price,
                    'tax_amount'          => $lineTax,
                    'discount_on_product' => $lineDiscount,
                    'discount_type'       => $item['discount_type'] ?? $product->discount_type ?? 'amount',
                    'update_flag'         => 0,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ];

                $subTotal      += $price * $quantity;
                $discountTotal += $lineDiscount * $quantity;
                $taxTotal      += $lineTax * $quantity;

                $product->increment('order_count');
            }

            $extraDiscount = $this->extraDiscount($data, $subTotal - $discountTotal);
            $couponDiscount = (float) ($data['coupon_discount'] ?? 0);
            $grandTotal = ($subTotal - $discountTotal) + $taxTotal - $extraDiscount - $couponDiscount;

            $order = Order::create([
                'owner_id'               => $sellerId,
                'user_id'                => $customer->id,
                'type'                   => $type,
                'cash'                   => $data['cash'] ?? 1,
                'payment_id'             => $data['type'] ?? null,
                'total_tax'              => $taxTotal,
                'order_amount'           => $data['order_amount'] ?? $grandTotal,
                'extra_discount'         => $extraDiscount,
                'coupon_code'            => $data['coupon_code'] ?? null,
                'coupon_discount_amount' => $couponDiscount,
                'coupon_discount_title'  => $data['coupon_title'] ?? null,
                'collected_cash'         => $data['collected_cash'] ?? $grandTotal,
                'transaction_reference'  => $data['collected_cash'] ?? null,
                'img'                    => $imagePath,
                'update_flag'            => 0,
            ]);

            foreach ($lines as &$line) {
                $line['order_id'] = $order->id;
            }
            unset($line);
            OrderDetail::insert($lines);

            // The working copy the settlement screen reads.
            CurrentOrder::create([
                'owner_id'     => $sellerId,
                'user_id'      => $customer->id,
                'type'         => $type,
                'cash'         => $data['cash'] ?? 1,
                'total_tax'    => $taxTotal,
                'order_amount' => $data['order_amount'] ?? $grandTotal,
                'collected_cash' => $data['collected_cash'] ?? $grandTotal,
            ]);

            // Installment variants are settled separately, so they post no
            // ledger entry here.
            if (!in_array($type, [12, 24], true)) {
                Transection::create([
                    'tran_type'   => (string) $type,
                    'seller_id'   => $sellerId,
                    'account_id'  => $data['type'] ?? null,
                    'customer_id' => $customer->id,
                    'order_id'    => $order->id,
                    'amount'      => $data['collected_cash'] ?? $grandTotal,
                    'cash'        => $data['cash'] ?? 1,
                    'description' => $type === self::TYPE_SALE ? 'مبيعات' : 'مرتجع مبيعات',
                    'date'        => now()->toDateString(),
                    'balance'     => $data['collected_cash'] ?? $grandTotal,
                    'img'         => $imagePath,
                ]);

                if ($type === self::TYPE_RETURN) {
                    // balance is nullable and increment() on NULL stays NULL,
                    // so normalise before crediting the refund.
                    $customer->balance = (float) $customer->balance + $grandTotal;
                    $customer->save();
                }
            }

            return $order->load(['details', 'customer']);
        });
    }

    /**
     * A line's discount.
     *
     * A percentage is applied to that line's price; an amount is taken as
     * given. Falls back to the product's own discount when the client sends
     * none.
     */
    private function lineDiscount(array $item, Product $product, float $price): float
    {
        if (!isset($item['discount'])) {
            return (float) Helpers::discount_calculate($product, $price);
        }

        $discount = (float) $item['discount'];
        $type     = $item['discount_type'] ?? $product->discount_type ?? 'amount';

        return $type === 'percent' ? ($price / 100) * $discount : $discount;
    }

    private function extraDiscount(array $data, float $netTotal): float
    {
        $value = (float) ($data['extra_discount'] ?? 0);

        return ($data['extra_discount_type'] ?? null) === 'percent'
            ? ($netTotal * $value) / 100
            : $value;
    }
}
