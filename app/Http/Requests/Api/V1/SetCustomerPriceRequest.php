<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class SetCustomerPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'product_id'  => ['required', 'integer', 'exists:products,id'],
            'price'       => ['required', 'numeric', 'min:0'],
        ];
    }
}
