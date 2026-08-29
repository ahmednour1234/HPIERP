<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A payment collected against a specific invoice.
 *
 * The cap — that the amount cannot exceed what is still owed — is enforced in
 * OrderService rather than here, because it depends on the invoice's current
 * collected_cash and has to be read under a lock.
 */
class CollectPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount'     => ['required', 'numeric', 'gt:0'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'date'       => ['required', 'date'],
            'note'       => ['nullable', 'string', 'max:2000'],
            'img'        => ['nullable', 'image', 'max:4096'],
            'cash'       => ['nullable', 'integer', 'in:1,2'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.gt'         => 'The amount must be greater than zero.',
            'account_id.exists' => 'That account does not exist.',
        ];
    }
}
