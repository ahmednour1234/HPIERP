<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'code'          => $this->code,
            'coupon_type'   => $this->coupon_type,
            'user_limit'    => $this->user_limit,
            'start_date'    => $this->start_date,
            'expire_date'   => $this->expire_date,
            'min_purchase'  => (float) $this->min_purchase,
            'max_discount'  => (float) $this->max_discount,
            'discount'      => (float) $this->discount,
            'discount_type' => $this->discount_type,
            'status'        => (bool) $this->status,

            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
