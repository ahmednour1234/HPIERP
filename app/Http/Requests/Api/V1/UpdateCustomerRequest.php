<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ownership is enforced in CustomerService
    }

    public function rules(): array
    {
        return [
            'id'            => ['required', 'integer'],
            'name'          => ['sometimes', 'required', 'string', 'max:255'],
            'name_en'       => ['nullable', 'string', 'max:255'],
            'mobile'        => ['sometimes', 'required', 'string', 'max:255'],
            'email'         => ['nullable', 'email', 'max:255'],
            'pharmacy_name' => ['nullable', 'string', 'max:5000'],
            'region_id'     => ['nullable', 'integer', 'exists:regions,id'],
            'category_id'   => ['nullable', 'integer'],
            'specialist'    => ['nullable', 'integer'],
            'state'         => ['nullable', 'string', 'max:255'],
            'city'          => ['nullable', 'string', 'max:255'],
            'zip_code'      => ['nullable', 'string', 'max:255'],
            'address'       => ['nullable', 'string', 'max:255'],
            'type'          => ['nullable', 'integer'],
            'limit'         => ['nullable', 'numeric', 'min:0'],
            // The pharmacy's location on the map. Stored as varchar, so the
            // range is enforced here rather than by the column type.
            'latitude'      => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'     => ['nullable', 'numeric', 'between:-180,180'],
            'active'        => ['nullable', 'boolean'],
            'image'         => ['nullable', 'image', 'max:4096'],
        ];
    }

    /** Fields to write, excluding the identifier and the uploaded file. */
    public function modelData(): array
    {
        return $this->safe()->except(['id', 'image']);
    }
}
