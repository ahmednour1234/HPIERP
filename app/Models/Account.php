<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'storage_id', 'account', 'description', 'balance', 'account_number', 'total_in', 'total_out', 'company_id',
    ];

    public function transections()
    {
        return $this->hasMany(Transection::class);
    }
        public function storage()
    {
        return $this->belongsTo(Storage::class, 'storage_id');
    }
}
