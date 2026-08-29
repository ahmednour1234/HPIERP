<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'local_id', 'name', 'mobile', 'email', 'image', 'state', 'city', 'zip_code',
        'address', 'due_amount', 'company_id', 'type', 'credit', 'active', 'limit',
    ];

    public function products()
    {
        return $this->hasMany(Product::class,'supplier_id');
    }
}
