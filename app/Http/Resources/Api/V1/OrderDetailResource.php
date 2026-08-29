<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetailResource extends JsonResource
{
    public function toArray($request)
    {
        // product_details is a JSON snapshot of the product as it was sold, so
        // an invoice still renders correctly after the catalogue changes.
        $snapshot = json_decode($this->product_details, true);

        return [
            'id'                  => $this->id,
            'product_id'          => $this->product_id,
            'product_name'        => $snapshot['name'] ?? null,
            'product_code'        => $snapshot['product_code'] ?? null,
            'quantity'            => (float) $this->quantity,
            'quantity_returned'   => (float) $this->quantity_returned,
            'price'               => (float) $this->price,
            'tax_amount'          => (float) $this->tax_amount,
            'discount_on_product' => (float) $this->discount_on_product,
            // Whether the stored discount came from a percentage or a flat
            // amount; the figure above is already resolved either way.
            'discount_type'       => $this->discount_type,
            'line_total'          => (float) $this->price * (float) $this->quantity,
        ];
    }
}
