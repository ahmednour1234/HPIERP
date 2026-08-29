<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id'             => ['required', 'integer', 'exists:products,id'],
            'name'           => ['sometimes', 'required', 'string', 'max:255'],
            'name_en'        => ['nullable', 'string', 'max:255'],
            'product_code'   => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('products', 'product_code')->ignore($this->input('id')),
            ],
            'category_id'    => ['nullable', 'string', 'max:255'],
            'unit_type'      => ['nullable', 'integer'],
            'unit_value'     => ['nullable', 'numeric'],
            'brand'          => ['nullable', 'string', 'max:255'],
            'purchase_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'selling_price'  => ['sometimes', 'required', 'numeric', 'min:0'],
            'discount'       => ['nullable', 'numeric', 'min:0'],
            'discount_type'  => ['nullable', 'string', 'max:255'],
            'tax'            => ['nullable', 'numeric', 'min:0'],
            'quantity'       => ['nullable', 'integer', 'min:0'],
            'limit_stock'    => ['nullable', 'integer', 'min:0'],
            'supplier_id'    => ['nullable', 'integer'],
            'image'          => ['nullable', 'image', 'max:4096'],
        ];
    }

    public function modelData(): array
    {
        return $this->safe()->except(['id', 'image']);
    }
}
