@extends('layouts.admin.app')

@section('title', \App\CPU\translate('add_new_expense'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin') }}/css/custom.css" />
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="row align-items-center mb-3">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title d-flex align-items-center g-2px text-capitalize"><i class="tio-add-circle-outlined"></i>
                    <span>{{ \App\CPU\translate('اضافة مصروف جديد') }}</span>
                </h1>
            </div>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('admin.account.store-expense') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="row pl-2">
                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ \App\CPU\translate('الحساب') }}</label>
                                        <select name="account_id" class="form-control js-select2-custom">
                                            <option value="">---{{ \App\CPU\translate('اختار الحساب') }}---</option>
                                            @foreach ($accounts as $account)
                                                    <option value="{{ $account['id'] }}">{{ $account['account'] }}
                                                    </option>
                                            @endforeach

                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label">{{ \App\CPU\translate('الوصف') }} </label>
                                        <input type="text" name="description" class="form-control"
                                            placeholder="{{ \App\CPU\translate('description') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="row pl-2">
                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label">{{ \App\CPU\translate('المبلغ') }}</label>
                                        <input type="number" step="0.01" min="1" name="amount"
                                            class="form-control" placeholder="{{ \App\CPU\translate('amount') }}"
                                            required>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ \App\CPU\translate('التاريخ') }} </label>
                                        <input type="date" name="date" class="form-control" required>
                                    </div>
                                </div>
                                    <div class="form-group">
        <label class="input-label" for="img">{{ \App\CPU\translate('تحميل صورة') }}</label>
        <input type="file" name="img" id="img" class="form-control" accept="image/*">
    </div>

                            </div>
                            <button type="submit" class="btn btn-primary">{{ \App\CPU\translate('حفظ') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="row align-items-center mb-2">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title d-flex align-items-center g-2px text-capitalize"><i class="tio-files"></i>
                    {{ \App\CPU\translate('قائمة المصروفات') }}
                    <span class="badge badge-soft-dark ml-2">{{ $expenses->total() }}</span>
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
                            <div class="col-12 col-md-6 col-lg-5 mb-3 mb-lg-0">
                                <form action="{{ url()->current() }}" method="GET">
                                    <!-- Search -->
                                    {{-- يحمل التاريخ حتى لا يُلغى المدى عند البحث --}}
                                    <input type="hidden" name="from" value="{{ $from }}">
                                    <input type="hidden" name="to" value="{{ $to }}">

                                    <div class="input-group input-group-merge input-group-flush">
                                        <div class="input-group-prepend">
                                            <div class="input-group-text">
                                                <i class="tio-search"></i>
                                            </div>
                                        </div>
                                        <input id="datatableSearch_" type="search" name="search" class="form-control"
                                            placeholder="{{ \App\CPU\translate('search_by_description') }}"
                                            value="{{ $search }}">
                                        <button type="submit" class="btn btn-primary">{{ \App\CPU\translate('البحث') }}
                                        </button>

                                    </div>
                                    <!-- End Search -->
                                </form>
                            </div>
                            <div class="col-12 col-lg-7">
                                <form action="{{ url()->current() }}" method="GET">
                                    {{-- يحمل نص البحث حتى لا يُلغى عند تغيير المدى --}}
                                    <input type="hidden" name="search" value="{{ $search }}">

                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="input-label"
                                                    for="exampleFormControlInput1">{{ \App\CPU\translate('من') }}
                                                </label>
                                                <input id="from_date" type="date" name="from" class="form-control"
                                                    value="{{ $from }}">
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="input-label"
                                                    for="exampleFormControlInput1">{{ \App\CPU\translate('الي') }}
                                                </label>
                                                <input id="to_date" type="date" name="to" class="form-control"
                                                    value="{{ $to }}">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <button href="" class="btn btn-success mt-4">
                                                {{ \App\CPU\translate('بحث') }}</button>
                                            {{-- التصدير يحمل فلاتر الشاشة الحالية --}}
                                            <x-export-button route="admin.account.export-expense" class="btn btn-info mt-4" />
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- End Header -->

                    <!-- Table -->
                    <div class="table-responsive datatable-custom">
                        <table
                            class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                         <tr class="table-light">
    <th class="text-center">{{ \App\CPU\translate('التاريخ') }} <i class="tio-calendar"></i></th>
    <th class="text-center">{{ \App\CPU\translate('الحساب') }} <i class="tio-wallet-outlined"></i></th>
        <th class="text-center">{{ \App\CPU\translate('الكاتب') }} <i class="tio-wallet-outlined"></i></th>

<th class="text-center">
    {{ \App\CPU\translate('نوع') }} <i class="tio-label"></i>
</th>
    <th class="text-center">{{ \App\CPU\translate('المبلغ') }} <i class="tio-money"></i></th>
    <th class="text-center">{{ \App\CPU\translate('الوصف') }} <i class="tio-document-text"></i></th>
    <th class="text-center">{{ \App\CPU\translate('الرصيد') }} <i class="tio-pie-chart"></i></th>
    <th class="text-center">{{ \App\CPU\translate('صورة') }} <i class="tio-pie-chart"></i></th>
    <th class="text-center">{{ \App\CPU\translate('إجراءات') }}</th>
</tr>

                            </thead>

                            <tbody>
                                @foreach ($expenses as $key => $expense)
                                    <tr>

                                        <td>{{ $expense->date }}</td>
                                        <td>
                                            {{ $expense->account ? $expense->account->account : '' }} <br>
                                        </td>
                                            <td>
                                            {{ $expense->seller->email ?? '' }} <br>
                                        </td>
                               <td>
    <span class="badge badge-danger ml-sm-3">
        @if ($expense->tran_type === 'Expense')
            {{ 'مصروف' }}
        @else
            {{ $expense->tran_type }}
        @endif
        <br>
    </span>
</td>

             
                                        <td>
                                            {{ $expense->amount . ' ' . \App\CPU\Helpers::currency_symbol() }}
                                        </td>
                                        <td>
                                            {{ Str::limit($expense->description, 30) }}
                                        </td>
                                     

                                        <td>
                                            {{ $expense->balance . ' ' . \App\CPU\Helpers::currency_symbol() }}
                                        </td>
                                            <td>
                                        <img class="navbar-brand-logo"
                         src="{{ asset('storage/shop/' . $expense->img) }}" alt="Logo">
                                    </td>

                                    {{-- تعديل المصروف أو حذفه، والحذف يرد المبلغ إلى الحساب --}}
                                    <td class="text-center">
                                        <a href="{{ route('admin.account.edit-expense', [$expense->id]) }}"
                                           class="btn btn-sm btn-white" title="تعديل">
                                            <i class="tio-edit"></i>
                                        </a>

                                        <button type="button" class="btn btn-sm btn-white text-danger"
                                                title="حذف"
                                                onclick="deleteExpense({{ $expense->id }})">
                                            <i class="tio-delete"></i>
                                        </button>

                                        <form id="expense-{{ $expense->id }}"
                                              action="{{ route('admin.account.delete-expense', [$expense->id]) }}"
                                              method="post" class="d-none">
                                            @csrf
                                            @method('delete')
                                        </form>
                                    </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="page-area">
                            <table>
                                <tfoot class="border-top">
                                    {!! $expenses->links() !!}
                                </tfoot>
                            </table>
                        </div>
                        @if (count($expenses) == 0)
                            <div class="text-center p-4">
                                <img class="mb-3 img-one-ex"
                                    src="{{ asset('public/assets/admin') }}/svg/illustrations/sorry.svg"
                                    alt="{{ \App\CPU\translate('Image Description') }}">
                                <p class="mb-0">{{ \App\CPU\translate('لاتوجد بيانات لعرضها') }}</p>
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
    <script>
        $('#from_date,#to_date').change(function() {
            let fr = $('#from_date').val();
            let to = $('#to_date').val();
            if (fr != '' && to != '') {
                if (fr > to) {
                    $('#from_date').val('');
                    $('#to_date').val('');
                    toastr.error('Invalid date range!', Error, {
                        CloseButton: true,
                        ProgressBar: true
                    });
                }
            }

        })
    </script>
@endpush

@push('script_2')
    <script>
        "use strict";

        // تأكيد قبل الحذف: العملية ترد المبلغ إلى الحساب ولا يمكن التراجع عنها.
        function deleteExpense(id) {
            if (confirm('هل تريد حذف هذا المصروف؟ سيُعاد المبلغ إلى الحساب.')) {
                document.getElementById('expense-' + id).submit();
            }
        }
    </script>
@endpush
