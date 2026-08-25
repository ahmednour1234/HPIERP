<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionSeller extends Model
{
    use HasFactory;

    // Specify the table name (optional if it matches the pluralized model name)
    protected $table = 'transaction_sellers';

    // Define fillable fields for mass assignment
    protected $fillable = [
        'seller_id',
        'account_id',
        'amount',
        'note',
        'actvie',
        'img',
    ];

    // Define the relationship with the Seller model
    public function sellers()
    {
        return $this->belongsTo(Seller::class,'seller_id');
    }
      public function accounts()
    {
        return $this->belongsTo(Account::class,'account_id');
    }
}
