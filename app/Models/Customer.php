<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'local_id', 'name', 'name_en', 'mobile', 'email', 'image', 'state', 'city',
        'zip_code', 'address', 'balance', 'credit', 'type', 'latitude', 'longitude',
        'active', 'limit', 'company_id', 'category_id', 'specialist', 'region_id',
        'pharmacy_name',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class,'user_id');
    }
      public function regions()
    {
        return $this->belongsto(Region::class,'region_id');
    }
}
