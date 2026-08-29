<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class FundTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_from_id' => ['required', 'integer', 'exists:accounts,id', 'different:account_to_id'],
            'account_to_id'   => ['required', 'integer', 'exists:accounts,id'],
            // 'min:1' on a numeric field means "at least 1", but v1 omitted the
            // numeric rule, so "abc" passed as a 3-character string.
            'amount'          => ['required', 'numeric', 'gt:0'],
            'description'     => ['required', 'string', 'max:255'],
            'date'            => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'account_from_id.different' => 'Choose two different accounts.',
        ];
    }
}
