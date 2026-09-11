<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockReturnRequestItem extends Model
{
    protected $fillable = ['request_id', 'product_id', 'quantity'];

    protected $casts = ['quantity' => 'integer'];

    public function request(): BelongsTo
    {
        return $this->belongsTo(StockReturnRequest::class, 'request_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
