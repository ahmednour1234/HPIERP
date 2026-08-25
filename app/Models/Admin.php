<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',   'supplier',
        'dashboard',
        'pos',
        'stock',
        'store',
        'cat',
        'unit',
        'product',
        'stock_limit',
        'coupons',
        'customer',
        'seller',
        'admin',
        'setting',
        'storage',
        'requests',
        'notification',
        'tracking',
              'regions',
        'reports',
        'vehicle_stock'
    ];
       public function storage()
    {
        return $this->belongsto(Storage::class);
    }
}
