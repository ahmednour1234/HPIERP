<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class SupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function isUpdate(): bool
    {
        return $this->filled('id');
    }

    public function rules(): array
    {
        $required = $this->isUpdate() ? 'sometimes' : 'required';

        return array_merge(
            $this->isUpdate() ? ['id' => ['required', 'integer', 'exists:suppliers,id']] : [],
            [
                'name'       => [$required, 'string', 'max:255'],
                'mobile'     => [$required, 'string', 'max:255'],
                'email'      => ['nullable', 'email', 'max:255'],
                'state'      => ['nullable', 'string', 'max:255'],
                'city'       => ['nullable', 'string', 'max:255'],
                'zip_code'   => ['nullable', 'string', 'max:255'],
                'address'    => ['nullable', 'string', 'max:255'],
                'due_amount' => ['nullable', 'numeric'],
                'credit'     => ['nullable', 'numeric'],
                'limit'      => ['nullable', 'numeric', 'min:0'],
                'type'       => ['nullable', 'integer'],
                'active'     => ['nullable', 'boolean'],
                'image'      => ['nullable', 'image', 'max:4096'],
            ]
        );
    }

    public function modelData(): array
    {
        return $this->safe()->except(['id', 'image']);
    }
}
