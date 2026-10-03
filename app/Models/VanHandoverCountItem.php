<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * صنف في جرد تسليم عربية.
 *
 * expected لقطةٌ وقت الجرد لا قراءةٌ حيّة، وdifference محفوظ لا
 * محسوب: العربية تتغير بعد الجرد، فإعادة الحساب تعطي رقمًا غير
 * الذي أقرّه الأدمن.
 */
class VanHandoverCountItem extends Model
{
    protected $fillable = [
        'count_id',
        'product_id',
        'expected',
        'counted',
        'difference',
    ];

    protected $casts = [
        'expected'   => 'integer',
        'counted'    => 'integer',
        'difference' => 'integer',
    ];

    public function count(): BelongsTo
    {
        return $this->belongsTo(VanHandoverCount::class, 'count_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
