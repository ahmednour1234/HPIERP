<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class UnitResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'              => $this->id,
            'unit_type'       => $this->unit_type,
            'symbol'          => $this->symbol,
            'conversion_rate' => (float) $this->conversion_rate,
            'base_unit_id'    => $this->base_unit_id,
            'is_base'         => (bool) $this->is_base,

            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
