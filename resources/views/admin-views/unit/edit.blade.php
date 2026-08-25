@extends('layouts.admin.app')

@section('title', \App\CPU\translate('تعديل وحدة القياس'))

@push('css_or_js')
    <style>
        .form-label {
            font-weight: 500;
        }
    </style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="mb-3">
        <div class="row align-items-center">
            <div class="col-sm">
                <h1 class="page-header-title d-flex align-items-center text-capitalize">
                    <i class="tio-edit"></i>
                    <span>{{ \App\CPU\translate('تعديل وحدة القياس') }}</span>
                </h1>
            </div>
        </div>
    </div>
    <!-- End Page Header -->

    <div class="row gx-2 gx-lg-3">
        <div class="col-sm-12 col-lg-12 mb-3">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.unit.update', [$unit->id]) }}" method="post">
                        @csrf

                        <div class="row">
                            {{-- اسم الوحدة --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ \App\CPU\translate('اسم الوحدة') }}</label>
                                <input type="text" name="unit_type" value="{{ old('unit_type', $unit->unit_type) }}" class="form-control" required>
                            </div>

                            {{-- الرمز --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ \App\CPU\translate('رمز الوحدة') }}</label>
                                <input type="text" name="symbol" value="{{ old('symbol', $unit->symbol) }}" class="form-control" placeholder="مثال: kg">
                            </div>

                            {{-- هل وحدة أساسية --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ \App\CPU\translate('هل وحدة أساسية؟') }}</label>
                                <select name="is_base" class="form-control">
                                    <option value="1" {{ $unit->is_base ? 'selected' : '' }}>{{ \App\CPU\translate('نعم') }}</option>
                                    <option value="0" {{ !$unit->is_base ? 'selected' : '' }}>{{ \App\CPU\translate('لا') }}</option>
                                </select>
                            </div>

                            {{-- الوحدة الأساسية (إن لم تكن أساسية) --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ \App\CPU\translate('الوحدة الأساسية') }}</label>
                                <select name="base_unit_id" class="form-control">
                                    <option value="">{{ \App\CPU\translate('اختر وحدة') }}</option>
                                    @foreach ($base_units as $base)
                                        <option value="{{ $base->id }}"
                                            {{ $unit->base_unit_id == $base->id ? 'selected' : '' }}>
                                            {{ $base->unit_type }} ({{ $base->symbol }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- معامل التحويل --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ \App\CPU\translate('معامل التحويل') }}</label>
                                <input type="number" name="conversion_rate" step="0.000001" class="form-control"
                                    value="{{ old('conversion_rate', $unit->conversion_rate) }}"
                                    placeholder="مثال: 0.001 (جرام)">
                            </div>
                        </div>

                        <hr>

                        <button type="submit" class="btn btn-primary">{{ \App\CPU\translate('تحديث') }}</button>
                        <a href="{{ route('admin.unit.index') }}" class="btn btn-secondary">{{ \App\CPU\translate('إلغاء') }}</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
@endpush
