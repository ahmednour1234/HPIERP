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
                'id'     => $this->customer->id,
                'name'   => $this->customer->name,
                'mobile' => $this->customer->mobile,
            ]),
            'created_at'  => optional($this->created_at)->toIso8601String(),
        ];
    }
}
