<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialBatch extends Model
{
    protected $fillable = [
        'material_id', 'quantity', 'expiration_date', 'unique_code','total','tax_amount','unit_id'
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
      public function unitRelation(): BelongsTo
    {
        return $this->belongsTo(Unit::class,'unit_id');
    }
    
}
