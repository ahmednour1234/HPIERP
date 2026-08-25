<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrentReserveProduct extends Model
{
    use HasFactory;
    
    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }
    
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
