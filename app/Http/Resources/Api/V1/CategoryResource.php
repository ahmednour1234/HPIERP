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
            'image'     => $this->image,
            'type'      => (int) $this->type,

            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
