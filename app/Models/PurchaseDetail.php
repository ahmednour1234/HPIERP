<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseDetail extends Model
{
   protected $fillable = [
    'purchase_id',
    'material_id',
    'quantity',
    'unit',
    'unit_price',
    'discount',
    'tax_amount',
    'total',
    'expiration_date',
    'unique_code',
];

    protected $casts = [
        'batch_data' => 'array',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
