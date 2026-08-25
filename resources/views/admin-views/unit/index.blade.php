@extends('layouts.admin.app')

@section('title',\App\CPU\translate('add_new_unit_type'))

@push('css_or_js')

@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="">
        <div class="row align-items-center mb-3">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title d-flex align-items-center g-2px text-capitalize">
                    <i class="tio-add-circle-outlined"></i>
                    <span>{{\App\CPU\translate('add_new_unit_type')}}</span>
                </h1>
            </div>
        </div>
    </div>
    <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <div class="card">
                    <div class="card-body">
<form action="{{ route('admin.unit.store') }}" method="post">
    @csrf
    <div class="row">
        {{-- اسم الوحدة --}}
        <div class="col-12 col-sm-6 mb-3">
            <label class="form-label">{{ \App\CPU\translate('اسم الوحدة') }}</label>
            <input type="text" name="unit_type" value="{{ old('unit_type') }}" class="form-control" placeholder="مثال: كيلوجرام">
        </div>

        {{-- الرمز --}}
        <div class="col-12 col-sm-6 mb-3">
            <label class="form-label">{{ \App\CPU\translate('رمز الوحدة') }}</label>
            <input type="text" name="symbol" value="{{ old('symbol') }}" class="form-control" placeholder="مثال: kg">
        </div>

        {{-- هل وحدة أساسية؟ --}}
        <div class="col-12 col-sm-6 mb-3">
            <label class="form-label">{{ \App\CPU\translate('هل وحدة أساسية؟') }}</label>
            <select name="is_base" class="form-control">
                <option value="1" {{ old('is_base') == 1 ? 'selected' : '' }}>{{ \App\CPU\translate('نعم') }}</option>
                <option value="0" {{ old('is_base') == '0' ? 'selected' : '' }}>{{ \App\CPU\translate('لا') }}</option>
            </select>
        </div>

        {{-- إذا كانت وحدة فرعية، اختر الوحدة الأم --}}
        <div class="col-12 col-sm-6 mb-3">
            <label class="form-label">{{ \App\CPU\translate('الوحدة الأساسية (إذا كانت فرعية)') }}</label>
            <select name="base_unit_id" class="form-control">
                <option value="">{{ \App\CPU\translate('اختر وحدة') }}</option>
                @foreach ($base_units as $base)
                    <option value="{{ $base->id }}" {{ old('base_unit_id') == $base->id ? 'selected' : '' }}>
                        {{ $base->unit_type }} ({{ $base->symbol }})
                    </option>
                @endforeach
            </select>
        </div>

        {{-- معامل التحويل --}}
        <div class="col-12 col-sm-6 mb-3">
            <label class="form-label">{{ \App\CPU\translate('معامل التحويل') }}</label>
            <input type="number" step="0.000001" name="conversion_rate" value="{{ old('conversion_rate', 1) }}" class="form-control" placeholder="مثال: 0.001 (لـ جرام)">
        </div>
    </div>

    <hr>
    <button type="submit" class="btn btn-primary">{{ \App\CPU\translate('حفظ') }}</button>
</form>
                    </div>
                </div>
            </div>

            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h5>{{ \App\CPU\translate('unit_type_table')}} <span class="badge badge-soft-dark">{{$units->total()}}</span></h5>
                        </div>
                    </div>
                    <!-- Table -->
                    <div class="table-responsive ">
                        <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                      <thead class="thead-light">
<tr>
    <th>#</th>
    <th>{{ \App\CPU\translate('اسم الوحدة') }}</th>
    <th>{{ \App\CPU\translate('الرمز') }}</th>
    <th>{{ \App\CPU\translate('أساسية؟') }}</th>
    <th>{{ \App\CPU\translate('الوحدة الأساسية') }}</th>
    <th>{{ \App\CPU\translate('معامل التحويل') }}</th>
    <th>{{ \App\CPU\translate('الإجراءات') }}</th>
</tr>
</thead>
<tbody>
@foreach ($units as $key => $unit)
    <tr>
        <td>{{ $units->firstItem() + $key }}</td>
        <td>{{ $unit->unit_type }}</td>
        <td>{{ $unit->symbol ?? '-' }}</td>
        <td>
            @if ($unit->is_base)
                <span class="badge badge-success">{{ \App\CPU\translate('نعم') }}</span>
            @else
                <span class="badge badge-secondary">{{ \App\CPU\translate('لا') }}</span>
            @endif
        </td>
        <td>
            {{ $unit->baseUnit->unit_type ?? '-' }}
        </td>
        <td>{{ $unit->conversion_rate }}</td>
        <td>
            <a class="btn btn-white mr-1" href="{{ route('admin.unit.edit', $unit->id) }}">
                <span class="tio-edit"></span>
            </a>
            <!--<a class="btn btn-white mr-1" href="javascript:"-->
            <!--   onclick="form_alert('unit-{{ $unit->id }}','{{ \App\CPU\translate('هل أنت متأكد من الحذف؟') }}')">-->
            <!--    <span class="tio-delete"></span>-->
            <!--</a>-->
            <!--<form action="{{ route('admin.unit.delete', $unit->id) }}" method="post" id="unit-{{ $unit->id }}">-->
            <!--    @csrf @method('delete')-->
            <!--</form>-->
        </td>
    </tr>
@endforeach
</tbody>

                        </table>

                        <hr>
                        <table>
                            <tfoot>
                            {!! $units->links() !!}
                            </tfoot>
                        </table>
                        @if(count($units)==0)
                            <div class="text-center p-4">
                                <img class="mb-3 img-one-un" src="{{asset('public/assets/admin')}}/svg/illustrations/sorry.svg" alt="{{\App\CPU\translate('image_description')}}">
                                <p class="mb-0">{{ \App\CPU\translate('No_data_to_show')}}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <!-- End Table -->
        </div>
</div>
@endsection
