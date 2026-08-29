<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;


/**
 * Unit create/update rules. The v1 endpoints validated little or nothing, so
 * unusable rows could be written.
 */
class UnitRequest extends FormRequest
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
            $this->isUpdate() ? ['id' => ['required', 'integer', 'exists:units,id']] : [],
            [
                'unit_type'       => [$required, 'string', 'max:255'],
                'symbol'          => ['nullable', 'string', 'max:255'],
                'conversion_rate' => ['nullable', 'numeric', 'min:0'],
                'base_unit_id'    => ['nullable', 'integer'],
                'is_base'         => ['nullable', 'boolean'],
            ]
        );
    }

    /** Fields to write, excluding the identifier and any uploaded file. */
    public function modelData(): array
    {
        return $this->safe()->except(['id', 'image']);
    }
}
