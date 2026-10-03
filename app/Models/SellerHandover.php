<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * تسليم عهدة مندوب إلى آخر.
 *
 * @see database/migrations/2026_10_03_000000_create_seller_handovers_table.php
 */
class SellerHandover extends Model
{
    protected $fillable = [
        'from_seller_id',
        'to_seller_id',
        'started_at',
        'ended_at',
        'note',
        'created_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];

    /** صاحب العهدة: من انتهت خدمته. */
    public function fromSeller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'from_seller_id');
    }

    /** المستلم: من يحصّل فواتير الأول. */
    public function toSeller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'to_seller_id');
    }

    /**
     * التسليمات القائمة فقط.
     *
     * القائم ما لم يُنهَ بعد: started_at فارغًا يعني ساريًا منذ
     * الإنشاء، فلا يُشترط ملؤه.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('ended_at')
            ->where(function (Builder $q) {
                $q->whereNull('started_at')->orWhere('started_at', '<=', now());
            });
    }

    /**
     * معرِّفات المناديب الذين استلم هذا المندوبُ عهدتَهم الآن.
     *
     * الدالة الوحيدة التي تقرر من يرى فواتير من، فلا يتفرق الشرط
     * على عدة مواضع ويختلف أحدها عن الآخر.
     *
     * @return array<int,int>
     */
    public static function sourcesFor(int $sellerId): array
    {
        return static::query()
            ->active()
            ->where('to_seller_id', $sellerId)
            ->pluck('from_seller_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /** هل يحقّ لهذا المندوب التصرّف في فواتير ذاك؟ */
    public static function allows(int $sellerId, int $ownerId): bool
    {
        return in_array($ownerId, static::sourcesFor($sellerId), true);
    }
}
