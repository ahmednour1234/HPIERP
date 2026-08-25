@extends('layouts.admin.app')

@section('title', \App\CPU\translate('تعديل شفت'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/custom.css') }}" />
@endpush

@section('content')
<div class="row">
    <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.shift.update', $shift->id) }}" method="POST">
                    @csrf

                    <div class="row">
                        {{-- اسم الشفت --}}
                        <div class="col-12 col-sm-6 col-md-6 col-lg-6">
                            <div class="form-group">
                                <label for="name">{{ \App\CPU\translate('اسم الشفت') }}</label>
                                <input type="text"
                                       name="name"
                                       id="name"
                                       class="form-control"
                                       value="{{ old('name', $shift->name) }}"
                                       placeholder="{{ \App\CPU\translate('أدخل اسم الشفت') }}"
                                       required>
                                <input type="hidden" name="position" value="{{ old('position', $shift->position) }}">
                            </div>
                        </div>

                        {{-- المسافة بالكيلومتر --}}
                        <div class="col-12 col-sm-6 col-md-6 col-lg-6">
                            <div class="form-group">
                                <label for="kilometer">{{ \App\CPU\translate('المسافة بالكيلومتر') }}</label>
                                <input type="number"
                                       name="kilometer"
                                       id="kilometer"
                                       class="form-control"
                                       min="0"
                                       required
                                       value="{{ old('kilometer', $shift->kilometer) }}">
                            </div>
                        </div>

                        {{-- وقت البداية --}}
                        <div class="col-12 col-sm-6 col-md-6 col-lg-6">
                            <div class="form-group">
                                <label for="start">{{ \App\CPU\translate('وقت البداية') }}</label>
                                <input type="time"
                                       name="start"
                                       id="start"
                                       class="form-control"
                                       value="{{ old('start', $shift->start) }}"
                                       required>
                            </div>
                        </div>

                        {{-- وقت النهاية --}}
                        <div class="col-12 col-sm-6 col-md-6 col-lg-6">
                            <div class="form-group">
                                <label for="end">{{ \App\CPU\translate('وقت النهاية') }}</label>
                                <input type="time"
                                       name="end"
                                       id="end"
                                       class="form-control"
                                       value="{{ old('end', $shift->end) }}"
                                       required>
                            </div>
                        </div>

                        {{-- مدة الاستراحة --}}
                        <div class="col-12 col-sm-6 col-md-6 col-lg-6">
                            <div class="form-group">
                                <label for="breake">{{ \App\CPU\translate('مدة الاستراحة (بالدقائق)') }}</label>
                                <input type="number"
                                       name="breake"
                                       id="breake"
                                       class="form-control"
                                       min="0"
                                       required
                                       value="{{ old('breake', $shift->breake) }}">
                            </div>
                        </div>

                        {{-- أقصى مدة للبصمة بعد انتهاء الشفت --}}
                        <div class="col-12 col-sm-6 col-md-6 col-lg-6">
                            <div class="form-group">
                                <label for="max">{{ \App\CPU\translate('أقصى مدة للبصمة بعد انتهاء الشيفت (بالدقائق)') }}</label>
                                <input type="number"
                                       name="max"
                                       id="max"
                                       class="form-control"
                                       min="0"
                                       value="{{ old('max', $shift->max) }}">
                            </div>
                        </div>

                        <input type="hidden" name="active" value="{{ old('active', $shift->active) }}">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        {{ \App\CPU\translate('تحديث') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const isDivided = document.getElementById('is_divided');
        const dividedFields = document.getElementById('divided_fields');
        const divisionsInput = document.getElementById('divisions_count');
        const hoursInput = document.getElementById('division_hours');

        function toggleDivided() {
            if (isDivided && isDivided.checked) {
                dividedFields.style.display = 'flex';
                divisionsInput.required = true;
                hoursInput.required = true;
            } else {
                dividedFields.style.display = 'none';
                divisionsInput.required = false;
                hoursInput.required = false;
                divisionsInput.value = '';
                hoursInput.value = '';
            }
        }

        if (isDivided) {
            isDivided.addEventListener('change', toggleDivided);
            toggleDivided();
        }
    });
</script>
