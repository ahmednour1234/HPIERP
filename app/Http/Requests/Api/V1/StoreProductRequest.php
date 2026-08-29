<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * v1 advertised POST /product/store but the route pointed at a controller
 * method that was never written, so the endpoint always 500'd. These are the
 * rules the v2 endpoint enforces.
 */
class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:255'],
            'name_en'        => ['nullable', 'string', 'max:255'],
            'product_code'   => ['required', 'string', 'max:255', 'unique:products,product_code'],
            'category_id'    => ['nullable', 'string', 'max:255'],
            'unit_type'      => ['nullable', 'integer'],
            'unit_value'     => ['nullable', 'numeric'],
            'brand'          => ['nullable', 'string', 'max:255'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'selling_price'  => ['required', 'numeric', 'min:0'],
            'discount'       => ['nullable', 'numeric', 'min:0'],
            'discount_type'  => ['nullable', 'string', 'max:255'],
            'tax'            => ['nullable', 'numeric', 'min:0'],
            'quantity'       => ['nullable', 'integer', 'min:0'],
            'limit_stock'    => ['nullable', 'integer', 'min:0'],
            'supplier_id'    => ['nullable', 'integer'],
            'type'           => ['nullable', 'string', 'max:255'],
            'image'          => ['nullable', 'image', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_code.unique' => 'A product with this code already exists.',
        ];
    }

    public function modelData(): array
    {
        return $this->safe()->except('image');
    }
}
