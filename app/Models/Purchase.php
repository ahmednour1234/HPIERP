<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
 
    protected $fillable = [
        'supplier_id','admin_id','status',
        'payment_type','paid_amount',
        'image_path','sub_total','total_discount','tax_amount'
    ];


    public function details(): HasMany
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
