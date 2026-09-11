<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ما يرجعه المندوب من عربيته إلى المخزن.
 *
 * الكميات أعداد صحيحة موجبة: جدول stocks يخزّنها كذلك، فكسر العدد
 * يُقتطع صامتًا عند الحفظ.
 */
class StockReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
            'note'               => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'              => 'حدد الأصناف المطلوب إرجاعها.',
            'items.min'                   => 'حدد صنفًا واحدًا على الأقل.',
            'items.*.product_id.required' => 'المنتج مطلوب.',
            'items.*.quantity.min'        => 'الكمية يجب أن تكون 1 على الأقل.',
        ];
    }
}
