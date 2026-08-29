<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A seller handing in the cash they collected, against one of the company's
 * accounts.
 *
 * The v1 endpoint validated only `amount|numeric` and `account_id|required`,
 * so a negative amount, a zero, or an account id that does not exist were all
 * accepted — and because `img` is NOT NULL with no default, every request
 * without a photo died with a 500 before any of it mattered.
 */
class SellerDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'amount'     => ['required', 'numeric', 'gt:0'],
            'note'       => ['nullable', 'string', 'max:2000'],
            // The deposit slip. Optional: a seller may hand cash over in
            // person and photograph the receipt later.
            'img'        => ['nullable', 'image', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'account_id.exists' => 'That account does not exist.',
            'amount.gt'         => 'The amount must be greater than zero.',
        ];
    }
}
