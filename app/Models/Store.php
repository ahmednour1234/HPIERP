<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
      use HasFactory;

    protected $fillable = [
        'store_name1',
        'store_code',
    ];
       protected $primaryKey = 'store_id';

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }
     public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

}
