<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    use HasFactory;

    // Specify the table associated with the model
    protected $table = 'salaries'; // Make sure this matches your database table name

    // Specify the fillable attributes for mass assignment
    protected $fillable = [
        'seller_id',
        'salary',
        'commission',
        'number_of_visitors',
        'result_of_visitors',
        'salary_of_visitors',
        'transport_amount',
        'collection_incentive',
        'score',
        'notemanager',
        'total',
        'other',
        'discount',
        'month',
        'number_of_days'
    ];

    // Define relationships if necessary
    public function seller()
    {
        return $this->belongsTo(Seller::class); // Assuming you have a Seller model
    }
}
