<?php

// Model: Material.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    protected $fillable = [
        'name', 'description', 'pdf_file', 'unit_id', 'tax_id', 'material_type'
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Taxe::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(MaterialBatch::class);
    }
}