@extends('layouts.admin.app')

@section('title', \App\CPU\translate('seller_list'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/custom.css') }}">
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="row align-items-center mb-3">
        <div class="col-sm mb-2 mb-sm-0">
            <h1 class="page-header-title d-flex align-items-center g-2px text-capitalize">
                <i class="tio-filter-list"></i> 
                @if($type == 0)
                    {{ \App\CPU\translate('قائمة ملاحظات التطوير') }}
                @elseif($type == 1)
                    {{ \App\CPU\translate('قائمة الكورسات التطوير') }}
                @else
                    {{ \App\CPU\translate('طلبات اجازات') }}
                @endif
                <span class="badge badge-soft-dark ml-2">{{ $developSellers->total() }}</span>
            </h1>
        </div>
    </div>
    <!-- End Page Header -->

    <div class="row gx-2 gx-lg-3">
        <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
            <!-- Card -->
            <div class="card">
                <!-- Header -->
                <div class="card-header">
                    <div class="row justify-content-between align-items-center flex-grow-1">
                        <div class="col-12 col-sm-7 col-md-6 col-lg-4 col-xl-6 mb-3 mb-sm-0">
                            <!-- Search Form -->
                            <form action="{{ url()->current() }}" method="GET">
                                <div class="input-group input-group-merge input-group-flush">
                                    <div class="input-group-prepend">
                                        <div class="input-group-text">
                                            <i class="tio-search"></i>
                                        </div>
                                    </div>
                                    <input 
                                        id="datatableSearch_" 
                                        type="search" 
                                        name="search" 
                                        class="form-control"
                                        placeholder="{{ \App\CPU\translate('بحث بالاسم او الايميل') }}" 
                                        aria-label="Search"
                                        value="{{ request('search') }}" 
                                        required>
                                    <button type="submit" class="btn btn-primary">
                                        {{ \App\CPU\translate('بحث') }}
                                    </button>
                                </div>
                            </form>
                            <!-- End Search Form -->
                        </div>

                        @if($type == 0)
                        <div class="col-12 col-sm-5">
                            <a href="{{ route('admin.developsellers.create', ['type' => 0]) }}" class="btn btn-primary float-right">
                                <i class="tio-add-circle"></i> {{ \App\CPU\translate('اضافة ملاحظة للتطوير') }}
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
                <!-- End Header -->

                <!-- Table -->
                <div class="table-responsive datatable-custom">
                    <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>{{ \App\CPU\translate('الاسم') }}</th>
                                <th>{{ \App\CPU\translate('الايميل') }}</th>
                                <th>{{ \App\CPU\translate('المدير') }}</th>
                                <th>
                                    @if($type == 0)
                                        {{ \App\CPU\translate('الملاحظة التطوير') }}
                                    @elseif($type == 1)
                                        {{ \App\CPU\translate('طلب موظف') }}
                                    @else
                                        {{ \App\CPU\translate('طلب إجازة') }}
                                    @endif
                                </th>
                                @if($type == 2)
                                    <th>{{ \App\CPU\translate('تاريخ الاجازة') }}</th>
                                    <th>{{ \App\CPU\translate('موافقة الاجازة') }}</th>
                                @endif
                                <th>{{ \App\CPU\translate('action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($developSellers as $key => $seller)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                               <td>{{ trim(optional($seller->sellers)->f_name . ' ' . optional($seller->sellers)->l_name) ?: '-' }}</td>
<td>{{ optional($seller->sellers)->email ?? '-' }}</td>
<td>{{ optional($seller->admins)->email ?? '-' }}</td>

                                <td>{{ $seller->note }}</td>
                                @if($type == 2)
                                    <td>{{ $seller->date }}</td>
<td>
    @if($seller->active == 0)
        <form action="{{ route('admin.developsellers.status', $seller->id) }}" method="POST">
            @csrf
            @method('PUT') <!-- Use PUT instead of PATCH -->
            <button type="submit" class="btn btn-primary btn-sm">
                {{ \App\CPU\translate('موافقة علي اجازة') }}
            </button>
        </form>
    @elseif($seller->active == 1)
        <span class="text-success">{{ \App\CPU\translate('لقد تمت الموافقة عل اجازة') }}</span>
    @endif
</td>

                                @endif
                                <td>
                                    <!-- Actions -->
                                    <a class="btn btn-white mr-1" href="{{ route('admin.developsellers.edit', $seller->id) }}">
                                        <span class="tio-edit"></span>
                                    </a>
                                    <a 
                                        class="btn btn-white mr-1" 
                                        href="javascript:" 
                                        onclick="form_alert('seller-{{ $seller->id }}', '{{ \App\CPU\translate('Want to delete this seller?') }}')">
                                        <span class="tio-delete"></span>
                                    </a>
                                    <form 
                                        action="{{ route('admin.developsellers.destroy', $seller->id) }}" 
                                        method="post" 
                                        id="seller-{{ $seller->id }}">
                                        @csrf
                                        @method('delete')
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div class="page-area">
                        <table>
                            <tfoot class="border-top">
                                {!! $developSellers->links() !!}
                            </tfoot>
                        </table>
                    </div>
                    <!-- No Data Found -->
                    @if($developSellers->isEmpty())
                        <div class="text-center p-4">
                            <img 
                                class="mb-3 w-one-cl" 
                                src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}" 
                                alt="{{ \App\CPU\translate('Image Description') }}">
                            <p class="mb-0">{{ \App\CPU\translate('No_data_to_show') }}</p>
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
    <script src="{{ asset('public/assets/admin/js/global.js') }}"></script>
@endpush
