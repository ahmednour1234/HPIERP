<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'mobile'     => $this->mobile,
            'email'      => $this->email,
            'image'      => $this->image,
            'state'      => $this->state,
            'city'       => $this->city,
            'zip_code'   => $this->zip_code,
            'address'    => $this->address,
            'due_amount' => (float) $this->due_amount,
            'credit'     => (float) $this->credit,
            'limit'      => (float) $this->limit,
            'type'       => (int) $this->type,
            'active'     => (bool) $this->active,
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
