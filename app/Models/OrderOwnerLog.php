<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * سجلّ نقل ملكية فاتورة من مندوب إلى آخر.
 *
 * بعد الترحيل يحمل orders.owner_id المندوبَ الجديد، فهذا الجدول هو
 * الموضع الوحيد الذي يُعرف منه أصل الفاتورة.
 *
 * @see database/migrations/2026_10_03_000001_create_order_owner_logs_table.php
 */
class OrderOwnerLog extends Model
{
    protected $fillable = [
        'order_id',
        'from_seller_id',
        'to_seller_id',
        'handover_id',
        'moved_by',
        'reason',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /** الأصل: صاحب الفاتورة قبل الترحيل. */
    public function fromSeller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'from_seller_id');
    }

    public function toSeller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'to_seller_id');
    }

    public function handover(): BelongsTo
    {
        return $this->belongsTo(SellerHandover::class, 'handover_id');
    }

    /**
     * صاحب الفاتورة الأصلي، قبل أي ترحيل.
     *
     * أقدم صفٍّ في السجلّ يحمل الأصل: ما بعده نقلٌ عن مالكٍ سبق أن
     * رُحِّلت إليه.
     */
    public static function originalOwnerOf(int $orderId): ?int
    {
        $first = static::where('order_id', $orderId)->orderBy('id')->first();

        return $first ? (int) $first->from_seller_id : null;
    }

    /**
     * الأصل لعدة فواتير دفعةً واحدة.
     *
     * القوائم تعرض عشرات الصفوف، والسؤال عن كلٍّ على حدة يعني
     * استعلامًا لكل صف.
     *
     * @param  array<int,int>  $orderIds
     * @return array<int,int>  order_id => original_owner_id
     */
    public static function originalOwnersFor(array $orderIds): array
    {
        if (empty($orderIds)) {
            return [];
        }

        return static::whereIn('order_id', $orderIds)
            ->orderBy('id')
            ->get(['order_id', 'from_seller_id'])
            // أقدم صفٍّ لكل فاتورة: keyBy يُبقي الأخير، فتُعكس أولًا.
            ->reverse()
            ->keyBy('order_id')
            ->map(fn ($row) => (int) $row->from_seller_id)
            // العكس يقلب ترتيب المفاتيح، فتُعاد بترتيب الطلب.
            ->sortKeys()
            ->all();
    }
}
