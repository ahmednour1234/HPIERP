<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/** Shared rules for a single-account expense or income entry. */
class MoneyMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_id'  => ['required', 'integer', 'exists:accounts,id'],
            'amount'      => ['required', 'numeric', 'gt:0'],
            'description' => ['required', 'string', 'max:255'],
            'date'        => ['required', 'date'],
        ];
    }
}
