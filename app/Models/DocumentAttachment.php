<?php
// app/Models/DocumentAttachment.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAttachment extends Model
{
    protected $fillable = ['document_id','type','url'];

    protected $appends = ['src'];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * الرابط الجاهز للعرض.
     *
     * عمود url غير متجانس: الإنشاء يحفظ مسارًا نسبيًّا
     * (documents/4/x.png) والتعديل يحفظ "/storage/documents/4/x.png"،
     * فصفحة تعرض المرفق وأخرى تعرض صورة مكسورة حسب أيّ شاشة أنشأته.
     * يُحسم هنا مرة واحدة بدل أن تخمّن كل واجهة.
     */
    public function getSrcAttribute(): string
    {
        $url = (string) ($this->url ?? '');

        if ($url === '') {
            return '';
        }

        // رابط خارجي يُترك كما هو.
        if ($this->type === 'link' || preg_match('#^https?://#i', $url)) {
            return $url;
        }

        // محفوظ بالفعل تحت /storage.
        if (str_starts_with($url, '/storage/') || str_starts_with($url, 'storage/')) {
            return asset(ltrim($url, '/'));
        }

        return asset('storage/' . ltrim($url, '/'));
    }
}
