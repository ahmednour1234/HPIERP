<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrentReserveProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'id', 'data', 'note', 'seller_id', 'customer_id', 'date', 'type',
        'active', 'notification', 'insert_flag', 'update_flag',
    ];
    
    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }
    
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
