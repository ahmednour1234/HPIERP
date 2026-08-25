<?php
// app/Models/Document.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    protected $fillable = ['name','description'];

    public function attachments(): HasMany
    {
        return $this->hasMany(DocumentAttachment::class);
    }
}
