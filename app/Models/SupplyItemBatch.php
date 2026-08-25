<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyItemBatch extends Model
{
    protected $table = 'supply_item_batches';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'supply_order_item_id',
        'material_batch_id',
        'unit_id',
        'quantity',
        'material_id'
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(SupplyOrderItem::class, 'supply_order_item_id');
    }

    public function materialBatch(): BelongsTo
    {
        return $this->belongsTo(MaterialBatch::class, 'material_batch_id');
    }
 public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
