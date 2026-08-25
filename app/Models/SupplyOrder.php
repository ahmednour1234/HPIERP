<?php
// app/Models/SupplyOrder.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyOrder extends Model
{
    protected $table = 'supply_orders';

    // If you use BIGINT unsigned for PK
    protected $keyType = 'int';
    public $incrementing = true;

    protected $fillable = [
        'factory_id',
        'admin_id',
        'order_date',
        'status',
        'note',
        'expected_cost',
    ];

    protected $casts = [
        'order_date'    => 'date',
        'expected_cost' => 'decimal:2',
    ];

    /**
     * The admin who created this order.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    /**
     * The factory (or production unit) receiving this order.
     */
    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class, 'factory_id');
    }

    /**
     * All items (products) requested in this supply order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(SupplyOrderItem::class, 'supply_order_id');
    }
}
