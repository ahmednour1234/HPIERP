<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * v1 accepted the cart as a loosely-quoted string and repaired it with a
 * regex before json_decode. Here it is a real array, validated line by line.
 */
class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Accept a JSON-encoded cart from older clients before validating. */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('cart'))) {
            $decoded = json_decode($this->input('cart'), true);
            if (is_array($decoded)) {
                $this->merge(['cart' => $decoded]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'user_id'             => ['required', 'integer', 'exists:customers,id'],
            'order_type'          => ['nullable', 'integer', 'in:4,7,12,24'],
            'cart'                => ['required', 'array', 'min:1'],
            'cart.*.id'           => ['required', 'integer', 'exists:products,id'],
            'cart.*.quantity'     => ['required', 'numeric', 'gt:0'],
            'cart.*.price'        => ['nullable', 'numeric', 'min:0'],
            // Per-line discount and tax. When absent the server falls back to
            // the product's own configured values, so a client that sends
            // neither still gets correct figures.
            'cart.*.discount'     => ['nullable', 'numeric', 'min:0'],
            'cart.*.discount_type' => ['nullable', 'string', 'in:percent,amount'],
            'cart.*.tax'          => ['nullable', 'numeric', 'min:0'],
            'type'                => ['nullable', 'integer'],
            'cash'                => ['nullable', 'integer'],
            'collected_cash'      => ['nullable', 'numeric', 'min:0'],
            'order_amount'        => ['nullable', 'numeric', 'min:0'],
            'extra_discount'      => ['nullable', 'numeric', 'min:0'],
            'extra_discount_type' => ['nullable', 'string', 'in:percent,amount'],
            'coupon_code'         => ['nullable', 'string', 'max:255'],
            'coupon_title'        => ['nullable', 'string', 'max:255'],
            'coupon_discount'     => ['nullable', 'numeric', 'min:0'],
            // The receipt or transfer slip photographed at the counter.
            'img'                 => ['nullable', 'image', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'cart.required'         => 'The cart is empty.',
            'cart.*.quantity.gt'    => 'Each line needs a quantity greater than zero.',
            'cart.*.id.exists'      => 'One of the products in the cart does not exist.',
        ];
    }
}
