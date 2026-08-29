<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class BrandResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'image'      => $this->image,
            'company_id' => $this->company_id,

            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
