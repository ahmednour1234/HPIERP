<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultVisitor extends Model
{
    use HasFactory;
  protected $fillable = [
        'customer_id',
        'admin_id',
        'note',
        'lang',
        'lat',
        // صورة الزيارة المرفوعة من التطبيق.
        'img',
    ];
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class,'admin_id');
    }
}
