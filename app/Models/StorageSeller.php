<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorageSeller extends Model
{
    use HasFactory;
    
    public $timestamps = false;

    public function storage()
    {
        return $this->belongsTo(Storage::class, 'storage_id');
    }
       public function seller()
    {
        return $this->belongsTo(Admin::class, 'seller_id');
    }
}
