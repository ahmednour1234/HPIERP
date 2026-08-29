<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray($request)
    {
        // The lines live as a JSON blob on `data`, not in their own table.
        $lines = json_decode($this->data, true) ?: [];

        $total = 0.0;
        foreach ($lines as $line) {
            $total += (float) ($line['price'] ?? 0) * (float) ($line['stock'] ?? 0);
        }

        return [
            'id'          => $this->id,
            'type'        => (string) $this->type,
            'type_text'   => (string) $this->type === '7' ? 'return' : 'request',
            'active'      => (int) $this->active,
            'status_text' => (int) $this->active === 1 ? 'pending' : 'closed',
            'note'        => $this->note,
            'date'        => $this->date,
            'customer'    => $this->whenLoaded('customer', fn () => $this->customer ? [
                'id'     => $this->customer->id,
                'name'   => $this->customer->name,
                'mobile' => $this->customer->mobile,
            ] : null),
            'items' => array_map(fn ($line) => [
                'product_id'   => (int) ($line['product_id'] ?? 0),
                'product_name' => $line['product_name'] ?? null,
                'product_code' => $line['product_code'] ?? null,
                'quantity'     => (float) ($line['stock'] ?? 0),
                'balance'      => (float) ($line['balance'] ?? 0),
                'price'        => (float) ($line['price'] ?? 0),
                'line_total'   => round((float) ($line['price'] ?? 0) * (float) ($line['stock'] ?? 0), 2),
            ], $lines),
            'items_count' => count($lines),
            'total'       => round($total, 2),
            'created_at'  => optional($this->created_at)->toIso8601String(),
        ];
    }
}
