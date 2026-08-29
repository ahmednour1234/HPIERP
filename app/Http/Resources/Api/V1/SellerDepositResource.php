<?php

namespace App\Http\Resources\Api\V1;

use App\Services\SellerDepositService;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerDepositResource extends JsonResource
{
    public function toArray($request)
    {
        $status = (int) $this->active;

        return [
            'id'         => $this->id,
            'seller_id'  => $this->seller_id,
            'account_id' => $this->account_id,
            // amount is a varchar in this schema; cast so clients get a number.
            'amount'     => (float) $this->amount,
            'note'       => $this->note,
            'image'      => $this->img !== '' ? $this->img : null,
            'status'     => $status,
            'status_text' => match ($status) {
                SellerDepositService::APPROVED => 'approved',
                SellerDepositService::REJECTED => 'rejected',
                default                        => 'pending',
            },
            'account'    => $this->whenLoaded('accounts', fn () => [
                'id'             => $this->accounts->id ?? null,
                'account'        => $this->accounts->account ?? null,
                'account_number' => $this->accounts->account_number ?? null,
            ]),
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
