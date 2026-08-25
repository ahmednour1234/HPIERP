<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderNotification extends Model
{
    use HasFactory;

    public function detailsnotification()
    {
        return $this->hasMany(OrderDetailNotification::class,'order_id');
    }

     public function owner()
    {
        return $this->belongsTo(Admin::class, 'owner_id');
    }

    public function user()
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }
    public function seller()
    {
        return $this->belongsTo(Seller::class, 'owner_id');
    }
    
    public function account()
    {
        return $this->belongsTo(Account::class, 'payment_id');
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_code', 'code');
    }


}
