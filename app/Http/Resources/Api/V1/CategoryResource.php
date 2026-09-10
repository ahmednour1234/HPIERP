<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'        => $this->id,
            'name'      => $this->name,
            'parent_id' => (int) $this->parent_id,
            'position'  => (int) $this->position,
            'status'    => (bool) $this->status,

            // اللوحة تعرض الصور من storage/category، لكن العمود يحمل اسم
            // الملف وحده، فالتطبيق لا يستطيع عرضه. نرجع الاسم كما هو
            // للتوافق مع أي عميل قائم، ونضيف الرابط الكامل بجانبه.
            'image'     => $this->image,
            'image_url' => $this->image ? asset('storage/category/' . $this->image) : null,

            // 0 = تخصص طبي، 1 = فئة منتجات.
            'type'      => (int) $this->type,

            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
