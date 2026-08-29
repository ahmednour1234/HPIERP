<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerPrice extends Model
{
    use HasFactory;

    protected $fillable = ['local_id', 'seller_id', 'product_id', 'price'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function sellers()
    {
        return $this->hasMany(Seller::class, 'seller_id');
    }
}
