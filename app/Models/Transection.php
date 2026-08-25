<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transection extends Model
{
    use HasFactory;

    // Specify the table name if it's different from the plural of the model name
    protected $table = 'transections'; // Adjust if necessary

    // Enable timestamps for this model
    public $timestamps = true;

    // Define fillable attributes to allow mass assignment
    protected $fillable = [
        'tran_type',
        'seller_id',
        'account_id',
        'amount',
        'description',
        'debit',
        'credit',
        'balance',
        'date',
        'customer_id',
        'order_id',
        'cash'
    ];

    // Define the relationship with the Account model
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
       public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }
}
