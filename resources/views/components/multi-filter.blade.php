@props([
    'name',           // اسم الحقل بدون [] مثل region_id
    'label',
    'options',        // Collection أو array من العناصر
    'selected' => [], // القيم المختارة
    'valueKey' => 'id',
    'labelKey' => 'name',
    'size' => 4,
    'col' => 'col-md-3',
])

@php
    // الاختيار قد يصل كنص أو رقم، فنوحّده نصًا قبل المقارنة حتى لا يفشل
    // إعادة التحديد بسبب اختلاف النوع.
    $selectedValues = array_map('strval', (array) $selected);
@endphp

<div class="{{ $col }} mb-2">
    <label for="{{ $name }}" class="form-label">
        {{ $label }}
        <small class="text-muted">({{ \App\CPU\translate('يمكن اختيار أكثر من قيمة') }})</small>
    </label>

    {{-- name="x[]" لأن المتعدد يُرسَل مصفوفة. size يجعل الخيارات ظاهرة
         فيعرف المستخدم أنه اختيار متعدد دون الحاجة إلى select2. --}}
    <select name="{{ $name }}[]"
            id="{{ $name }}"
            class="form-control"
            multiple
            size="{{ $size }}"
            style="height:auto;">
        @foreach ($options as $option)
            @php
                $value = is_array($option) ? ($option[$valueKey] ?? null) : ($option->{$valueKey} ?? null);
                $text  = is_array($option) ? ($option[$labelKey] ?? '')   : ($option->{$labelKey} ?? '');
            @endphp
            <option value="{{ $value }}"
                {{ in_array((string) $value, $selectedValues, true) ? 'selected' : '' }}>
                {{ $text }}
            </option>
        @endforeach
    </select>
</div>
