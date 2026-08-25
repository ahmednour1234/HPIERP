<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;
    
    // public function seller_exist($seller_id)
    // {
    //     return SellerRegion::where('seller_id', $seller_id)->where('region_id', $this->id)->count();
    // }
}
