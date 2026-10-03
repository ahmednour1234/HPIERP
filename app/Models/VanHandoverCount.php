<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * جرد تسليم عربية مندوب.
 *
 * @see database/migrations/2026_10_03_000002_create_van_handover_counts_table.php
 */
class VanHandoverCount extends Model
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'seller_id',
        'handover_id',
        'status',
        'note',
        'admin_note',
        'counted_by',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(VanHandoverCountItem::class, 'count_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function handover(): BelongsTo
    {
        return $this->belongsTo(SellerHandover::class, 'handover_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /** العجز: مجموع الفروق السالبة، موجبًا. */
    public function shortage(): int
    {
        return (int) abs($this->items->where('difference', '<', 0)->sum('difference'));
    }

    /** الزيادة: مجموع الفروق الموجبة. */
    public function surplus(): int
    {
        return (int) $this->items->where('difference', '>', 0)->sum('difference');
    }
}
