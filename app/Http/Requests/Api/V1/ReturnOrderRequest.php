<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A return filed against the invoice it reverses.
 *
 * The quantities are checked against what is actually left on each line by
 * OrderService, which is the part that cannot be expressed as a rule here:
 * it depends on how much of that line has already come back.
 */
class ReturnOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Older clients send the lines as a JSON string.
        if (is_string($this->input('items'))) {
            $decoded = json_decode($this->input('items'), true);
            if (is_array($decoded)) {
                $this->merge(['items' => $decoded]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'order_id'           => ['required', 'integer', 'exists:orders,id'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'numeric', 'gt:0'],
            'note'               => ['nullable', 'string', 'max:2000'],
            'type'               => ['nullable', 'integer'],
            'cash'               => ['nullable', 'integer', 'in:1,2'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.exists'         => 'No such invoice.',
            'items.required'          => 'Send the lines you are returning.',
            'items.*.quantity.gt'     => 'Each line needs a quantity greater than zero.',
            'items.*.product_id.exists' => 'One of the products does not exist.',
        ];
    }
}
