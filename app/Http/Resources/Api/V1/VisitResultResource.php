<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class VisitResultResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'admin_id'    => $this->admin_id,
            'customer_id' => $this->customer_id,
            'note'        => $this->note,
            // Stored as lat/lang in this schema; exposed under clear names.
            'latitude'    => $this->lat,
            'longitude'   => $this->lang,
            'customer'    => $this->whenLoaded('customer', fn () => [
                'id'          => $this->customer->id,
                'name'        => $this->customer->name,
                'mobile'      => $this->customer->mobile,
                // المنطقة والتخصص يحتاجهما التطبيق للفلترة والعرض.
                'region_id'   => $this->customer->region_id,
                'region'      => $this->customer->relationLoaded('regions') && $this->customer->regions
                    ? ['id' => $this->customer->regions->id, 'name' => $this->customer->regions->name]
                    : null,
                'category_id' => $this->customer->category_id,
                'category'    => $this->customer->relationLoaded('category') && $this->customer->category
                    ? ['id' => $this->customer->category->id, 'name' => $this->customer->category->name]
                    : null,
            ]),
            // رابط كامل للصورة، أو null حين لا توجد.
            'img'         => $this->img,
            'img_url'     => $this->img ? asset('storage/' . $this->img) : null,
            'created_at'  => optional($this->created_at)->toIso8601String(),
        ];
    }
}
