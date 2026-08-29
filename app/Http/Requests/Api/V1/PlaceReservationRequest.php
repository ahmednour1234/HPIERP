<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A stock request (or return request) filed by a seller from the app.
 *
 * v1 checked only that `data` was a non-empty array and read the rest of each
 * line unchecked, so a malformed line reached the database.
 */
class PlaceReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Older clients send the lines as a JSON string.
        if (is_string($this->input('data'))) {
            $decoded = json_decode($this->input('data'), true);
            if (is_array($decoded)) {
                $this->merge(['data' => $decoded]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'data'                => ['required', 'array', 'min:1'],
            'data.*.product_id'   => ['required', 'integer', 'exists:products,id'],
            'data.*.stock'        => ['required', 'numeric', 'gt:0'],
            'data.*.balance'      => ['nullable', 'numeric', 'min:0'],
            'data.*.product_name' => ['nullable', 'string', 'max:255'],
            'customer_id'         => ['nullable', 'integer', 'exists:customers,id'],
            'type'                => ['nullable', 'in:4,7'],
            // What the seller typed when placing the request.
            'note'                => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'data.required'         => 'Send at least one product.',
            'data.*.stock.gt'       => 'Each line needs a quantity greater than zero.',
            'data.*.product_id.exists' => 'One of the products does not exist.',
        ];
    }
}
