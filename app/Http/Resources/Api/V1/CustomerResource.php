<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'name_en'       => $this->name_en,
            'mobile'        => $this->mobile,
            'email'         => $this->email,
            'pharmacy_name' => $this->pharmacy_name,
            'specialist'    => $this->specialist,
            'address'       => $this->address ?? '',
            'state'         => $this->state,
            'city'          => $this->city,
            'zip_code'      => $this->zip_code,
            // Stored as varchar; cast so clients get numbers, not strings.
            'latitude'      => $this->latitude !== null && $this->latitude !== ''
                ? (float) $this->latitude : null,
            'longitude'     => $this->longitude !== null && $this->longitude !== ''
                ? (float) $this->longitude : null,
            'balance'       => (float) $this->balance,
            'credit'        => (float) $this->credit,
            'limit'         => (float) $this->limit,
            'type'          => $this->type,
            'category_id'   => $this->category_id,
            'active'        => (bool) $this->active,
            'image'         => $this->image,
            // The raw ids are always returned. `region` carries the name too,
            // but only when the relation is loaded - a client that just created
            // a customer still needs region_id back, and whenLoaded() alone
            // dropped it from the response entirely.
            'region_id'     => $this->region_id,
            'region'        => $this->whenLoaded('regions', fn () => [
                'id'   => $this->regions->id ?? null,
                'name' => $this->regions->name ?? '',
            ]),
            // Present only on the seller listing, which computes them in SQL.
            'order_count'     => $this->when(isset($this->order_count), fn () => (int) $this->order_count),
            'executed_visits' => $this->when(isset($this->executed_visits), fn () => (int) $this->executed_visits),
            'created_at'      => optional($this->created_at)->toIso8601String(),
        ];
    }
}
