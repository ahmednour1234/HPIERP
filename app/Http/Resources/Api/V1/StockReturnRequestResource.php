<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class StockReturnRequestResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'seller_id'  => (int) $this->seller_id,

            // pending | approved | rejected
            'status'      => $this->status,
            'status_text' => $this->statusText(),

            'note'       => $this->note,
            'admin_note' => $this->admin_note,

            'reviewed_at' => optional($this->reviewed_at)->toIso8601String(),
            'created_at'  => optional($this->created_at)->toIso8601String(),

            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'product_id' => (int) $item->product_id,
                'quantity'   => (int) $item->quantity,
                'product'    => $item->product ? [
                    'id'           => $item->product->id,
                    'name'         => $item->product->name,
                    'product_code' => $item->product->product_code,
                ] : null,
            ])->values()),
        ];
    }

    private function statusText(): string
    {
        return [
            'pending'  => 'بانتظار الموافقة',
            'approved' => 'تمت الموافقة',
            'rejected' => 'مرفوض',
        ][$this->status] ?? $this->status;
    }
}
