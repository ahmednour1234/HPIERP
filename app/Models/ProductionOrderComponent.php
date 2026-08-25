<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionOrderComponent extends Model
{
    protected $fillable = [
        'production_order_id',
        'supply_order_item_id',
        'material_batch_id',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function supplyOrderItem(): BelongsTo
    {
        return $this->belongsTo(SupplyOrderItem::class);
    }

    public function materialBatch(): BelongsTo
    {
        return $this->belongsTo(MaterialBatch::class);
    }
}