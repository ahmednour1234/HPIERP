<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
        protected $fillable = [
        'user_id',
        'owner_id',
        'total_tax', // Add total_tax to fillable properties
        'order_amount',
        'extra_discount',
        'coupon_discount_amount',
        'collected_cash',
        'type',
        'cash',
        'payment_id',
        'notification',
        'transaction_reference',
        'active',
        'img'
    ];

    public function details()
    {
        return $this->hasMany(OrderDetail::class);
    }
    public function parentOrder()
{
    return $this->belongsTo(Order::class, 'parent_id');
}
// app/Models/Order.php
public function returnedOrders()
{
    return $this->hasMany(Order::class, 'parent_id');
}


    public function customer()
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
