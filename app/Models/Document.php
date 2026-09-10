<?php
// app/Models/Document.php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    protected $fillable = ['name','description'];

    public function attachments(): HasMany
    {
        return $this->hasMany(DocumentAttachment::class);
    }

    /** المناديب المسند لهم هذه الوثيقة. فراغها يعني أنها عامة. */
    public function sellers(): BelongsToMany
    {
        return $this->belongsToMany(
            Seller::class,
            'document_sellers',
            'document_id',
            'seller_id'
        );
    }

    /**
     * الوثائق التي يراها مندوب بعينه: المسندة له، وكذلك العامة التي لم
     * تُسند لأحد.
     */
    public function scopeVisibleTo(Builder $query, int $sellerId): Builder
    {
        return $query->where(
            fn (Builder $q) => $q
                ->whereDoesntHave('sellers')
                ->orWhereHas('sellers', fn (Builder $s) => $s->where('admins.id', $sellerId))
        );
    }
}
