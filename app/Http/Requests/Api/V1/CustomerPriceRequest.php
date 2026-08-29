<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Guards the cart payload that the v1 endpoint looped over unchecked.
 */
class CustomerPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:customers,id'],
            'cart'    => ['required', 'array', 'min:1'],
            'cart.*'  => ['required', 'integer', 'exists:products,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'cart.required' => 'Send a cart of product ids.',
            'cart.*.exists' => 'One of the products in the cart does not exist.',
        ];
    }
}
