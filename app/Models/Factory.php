<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Factory extends Model
{
    protected $fillable = [
        'name', 'phone', 'email', 'address', 'lang', 'late', 'active'
    ];
}
