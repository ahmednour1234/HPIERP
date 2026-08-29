<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'tran_type'   => $this->tran_type,
            'account_id'  => $this->account_id,
            'seller_id'   => $this->seller_id,
            'customer_id' => $this->customer_id,
            'supplier_id' => $this->supplier_id,
            'order_id'    => $this->order_id,
            'amount'      => (float) $this->amount,
            'description' => $this->description,
            'debit'       => (float) $this->debit,
            'credit'      => (float) $this->credit,
            'balance'     => (float) $this->balance,
            'date'        => $this->date,
            'active'      => (bool) $this->active,
            'created_at'  => optional($this->created_at)->toIso8601String(),
        ];
    }
}
