<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseSeller extends Model
{
    use HasFactory;

    public function admins()
    {
        return $this->belongsTo(Admin::class,'admin_id');
    }
        public function sellers()
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }
}
