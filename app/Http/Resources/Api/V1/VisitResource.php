<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class VisitResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'seller_id'   => $this->seller_id,
            'customer_id' => $this->customer_id,
            'note'        => $this->note,
            'date'        => $this->date,
            'customer'    => $this->whenLoaded('customer', fn () => [
                'id'      => $this->customer->id,
                'name'    => $this->customer->name,
                'mobile'  => $this->customer->mobile,
                'address' => $this->customer->address,
            ]),
            'created_at'  => optional($this->created_at)->toIso8601String(),
        ];
    }
}
