<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The v1 endpoint required four amount fields the client had to compute
 * (total_due_amount, remaining_due_amount) and trusted them. Only the amount
 * actually being paid is needed; the service derives the rest.
 */
class SupplierPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'account_id'  => ['required', 'integer', 'exists:accounts,id'],
            'amount'      => ['required', 'numeric', 'gt:0'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
