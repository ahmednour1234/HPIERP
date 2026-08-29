<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;


/**
 * Coupon create/update rules. The v1 endpoints validated little or nothing, so
 * unusable rows could be written.
 */
class CouponRequest extends FormRequest
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
            $this->isUpdate() ? ['id' => ['required', 'integer', 'exists:coupons,id']] : [],
            [
                'title'         => [$required, 'string', 'max:100'],
                'code'          => [$required, 'string', 'max:255'],
                'coupon_type'   => ['nullable', 'string', 'max:255'],
                'user_limit'    => ['nullable', 'integer', 'min:0'],
                'start_date'    => ['nullable', 'date'],
                'expire_date'   => ['nullable', 'date', 'after_or_equal:start_date'],
                'min_purchase'  => ['nullable', 'numeric', 'min:0'],
                'max_discount'  => ['nullable', 'numeric', 'min:0'],
                'discount'      => ['nullable', 'numeric', 'min:0'],
                'discount_type' => ['nullable', 'string', 'max:15'],
                'status'        => ['nullable', 'boolean'],
            ]
        );
    }

    /** Fields to write, excluding the identifier and any uploaded file. */
    public function modelData(): array
    {
        return $this->safe()->except(['id', 'image']);
    }
}
