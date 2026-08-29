<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The legacy endpoint called validate([]) with no rules and fell back to
 * placeholder values ('das' for email, '0' for mobile), so unusable rows could
 * be written. These are the real constraints.
 */
class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route already sits behind auth:admin-api
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'name_en'       => ['nullable', 'string', 'max:255'],
            'mobile'        => ['required', 'string', 'max:255'],
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
            'image'         => ['nullable', 'image', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'   => 'Customer name is required.',
            'mobile.required' => 'Mobile number is required.',
            'email.email'     => 'Enter a valid email address.',
            'region_id.exists'=> 'That region does not exist.',
        ];
    }

    /** Only the validated, non-file fields belong in the model. */
    public function modelData(): array
    {
        return $this->safe()->except('image');
    }
}
