@extends('layouts.admin.app')

@section('title',\App\CPU\translate('Visitor'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/custom.css"/>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="row align-items-center mb-3">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title d-flex align-items-center g-2px text-capitalize"><i
                        class="tio-filter-list"></i> {{\App\CPU\translate('قائمة الزيارات')}}
                    <span class="badge badge-soft-dark ml-2">{{$visitors->total()}}</span>
                </h1>
            </div>
        </div>
        <div class="card-header">
    <form action="{{ route('admin.visitor.index') }}" method="GET">
        <div class="row justify-content-between align-items-center flex-grow-1">
            <!-- Search by Seller -->
            <div class="col-12 col-sm-4 mb-3 mb-sm-0">
                <div class="form-group">
                    <label for="seller_id">{{ \App\CPU\translate('البحث عن طريق المندوب') }}</label>
                    <select name="seller_id" id="seller_id" class="form-control">
                        <option value="">{{ \App\CPU\translate('اختار المندوب') }}</option>
                        @foreach($sellers as $seller)
                            <option value="{{ $seller->id }}" {{ request()->seller_id == $seller->id ? 'selected' : '' }}>
                                {{ $seller->email }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-12 col-sm-4 mb-3 mb-sm-0">
                <div class="form-group">
                    <label for="region_id">{{ \App\CPU\translate('البحث عن طريق المنطقة') }}</label>
                    <select name="region_id" id="region_id" class="form-control">
                        <option value="">{{ \App\CPU\translate('اختار منطقة') }}</option>
                        @foreach($regions as $region)
                            <option value="{{ $region->id }}" {{ request()->region_id == $region->id ? 'selected' : '' }}>
                                {{ $region->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Search by Date Range -->
            <div class="col-12 col-sm-3 mb-3 mb-sm-0">
                <div class="form-group">
                    <label for="from_date">{{ \App\CPU\translate('من تاريخ') }}</label>
                    <input type="date" name="from_date" id="from_date" class="form-control" value="{{ request()->from_date }}">
                </div>
            </div>

            <div class="col-12 col-sm-3 mb-3 mb-sm-0">
                <div class="form-group">
                    <label for="to_date">{{ \App\CPU\translate('إلى تاريخ') }}</label>
                    <input type="date" name="to_date" id="to_date" class="form-control" value="{{ request()->to_date }}">
                </div>
            </div>

            <!-- Submit Button -->
            <div class="col-12 col-sm-2 mb-3 mb-sm-0">
                <button type="submit" class="btn btn-primary">{{ \App\CPU\translate('بحث') }}</button>
            </div>
        </div>
    </form>
    <form method="GET" action="{{ route('admin.visitor.export') }}">
    <input type="hidden" name="seller_id" value="{{ request('seller_id') }}">
    <input type="hidden" name="region_id" value="{{ request('region_id') }}">
    <input type="hidden" name="from_date" value="{{ request('from_date') }}">
    <input type="hidden" name="to_date" value="{{ request('to_date') }}">
    <button type="submit">Export CSV</button>
</form>

</div>

        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <!-- Card -->
                <div class="card">
                    <!-- Header -->
                    <div class="card-header">
                        <div class="row justify-content-between align-items-center flex-grow-1">
                            {{-- <div class="col-12 col-sm-7 col-md-6 col-lg-4 col-xl-6 mb-3 mb-sm-0">
                                <form action="{{url()->current()}}" method="GET">
                                    <!-- Search -->
                                    <div class="input-group input-group-merge input-group-flush">
                                        <div class="input-group-prepend">
                                            <div class="input-group-text">
                                                <i class="tio-search"></i>
                                            </div>
                                        </div>
                                        <input id="datatableSearch_" type="search" name="search" class="form-control"
                                               placeholder="{{\App\CPU\translate('search_by_name')}}" aria-label="Search" value="{{ '$search' }}"  required>
                                        <button type="submit" class="btn btn-primary">{{\App\CPU\translate('بحث')}} </button>

                                    </div>
                                    <!-- End Search -->
                                </form>
                            </div> --}}
                            <div class="col-12 col-sm-12">
                                <a href="{{route('admin.visitor.create')}}" class="btn btn-primary float-right"><i
                                        class="tio-add-circle"></i> {{\App\CPU\translate('اضافة زيارة')}}
                                </a>
                            </div>
                        </div>
                    </div>
                    <!-- End Header -->

                    <!-- Table -->
                    <div class="table-responsive datatable-custom">
                        <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                            <tr>
                                <th>{{\App\CPU\translate('#')}}</th>
                                <th>{{\App\CPU\translate('اسم الزائر')}}</th>
                                <th>{{\App\CPU\translate('العميل الذي سوف سيزور')}}</th>
                                <th>{{\App\CPU\translate('المنطقة')}}</th>
                                <th>{{ \App\CPU\translate('تاريخ الزيارة') }}</th>
                                <th>{{ \App\CPU\translate('ملاحظة') }}</th>
                                <th>{{\App\CPU\translate('action')}}</th>
                            </tr>
                            </thead>

                            <tbody id="set-rows">
                            @foreach($visitors as $key=>$visitor)
                                <tr>
                                    <td>{{ $key+1 }}</td>
                                  <td>
    {{ trim(optional($visitor->seller)->f_name . ' ' . optional($visitor->seller)->l_name) ?: '-' }}
</td>
<td>
    {{ optional($visitor->customer)->name ?? '-' }}/{{ optional($visitor->customer)->address ?? '-' }}/{{ optional($visitor->customer)->mobile ?? '-' }}
</td>

                                      <td>
                                        {{ $visitor->customer->regions->name ??''}}
                                    </td>
                                    <td>
                                        {{ $visitor->date }}
                                    </td>
                                    <td>
                                        {{ $visitor->note }}
                                    </td>
                                    <td>
                                        {{-- <a class="btn btn-white mr-1" href="{{route('admin.visitor.view',[$visitor['id']])}}"><span class="tio-visible"></span></a> --}}
                                        <!--<a class="btn btn-white mr-1"-->
                                        <!--    href="{{route('admin.visitor.edit',[$visitor['id']])}}">-->
                                        <!--    <span class="tio-edit"></span>-->
                                        <!--</a>-->
                                        <a class="btn btn-white mr-1" href="javascript:"
                                            onclick="form_alert('stock-{{$visitor['id']}}','Want to delete this stock?')"><span class="tio-delete"></span>
                                        </a>
                                        <form action="{{route('admin.visitor.delete',[$visitor['id']])}}"
                                                method="post" id="stock-{{$visitor['id']}}">
                                            @csrf @method('delete')
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
<div class="page-area">
    <table>
        <tfoot class="border-top">
            {!! $visitors->appends(request()->query())->links() !!}
        </tfoot>
    </table>
</div>

                        @if(count($visitors)==0)
                            <div class="text-center p-4">
                                <img class="mb-3 w-one-cl" src="{{asset('public/assets/admin')}}/svg/illustrations/sorry.svg" alt="{{\App\CPU\translate('Image Description')}}">
                                <p class="mb-0">{{ \App\CPU\translate('لاتوجد بيانات لعرضها')}}</p>
                            </div>
                        @endif
                    </div>
                    <!-- End Table -->
                </div>
                <!-- End Card -->
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script src={{asset("public/assets/admin/js/global.js")}}></script>
@endpush
