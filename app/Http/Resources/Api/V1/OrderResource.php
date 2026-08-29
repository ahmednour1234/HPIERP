<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                     => $this->id,
            'type'                   => (int) $this->type,
            'customer_id'            => $this->user_id,
            'seller_id'              => $this->owner_id,
            'order_amount'           => (float) $this->order_amount,
            'total_tax'              => (float) $this->total_tax,
            'extra_discount'         => (float) $this->extra_discount,
            'coupon_code'            => $this->coupon_code,
            'coupon_discount_amount' => (float) $this->coupon_discount_amount,
            'collected_cash'         => (float) $this->collected_cash,
            'cash'                   => (int) $this->cash,
            'payment_id'             => $this->payment_id,
            'img'                    => $this->img,
            'customer'               => $this->whenLoaded('customer', fn () => [
                'id'     => $this->customer->id,
                'name'   => $this->customer->name,
                'mobile' => $this->customer->mobile,
            ]),
            'details'                => OrderDetailResource::collection($this->whenLoaded('details')),
            'created_at'             => optional($this->created_at)->toIso8601String(),
        ];
    }
}
