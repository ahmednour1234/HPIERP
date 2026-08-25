@extends('layouts.admin.app')

@section('title',\App\CPU\translate('supplier_list'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
<div class="row align-items-center mb-3">
    <div class="col-sm mb-2 mb-sm-0">
        <h1 class="page-header-title d-flex align-items-center g-2px text-capitalize"><i
                class="tio-filter-list"></i> {{ \App\CPU\translate('قائمة الموردين') }}
            <span class="badge badge-soft-dark ml-2">{{ $suppliers->total() }}</span>
        </h1>
<div class="row">
    <div class="col-12 col-md-7 mt-2">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <span class="font-one-stl badge badge-warning">{{ \App\CPU\translate('اجمالي حساب الموردين') }}</span>
                   <div class="row">

                    <div class="col-12 style-one-stl mt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="font-weight-bold">مدين:</span>
                            <span>
                                {{ $total_due_amount ? $total_due_amount . ' ' . \App\CPU\Helpers::currency_symbol() : '0 ' . \App\CPU\Helpers::currency_symbol() }} 
                            </span>
                        </div>
                    </div>
                    <div class="col-12 style-one-stl mt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="font-weight-bold">دائن:</span>
                            <span>
                                {{ $total_credit ? $total_credit . ' ' . \App\CPU\Helpers::currency_symbol() : '0 ' . \App\CPU\Helpers::currency_symbol() }} 
                            </span>
                        </div>
                    </div>
                    <div class="col-12 style-one-stl mt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="font-weight-bold">الرصيد النهائي:</span>
                            @php
                                $final_balance = $total_due_amount - $total_credit;
                            @endphp
                            <span>
                                {{ $final_balance >= 0 ? $final_balance . ' ' . \App\CPU\Helpers::currency_symbol() : '0 ' . \App\CPU\Helpers::currency_symbol() }} 
                                ({{ $final_balance > 0 ? 'مدين' : 'دائن' }})
                            </span>
            </div>
        </div>
    </div>
</div>

                </div>
            </div>
        </div>
    </div>
</div>
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
                            <div class="col-12 col-md-6 mb-3">
                                <form action="{{url()->current()}}" method="GET">
                                    <!-- Search -->
                                    <div class="input-group input-group-merge input-group-flush">
                                        <div class="input-group-prepend">
                                            <div class="input-group-text">
                                                <i class="tio-search"></i>
                                            </div>
                                        </div>
                                        <input id="datatableSearch_" type="search" name="search" class="form-control"
                                               placeholder="{{\App\CPU\translate('بحث برقم الهاتف او اسم المورد')}}" aria-label="Search" value="{{ $search }}"  required>
                                        <button type="submit" class="btn btn-primary">{{\App\CPU\translate('بحث')}} </button>

                                    </div>
                                    <!-- End Search -->
                                </form>
                            </div>
                            <div class="col-12 col-md-6">
                                <a href="{{route('admin.supplier.add')}}" class="btn btn-primary float-right"><i
                                        class="tio-add-circle"></i> {{\App\CPU\translate('اضافة مورد جديد')}}
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
                                <th>{{\App\CPU\translate('الاسم')}}</th>
                                <th class="hide-div-sl">{{\App\CPU\translate('الايميل')}}</th>
                                <th class="hide-div-sl"> {{ \App\CPU\translate('رقم الهاتف') }}</th>
                                <th>{{ \App\CPU\translate('المنتجات ') }}</th>
                                <th>{{ \App\CPU\translate('حالة التعامل ') }}</th>
                                <th class="text-center">{{ \App\CPU\translate('دائن') }}</th>
                                <th class="text-center">{{ \App\CPU\translate('مدين') }}</th>
                                <th>{{\App\CPU\translate('الاجراءات')}}</th>
                            </tr>
                            </thead>

                            <tbody id="set-rows">
                            @foreach($suppliers as $key=>$supplier)
                                <tr>
                                    <td>{{ $suppliers->firstItem()+$key }}</td>
                                    <td>
                                        <a class="text-primary" href="{{ route('admin.supplier.view',[$supplier['id']]) }}">
                                            {{ $supplier->name }}
                                        </a>
                                    </td>
                                    <td class="hide-div-sl">
                                        <a class="text-dark" href="mailto:{{ $supplier['email'] }}" class="text-primary">{{ $supplier['email'] }}</a>
                                    </td>
                                    <td class="hide-div-sl">
                                        <a href="tel:{{$supplier->mobile}}">{{$supplier->mobile}}</a>
                                    </td>
                                    <td>
                                        <a data-toggle="tooltip" class="badge badge-soft-info" href="{{ route('admin.supplier.products',[$supplier['id']]) }}"
                                            title="{{ \App\CPU\translate('product_view') }}">
                                            {{ $supplier->products->count() }}
                                        </a>
                                        <div class="tooltip bs-tooltip-top" role="tooltip">
                                            <div class="arrow"></div>
                                            <div class="tooltip-inner"></div>
                                        </div>
                                        
                                    </td>
                                                   <td>
                        <label class="toggle-switch toggle-switch-sm">
                            <input type="checkbox" class="toggle-switch-input" onclick="location.href='{{ route('admin.supplier.status', [$supplier['id'], $supplier->active ? 1 : 0]) }}'" {{ $supplier->active ? 'checked' : '' }}>
                            <span class="toggle-switch-label">
                                <span class="toggle-switch-indicator"></span>
                            </span>
                        </label>
                    </td>
                                          <td class="text-center p-5">
                        @if ($supplier->id != 0)
                            <div class="row">
                                <div class="col-5">
                                    {{ $supplier->due_amount . ' ' . \App\CPU\Helpers::currency_symbol() }}
                                </div>
                                <div class="col-5">
                                    <a class="btn btn-info p-1 badge" id="{{ $supplier->id }}" onclick="update_supplier_balance_cl({{ $supplier->id }})" data-toggle="modal" data-target="#update-customer-balance">
                                        <i class="tio-add-circle"></i>
                                        {{ \App\CPU\translate('استلام نقدية لهذا المورد') }}
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="row">
                                <div class="col-6">
                                    {{ \App\CPU\translate('هذا  المورد غير مدائن لنا بشئ') }}
                                </div>
                            </div>
                        @endif
                    </td>
                    <td class="text-center p-5">
                        @if ($supplier->id != 0)
                            <div class="row">
                                <div class="col-5">
                                    {{ $supplier->credit . ' ' . \App\CPU\Helpers::currency_symbol() }}
                                </div>
                                <div class="col-5">
                                    <a class="btn btn-info p-1 badge" id="{{ $supplier->id }}" onclick="update_supplier_credit_cl({{ $supplier->id }})" data-toggle="modal" data-target="#update-customer-credit">
                                        <i class="tio-add-circle"></i>
                                        {{ \App\CPU\translate('تحصيل نقدية من هذا  المورد') }}
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="row">
                                <div class="col-6">
                                    {{ \App\CPU\translate('هذا المورد غير مدين لنا بشئ') }}
                                </div>
                            </div>
                        @endif
                    </td>
                       

                                    <td>
                                        <a class="btn btn-white mr-1" href="{{route('admin.supplier.view',[$supplier['id']])}}"><span class="tio-visible"></span></a>
                                        <a class="btn btn-white mr-1"
                                            href="{{route('admin.supplier.edit',[$supplier['id']])}}">
                                            <span class="tio-edit"></span>
                                        </a>
                                        <a class="btn btn-white mr-1" href="javascript:"
                                            onclick="form_alert('supplier-{{$supplier['id']}}','Want to delete this supplier?')"><span class="tio-delete"></span></a>
                                            <form action="{{route('admin.supplier.delete',[$supplier['id']])}}"
                                                    method="post" id="supplier-{{$supplier['id']}}">
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
                                {!! $suppliers->links() !!}
                                </tfoot>
                            </table>
                        </div>
                        @if(count($suppliers)==0)
                            <div class="text-center p-4">
                                <img class="mb-3 img-one-sl" src="{{asset('public/assets/admin')}}/svg/illustrations/sorry.svg" alt="{{\App\CPU\translate('Image Description')}}">
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
<div class="modal fade" id="update-customer-balance" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ \App\CPU\translate('اضافة استلام نقدية') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ route('admin.supplier.update-balance') }}" method="post" class="row" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="supplier_id" name="supplier_id">
 <div class="form-group">
        <label class="input-label" for="img">{{ \App\CPU\translate('تحميل صورة') }}</label>
        <input type="file" name="img" id="img" class="form-control" required>
                   </div>
                    <div class="form-group col-12 col-sm-6">
                        <label>{{ \App\CPU\translate('استلام نقدية') }}</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="amount" required>
                    </div>

                    <div class="form-group col-12 col-sm-6">
                        <label>{{ \App\CPU\translate('الحساب الذي ستضاف له سيتم دفع منه المديونية') }}</label>
                        <select id="account_id" name="account_id" class="form-control js-select2-custom" required>
                            <option value="">---{{ \App\CPU\translate('اختار') }}---</option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account['id'] }}" data-balance="{{ $account['balance'] }}">{{ $account['account'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-12 col-sm-6">
                        <label>{{ \App\CPU\translate('وصف') }}</label>
                        <input type="text" name="description" class="form-control" placeholder="{{ \App\CPU\translate('description') }}">
                    </div>

                    <div class="form-group col-12 col-sm-6">
                        <label>{{ \App\CPU\translate('التاريخ') }}</label>
                        <input type="date" name="date" class="form-control" required>
                    </div>

                    <div class="form-group col-12 col-sm-6">
                        <label>{{ \App\CPU\translate('رصيد الحساب') }}</label>
                        <p id="account_balance">0</p>
                    </div>

                    <div class="form-group col-sm-12">
                        <button class="btn btn-sm btn-primary" type="submit">{{ \App\CPU\translate('حفظ') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="update-customer-credit" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ \App\CPU\translate('اضافة دفع نقدية') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ route('admin.supplier.update-credit') }}" method="post" class="row" enctype="multipart/form-data">
                    @csrf
                     <div class="form-group">
        <label class="input-label" for="img">{{ \App\CPU\translate('تحميل صورة') }}</label>
        <input type="file" name="img" id="img" class="form-control" required>
                   </div>

                    <div class="form-group col-12 col-sm-6">
                        <label>{{ \App\CPU\translate('دفع نقدية') }}</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="amount" required>
                    </div>
                    
<input type="hidden" id="supplier_credit_id" name="supplier_id">

                    <div class="form-group col-12 col-sm-6">
                        <label>{{ \App\CPU\translate('الحساب الذي ستضاف له سيتم دفع اليه المبلغ ') }}</label>
                        <select id="account_id" name="account_id" class="form-control js-select2-custom" required>
                            <option value="">---{{ \App\CPU\translate('اختار') }}---</option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account['id'] }}" data-balance="{{ $account['balance'] }}">{{ $account['account'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-12 col-sm-6">
                        <label>{{ \App\CPU\translate('وصف') }}</label>
                        <input type="text" name="description" class="form-control" placeholder="{{ \App\CPU\translate('description') }}">
                    </div>

                    <div class="form-group col-12 col-sm-6">
                        <label>{{ \App\CPU\translate('التاريخ') }}</label>
                        <input type="date" name="date" class="form-control" required>
                    </div>

                    <div class="form-group col-12 col-sm-6">
                        <label>{{ \App\CPU\translate('رصيد الحساب') }}</label>
                        <p id="account_balance">0</p>
                    </div>

                    <div class="form-group col-sm-12">
                        <button class="btn btn-sm btn-primary" type="submit">{{ \App\CPU\translate('حفظ') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>     
    function update_supplier_balance_cl(customerId) {
    document.getElementById('supplier_id').value = customerId; // For balance modal
}

function update_supplier_credit_cl(customerId) {
    document.getElementById('supplier_credit_id').value = customerId; // For credit modal
}

    
    document.addEventListener('DOMContentLoaded', function () {
    const accountSelect = document.getElementById('account_id');
    const balanceDisplay = document.getElementById('account_balance');

    accountSelect.addEventListener('change', function () {
        const selectedOption = accountSelect.options[accountSelect.selectedIndex];
        const balance = selectedOption.getAttribute('data-balance');
        balanceDisplay.textContent = balance ? balance : '0';
    });

    // Initialize the balance display for the default selected option
    if (accountSelect.value) {
        const selectedOption = accountSelect.options[accountSelect.selectedIndex];
        const balance = selectedOption.getAttribute('data-balance');
        balanceDisplay.textContent = balance ? balance : '0';
    }
});

</script>
@endpush
