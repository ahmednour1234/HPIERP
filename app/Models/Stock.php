<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;
  protected $fillable = [
        'tran_type', // Add tran_type here
        'product_id',
        'quantity',
        'seller_id',
        'store_id'
        // Add other fillable attributes as needed
    ];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }
    
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
