<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;


/**
 * Category create/update rules. The v1 endpoints validated little or nothing, so
 * unusable rows could be written.
 */
class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** True when this is an update, which carries the record id. */
    protected function isUpdate(): bool
    {
        return $this->filled('id');
    }

    public function rules(): array
    {
        $required = $this->isUpdate() ? 'sometimes' : 'required';

        return array_merge(
            $this->isUpdate() ? ['id' => ['required', 'integer', 'exists:categories,id']] : [],
            [
                'name'      => [$required, 'string', 'max:255'],
                'parent_id' => ['nullable', 'integer'],
                'position'  => ['nullable', 'integer'],
                'status'    => ['nullable', 'boolean'],
                'type'      => ['nullable', 'integer'],
                'image'     => ['nullable', 'image', 'max:4096'],
            ]
        );
    }

    /** Fields to write, excluding the identifier and any uploaded file. */
    public function modelData(): array
    {
        return $this->safe()->except(['id', 'image']);
    }
}
