<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerRegion extends Model
{
    use HasFactory;
    
    public $timestamps = false;

    public function reg()
    {
        return $this->belongsTo(Region::class, 'region_id');
    }
}
