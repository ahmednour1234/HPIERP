<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminSeller extends Model
{
    use HasFactory;

    public $timestamps = false;
    
    public function sellers()
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }
}
