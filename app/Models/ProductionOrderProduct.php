<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class ProductionOrderProduct extends Model
{
    protected $fillable = [
        'production_order_id',
        'product_id',
        'target_quantity',
        'produced_quantity',
        'cost_price',
        'additional_cost_price',
        'production_date',
        'end_date',
        'batch_number',
        'code',
    ];

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    // App\Models\ProductionOrderProduct
public function components()
{
    return $this->hasMany(ProductionOrderComponent::class, 'production_order_product_id');
}

}
