@props([
    'route',                 // اسم المسار أو رابط جاهز
    'label' => null,
    'class' => 'btn btn-success px-4',
])

@php
    // التصدير يجب أن يصف نفس الصفوف المعروضة، فيحمل فلاتر الشاشة الحالية.
    // page تُستبعد: التصدير يشمل كامل النتيجة لا الصفحة المعروضة وحدها.
    $params = request()->except('page');
    $href = \Illuminate\Support\Facades\Route::has($route)
        ? route($route, $params)
        : $route;
@endphp

<a href="{{ $href }}" class="{{ $class }}">
    <i class="tio-download-to me-1"></i>
    {{ $label ?? \App\CPU\translate('تصدير اكسيل') }}
</a>
