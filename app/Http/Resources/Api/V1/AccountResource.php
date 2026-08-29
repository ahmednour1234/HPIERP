<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'             => $this->id,
            'account'        => $this->account,
            'account_number' => $this->account_number,
            'description'    => $this->description,
            'balance'        => (float) $this->balance,
            'total_in'       => (float) $this->total_in,
            'total_out'      => (float) $this->total_out,
            'storage_id'     => $this->storage_id,

            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
