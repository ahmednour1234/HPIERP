<?php

namespace App\Services;

use App\Models\CurrentReserveProduct;
use App\Models\CustomerPrice;
use App\Models\Product;
use App\Models\ReserveProduct;
use App\Models\SellerPrice;
use App\Repositories\CustomerRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Reservations a seller files from the app.
 *
 * The lines are stored as a JSON blob on `data` rather than in their own
 * table, so they are decoded on the way out and priced on the way in.
 */
class ReservationService
{
    /** `type` on the row: a request for stock, or a request to send stock back. */
    public const TYPE_REQUEST = '4';
    public const TYPE_RETURN  = '7';

    /**
     * أمر الصرف: ما صرفه الأدمن فعلًا إلى عربية المندوب.
     *
     * تكتبه شاشة "إضافة مخزون للعربية" في اللوحة بعد أن تكون قد خصمت من
     * المخزن وأضافت للعربية، فالصف هنا سجل لصرف نُفِّذ لا طلب معلّق —
     * ولهذا active = 2 لا 1.
     */
    public const TYPE_ISSUE = '3';

    public function __construct(private CustomerRepository $customers)
    {
    }

    public function listForSeller(int $sellerId, array $filters): LengthAwarePaginator
    {
        return ReserveProduct::where('seller_id', $sellerId)
            ->with(['customer:id,name,mobile'])
            // طلبات المندوب وحدها: أوامر الصرف (type 3) يكتبها الأدمن
            // وتُقرأ من GET /reservations/issued، فبدون هذا الحد تظهر
            // مختلطة بها في قائمة بلا فلتر type.
            ->whereIn('type', [self::TYPE_REQUEST, self::TYPE_RETURN])
            ->when($filters['type'] ?? null, fn (Builder $q, $t) => $q->where('type', $t))
            ->when(isset($filters['active']) && $filters['active'] !== '',
                fn (Builder $q) => $q->where('active', (int) $filters['active']))
            ->when($filters['customer_id'] ?? null, fn (Builder $q, $c) => $q->where('customer_id', $c))
            ->when($filters['from'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['to'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($filters['search'] ?? null, fn (Builder $q, $term) =>
                $q->where(fn (Builder $i) => $i->where('id', $term)
                    ->orWhere('note', 'LIKE', "%{$term}%")
                    ->orWhereHas('customer', fn (Builder $c) => $c->where('name', 'LIKE', "%{$term}%"))))
            ->latest('id')
            ->paginate((int) ($filters['limit'] ?? 25), ['*'], 'page', (int) ($filters['offset'] ?? 1));
    }

    /**
     * أوامر الصرف المنفَّذة لهذا المندوب، الأحدث أولًا.
     *
     * منفصلة عن listForSeller لأن أمر الصرف ليس طلبًا يقدّمه المندوب:
     * الأدمن هو من ينشئه وقد نُفِّذ سلفًا، فلا يمر بفلتر active ولا
     * يُخلط بطلبات الحجز والرد في نفس القائمة.
     */
    public function issuedToSeller(int $sellerId, array $filters): LengthAwarePaginator
    {
        return ReserveProduct::where('seller_id', $sellerId)
            ->where('type', self::TYPE_ISSUE)
            ->when($filters['from'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['to'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '<=', $d))
            // البحث في اسم المنتج داخل data: الأسطر مخزَّنة كـ JSON في
            // العمود لا في جدول مستقل، فلا سبيل لربطها بـ products.
            ->when($filters['search'] ?? null, fn (Builder $q, $term) =>
                $q->where(fn (Builder $i) => $i->where('id', $term)
                    ->orWhere('data', 'LIKE', "%{$term}%")))
            // الفاصلة بعد الرقم تُغلق المطابقة: بدونها يطابق "product_id":143
            // كلَّ صف فيه 14316 لأنه بادئة له.
            ->when($filters['product_id'] ?? null, fn (Builder $q, $p) =>
                $q->where('data', 'LIKE', '%"product_id":' . (int) $p . ',%'))
            ->latest('id')
            ->paginate((int) ($filters['limit'] ?? 25), ['*'], 'page', (int) ($filters['offset'] ?? 1));
    }

    public function findForSeller(int $id, int $sellerId): ReserveProduct
    {
        $reservation = ReserveProduct::with(['customer:id,name,mobile'])->findOrFail($id);

        if ((int) $reservation->seller_id !== $sellerId) {
            throw new AuthorizationException('This reservation was not filed by you');
        }

        return $reservation;
    }

    /**
     * File a reservation.
     *
     * Prices are resolved server-side — a negotiated customer price beats a
     * seller price, which beats the catalogue — so a client cannot set its
     * own. Both the permanent row and the working copy are written together.
     */
    public function place(int $sellerId, array $payload): ReserveProduct
    {
        $type = (string) ($payload['type'] ?? self::TYPE_REQUEST);

        if (!empty($payload['customer_id'])
            && !$this->customers->belongsToSeller((int) $payload['customer_id'], $sellerId)) {
            throw new AuthorizationException('This customer is not assigned to you');
        }

        $productIds = array_column($payload['data'], 'product_id');
        $products   = Product::whereIn('id', $productIds)->get()->keyBy('id');

        $customerPrices = !empty($payload['customer_id'])
            ? CustomerPrice::where('customer_id', $payload['customer_id'])
                ->whereIn('product_id', $productIds)->pluck('price', 'product_id')
            : collect();

        $sellerPrices = SellerPrice::where('seller_id', $sellerId)
            ->whereIn('product_id', $productIds)->pluck('price', 'product_id');

        $lines = [];

        foreach ($payload['data'] as $item) {
            $productId = (int) $item['product_id'];
            $product   = $products->get($productId);

            if (!$product) {
                throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())
                    ->setModel(Product::class, $productId);
            }

            // A return can only send back what is actually held.
            if ($type === self::TYPE_RETURN
                && isset($item['balance'], $item['stock'])
                && (float) $item['balance'] < (float) $item['stock']) {
                throw new \InvalidArgumentException(
                    'لا توجد كمية كافية من هذا المنتج للاسترجاع: ' . $product->name
                );
            }

            $lines[] = [
                'product_id'   => $productId,
                'product_name' => $item['product_name'] ?? $product->name,
                'product_code' => $product->product_code,
                'stock'        => (float) $item['stock'],
                'balance'      => (float) ($item['balance'] ?? 0),
                'price'        => (float) ($customerPrices[$productId]
                                    ?? $sellerPrices[$productId]
                                    ?? $product->selling_price),
            ];
        }

        return DB::transaction(function () use ($sellerId, $payload, $type, $lines) {
            $reservation = ReserveProduct::create([
                'data'        => json_encode($lines, JSON_UNESCAPED_UNICODE),
                'note'        => $payload['note'] ?? null,
                'seller_id'   => $sellerId,
                'customer_id' => $payload['customer_id'] ?? null,
                'date'        => now()->toDateString(),
                'type'        => $type,
                'active'      => 1,
                'update_flag' => 0,
            ]);

            // The working copy the settlement screen reads.
            CurrentReserveProduct::create([
                'id'          => $reservation->id,
                'data'        => json_encode($lines, JSON_UNESCAPED_UNICODE),
                'note'        => $payload['note'] ?? null,
                'seller_id'   => $sellerId,
                'customer_id' => $payload['customer_id'] ?? null,
                'date'        => now()->toDateString(),
                'type'        => (int) $type,
                'update_flag' => 0,
            ]);

            return $reservation->load('customer:id,name,mobile');
        });
    }
}
