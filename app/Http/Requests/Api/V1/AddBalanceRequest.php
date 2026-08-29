<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * v1 routed POST /customer/add-balance at a method that does not exist, so
 * there was never any validation to inherit. These are the rules the v2
 * endpoint enforces.
 */
class AddBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'account_id'  => ['required', 'integer', 'exists:accounts,id'],
            'amount'      => ['required', 'numeric', 'gt:0'],
            'date'        => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.gt' => 'The amount must be greater than zero.',
        ];
    }
}
