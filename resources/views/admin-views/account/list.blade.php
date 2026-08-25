@extends('layouts.admin.app')

@section('title',\App\CPU\translate('account_list'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="mb-3">
            <h1 class="page-header-title d-flex align-items-center g-2px text-capitalize">
                <i class="tio-filter-list"></i>
                <span>{{\App\CPU\translate('قائمة الحسابات')}} <span
                        class="badge badge-soft-dark ml-2">{{$accounts->total()}}</span></span>
            </h1>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <!-- Card -->
                <div class="card">
                    <!-- Header -->
                    <div class="card-header">
                        <div class="row justify-content-between align-items-center flex-grow-1">
                            <div class="col-10 mb-1 mb-md-0 col-sm-7 col-md-6">
                                <form action="{{url()->current()}}" method="GET">
                                    <!-- Search -->
                                    <div class="input-group input-group-merge input-group-flush">
                                        <div class="input-group-prepend">
                                            <div class="input-group-text">
                                                <i class="tio-search"></i>
                                            </div>
                                        </div>
                                        <input id="datatableSearch_" type="search" name="search" class="form-control"
                                               placeholder="{{\App\CPU\translate('search_by_account_title')}}"
                                               value="{{ $search }}" required>
                                        <button type="submit"
                                                class="btn btn-primary">{{\App\CPU\translate('يحث')}} </button>

                                    </div>
                                    <!-- End Search -->
                                </form>
                            </div>
                            <div class="col-12 col-sm-5  col-md-4">
                                <a href="{{route('admin.account.add')}}" class="btn btn-primary float-right"><i
                                        class="tio-add-circle"></i> {{\App\CPU\translate('اضافة حساب جديد')}}
                                </a>
                            </div>
                        </div>
                    </div>
                    <!-- End Header -->

                    <!-- Table -->
                    <div class="table-responsive datatable-custom">
<table class="table table-hover table-striped table-bordered table-nowrap table-align-middle card-table">
    <thead class="thead-light">
        <tr>
            <th>{{ \App\CPU\translate('#') }}</th>
            <th>{{ \App\CPU\translate('معلومات الحساب') }}</th>
            <th>{{ \App\CPU\translate('معلومات الميزانية') }}</th>
            <th class="w-fp-acc text-center">{{ \App\CPU\translate('اجراءات') }}</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($accounts as $key => $account)
        <tr>
            <td class="align-middle">{{ $accounts->firstItem() + $key }}</td>
            <td class="align-middle">
                <strong>{{ $account->account }}</strong> <br>
                <span class="text-muted">{{ $account->account_number }}</span> <br>
                <span class="text-muted">{{ $account->description }}</span>
            </td>

            <td class="align-middle">
                <div class="d-flex flex-column">
                    <span>{{ \App\CPU\translate('الاجمالي') }}: <strong>{{ $account->balance . ' ' . \App\CPU\Helpers::currency_symbol() }}</strong></span>
                    <span>{{ \App\CPU\translate('الدخل الحساب') }}: <strong>{{ $account->total_in ? $account->total_in . ' ' . \App\CPU\Helpers::currency_symbol() : 0 . ' ' . \App\CPU\Helpers::currency_symbol() }}</strong></span>
                    <span>{{ \App\CPU\translate('الخارج من الحساب') }}: <strong>{{ $account->total_out ? $account->total_out . ' ' . \App\CPU\Helpers::currency_symbol() : 0 . ' ' . \App\CPU\Helpers::currency_symbol() }}</strong></span>
                </div>
            </td>

            <td class="align-middle text-center">
                @if ($account->id != 1 && $account->id != 2 && $account->id != 3)
                <div class="d-flex justify-content-center">
                    <a class="btn btn-sm btn-outline-primary mr-1" href="{{ route('admin.account.edit', [$account['id']]) }}">
                        <i class="tio-edit"></i> {{ \App\CPU\translate('تعديل') }}
                    </a>
                    <a class="btn btn-sm btn-outline-danger mr-1" href="javascript:" onclick="form_alert('account-{{ $account['id'] }}','{{ \App\CPU\translate('هل تريد حذف هذا الحساب؟') }}')">
                        <i class="tio-delete"></i> {{ \App\CPU\translate('حذف') }}
                    </a>
                </div>
                <form action="{{ route('admin.account.delete', [$account['id']]) }}" method="post" id="account-{{ $account['id'] }}">
                    @csrf
                    @method('delete')
                </form>
                @else
                <span class="text-muted">{{ \App\CPU\translate('لا يمكن التعديل عليهم') }}</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

                        <div class="page-area">
                            <table>
                                <tfoot class="border-top">
                                {!! $accounts->links() !!}
                                </tfoot>
                            </table>
                        </div>
                        @if(count($accounts)==0)
                            @include('layouts.admin.partials._no-data-section')
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

@endpush
