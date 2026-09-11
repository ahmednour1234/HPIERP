<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * طلب إرجاع بضاعة من عربية المندوب إلى المخزن، بانتظار موافقة الأدمن.
 *
 * المخزون لا يتحرك عند الإنشاء؛ الاعتماد وحده هو ما ينقل الكميات.
 */
class StockReturnRequest extends Model
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'seller_id', 'status', 'note', 'admin_note', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(StockReturnRequestItem::class, 'request_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
