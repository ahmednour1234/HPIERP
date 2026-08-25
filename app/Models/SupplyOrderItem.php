<?php
// app/Models/SupplyOrderItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyOrderItem extends Model
{
    protected $table = 'supply_order_items';

    protected $keyType = 'int';
    public $incrementing = true;

    protected $fillable = [
        'supply_order_id',
        'product_id',
        'product_quantity',
        'expected_cost_per_unit',
    ];

    protected $casts = [
        'product_quantity'         => 'decimal:3',
        'expected_cost_per_unit'   => 'decimal:2',
    ];

    /**
     * The parent supply order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(SupplyOrder::class, 'supply_order_id');
    }

    /**
     * The product that will be manufactured.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * The batches of raw material consumed for this item.
     */
    public function batches()
    {
        return $this->hasMany(SupplyItemBatch::class, 'supply_order_item_id');
    }
}
