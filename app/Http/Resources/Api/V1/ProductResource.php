<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'name_en'        => $this->name_en,
            'product_code'   => $this->product_code,
            'unit_type'      => $this->unit_type,
            'unit_value'     => (int) $this->unit_value,
            'brand'          => $this->brand,
            'category_id'    => $this->category_id,
            'purchase_price' => (float) $this->purchase_price,
            'selling_price'  => (float) $this->selling_price,
            'discount_type'  => $this->discount_type,
            'discount'       => (float) $this->discount,
            'tax'            => (float) $this->tax,
            'quantity'       => (int) $this->quantity,
            'limit_stock'    => (int) $this->limit_stock,
            'image'          => $this->image,
            'supplier_id'    => $this->supplier_id,
            'expiry_date'    => $this->expiry_date,
            'created_at'     => optional($this->created_at)->toIso8601String(),
        ];
    }
}
