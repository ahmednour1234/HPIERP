<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;
    public $timestamps = true;
    function convertQuantity($value, Unit $from, Unit $to)
{
  
    // تحويل إلى الوحدة الأساسية ثم إلى الهدف
    $toBase = $value * $from->conversion_rate;
    return $toBase / $to->conversion_rate;
}
public function baseUnit()
{
    return $this->belongsTo(Unit::class, 'base_unit_id');
}

}
