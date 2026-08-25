<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// 1) ProductionOrder Model
class ProductionOrder extends Model
{
    protected $fillable = [
        'admin_id',
        'supply_order_id',
        'factory_id',
        'total_cash',
        'paid',
        'status',
    ];

    /**
     * الفوريعات التي تخص أمر الإنتاج
     */
    public function products(): HasMany
    {
        return $this->hasMany(ProductionOrderProduct::class,'production_order_id');
    }

   

    public function additionalCosts(): HasMany
    {
        return $this->hasMany(ProductionOrderAdditionalCost::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function supplyOrder(): BelongsTo
    {
        return $this->belongsTo(SupplyOrder::class);
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }
}

