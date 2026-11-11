<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class RecordVisitResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'note'        => ['required', 'string', 'max:5000'],
            // الإحداثيات إجبارية: زيارة بلا موقع لا يمكن التحقق منها على الخريطة.
            'lat'         => ['required', 'numeric', 'between:-90,90', 'not_in:0'],
            'lang'        => ['required', 'numeric', 'between:-180,180', 'not_in:0'],
            // صورة الزيارة: لم تكن ضمن القواعد فكانت تُحذف قبل الحفظ.
            'img'         => ['nullable', 'image', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'lat.required'  => 'الموقع مطلوب: فعّل الـ GPS وأعد المحاولة.',
            'lang.required' => 'الموقع مطلوب: فعّل الـ GPS وأعد المحاولة.',
            'lat.not_in'    => 'الموقع غير صالح: فعّل الـ GPS وأعد المحاولة.',
            'lang.not_in'   => 'الموقع غير صالح: فعّل الـ GPS وأعد المحاولة.',
            'lat.between'  => 'Latitude must be between -90 and 90.',
            'lang.between' => 'Longitude must be between -180 and 180.',
        ];
    }
}
