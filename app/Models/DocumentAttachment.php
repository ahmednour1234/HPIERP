<?php
// app/Models/DocumentAttachment.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAttachment extends Model
{
    protected $fillable = ['document_id','type','url'];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
