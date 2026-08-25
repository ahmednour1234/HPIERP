@extends('layouts.admin.app')

@section('title',\App\CPU\translate('supplier_details'))

@push('css_or_js')

@endpush

@section('content')

<div class="content container-fluid">
    <div class="page-header">
        <div>
            <h1 class="page-header-title">{{ $supplier->name }}</h1>
        </div>
        <div class="js-nav-scroller hs-nav-scroller-horizontal">
            <ul class="nav nav-tabs page-header-tabs">
                <li class="nav-item">
                    <a class="nav-link active" href="{{ route('admin.supplier.view',[$supplier['id']]) }}">{{\App\CPU\translate('حجم  تعامل المورد')}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('admin.supplier.products',[$supplier['id']]) }}">{{\App\CPU\translate('قائمة المنتجات')}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('admin.supplier.transaction-list',[$supplier['id']]) }}">{{\App\CPU\translate('كشاف حساب المورد')}}</a>
                </li>
            </ul>

        </div>
    </div>

<!-- End Page Header -->
        <div class="row" id="">
            <div class="col-lg-8 mb-3 mb-lg-0">
                <div class="card">
                    <div class="card-header">
                        <div class="row justify-content-between align-items-center flex-grow-1">
                            <div class="col-12 col-sm-4 col-md-4 col-lg-4">
                                <h3>{{\App\CPU\translate('اجمالي الفواتير')}}
                                    <span class="badge badge-soft-dark ml-2">{{$orders->total()}}</span>
                                </h3>
                            </div>
                            <div class="col-12 col-sm-8 col-md-4 col-lg-6">
                            <form action="{{ url()->current() }}" method="GET">
    <!-- Search -->
    <div class="input-group input-group-merge input-group-flush mb-3">
        <div class="input-group-prepend">
            <div class="input-group-text">
                <i class="tio-search"></i>
            </div>
        </div>
        <input id="datatableSearch_" type="search" name="search" class="form-control"
            placeholder="{{ \App\CPU\translate('بحث برقم الفاتورة') }}" aria-label="Search" value="{{ $search }}">
        <button type="submit" class="btn btn-primary">{{ \App\CPU\translate('بحث') }}</button>
    </div>
    <!-- End Search -->

    <!-- Order Type Selection -->
    <!--<div class="form-group mb-3">-->
    <!--    <label for="order_type">{{ \App\CPU\translate('نوع الطلب') }}</label>-->
    <!--    <select name="order_type" class="form-control" id="order_type">-->
    <!--        <option value="">{{ \App\CPU\translate('اختر نوع الطلب') }}</option>-->
    <!--        <option value="4" {{ request('order_type') == '4' ? 'selected' : '' }}>{{ \App\CPU\translate('فاتورة مبيعات') }}</option>-->
    <!--        <option value="7" {{ request('order_type') == '7' ? 'selected' : '' }}>{{ \App\CPU\translate('مردود مبيعات') }}</option>-->
    <!--        <option value="14" {{ request('order_type') == '14' ? 'selected' : '' }}>{{ \App\CPU\translate('مردود مشتريات') }}</option>-->
    <!--        <option value="8" {{ request('order_type') == '8' ? 'selected' : '' }}>{{ \App\CPU\translate('مردود مبيعات') }}</option>-->
    <!--    </select>-->
    <!--</div>-->

    <!-- Date Range -->
    <div class="form-row mb-3">
        <div class="col">
            <label for="start_date">{{ \App\CPU\translate('من تاريخ') }}</label>
            <input type="date" name="start_date" class="form-control" id="start_date" value="{{ request('start_date') }}">
        </div>
        <div class="col">
            <label for="end_date">{{ \App\CPU\translate('الى تاريخ') }}</label>
            <input type="date" name="end_date" class="form-control" id="end_date" value="{{ request('end_date') }}">
        </div>
    </div>
</form>

                            </div>
                        </div>
                    </div>
                    <!-- Table -->
<div class="table-responsive datatable-custom">
    <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
        <thead class="thead-light">
            <tr>
                <th>{{ \App\CPU\translate('#') }}</th>
                <th class="text-center">{{ \App\CPU\translate('الفاتورة رقم') }}</th>
                <th>{{ \App\CPU\translate('الاجمالي') }}</th>
                <th>{{ \App\CPU\translate('نوع الفاتورة') }}</th>
                                <th>{{ \App\CPU\translate('الحالة') }}</th>
              <th>{{ \App\CPU\translate('التاريخ') }}</th>
                <th>{{ \App\CPU\translate('رؤوية') }}</th>
            </tr>
        </thead>

        <tbody>
        @foreach($orders as $key => $order)
            <tr>
                <td>{{ $orders->firstItem() + $key }}</td>
                <td class="table-column-pl-0 text-center">
                    <a href="#">{{ $order['id'] }}</a>
                </td>
                <td>{{ $order['sub_total'] -$order['total_discount']+$order['tax_amount']. " " . \App\CPU\Helpers::currency_symbol() }}</td>
                <td>
                    @php
                        $invoiceType = '';
                        switch ($order['type']) {
                            case 4:
                                $invoiceType = 'فاتورة مبيعات';
                                break;
                            case 7:
                                $invoiceType = 'مردود مبيعات';
                                break;
                            case 14:
                                $invoiceType = 'مردود مشتريات';
                                break;
                            case 8:
                                $invoiceType = 'مردود مبيعات';
                                break;
                            default:
                                $invoiceType = 'مشتريات'; // Default case
                        }
                    @endphp
                    {{ $invoiceType }}
                </td>
               <td class="table-column-pl-0 text-center">
    @switch($order['status'])
        @case('draft')
            <span class="badge bg-secondary">مسودة</span>
            @break

        @case('executed')
            <span class="badge bg-success">منفذة</span>
            @break

        @default
            <span class="badge bg-light text-dark">{{ $order['status'] }}</span>
    @endswitch
</td>

                    <td class="table-column-pl-0 text-center">
               {{ $order['created_at'] }}
                </td>
                <td>
                    <button class="btn btn-sm btn-white" type="button"
                        onclick="print_invoice('{{ $order->id }}')">
                        <i class="tio-download"></i> {{ \App\CPU\translate('روؤية') }}
                    </button>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <!-- Footer -->
    <div class="card-footer">
        <!-- Pagination -->
        {!! $orders->links() !!}
        <!-- End Pagination -->
    </div>

    @if(count($orders) == 0)
        <div class="text-center p-4">
            <img class="mb-3 w-one-carsi" src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}" alt="Image Description">
            <p class="mb-0">{{ \App\CPU\translate('لاتوجد بيانات لعرضها') }}</p>
        </div>
    @endif
    <!-- End Footer -->
</div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Card -->
                <div class="card">
                    <!-- Header -->
                    <div class="card-header">
                        <h4 class="card-header-title">{{\App\CPU\translate('العميل')}}</h4>
                    </div>
                    <!-- End Header -->

                    <!-- Body -->
                             @if($supplier)
<div class="card-body">
    <div class="media align-items-center" href="javascript:">
        <div class="avatar avatar-circle mr-3">
            <img
                class="avatar-img"
                onerror="this.src='{{asset('public/assets/admin/img/160x160/img1.jpg')}}'"
                src="{{asset('storage/app/public/supplier/'.$supplier->image)}}"
                alt="{{\App\CPU\translate('image_description')}}">
        </div>
        <div class="media-body">
            <span class="text-body text-hover-primary">{{$supplier['name']}}</span>
        </div>
    </div>

    <hr>

    <div class="media align-items-center" href="javascript:">
        <div class="icon icon-soft-info icon-circle mr-3">
            <i class="tio-shopping-basket-outlined"></i>
        </div>
        <div class="media-body">

        </div>
    </div>
    
<div class="media align-items-center mt-1" href="javascript:">
    <div class="icon icon-soft-info icon-circle mr-3">
        <i class="tio-money"></i>
    </div>
<div class="media-body">
    @php
        // Calculate total balance and credit for the customer
        $totalBalance = $supplier->due_amount; // Assuming balance is a single value
        $totalCredit = $supplier->credit; // Assuming credit is a single value
        $netAmount = $totalBalance - $totalCredit;
    @endphp

    <!-- Display total balance with creditor/debtor status -->
    <span class="text-body text-hover-primary">
        {{ number_format(abs($totalBalance), 2) }}
        {{ $totalBalance >= 0 ? \App\CPU\translate('دائن') : \App\CPU\translate('مدين') }}
    </span>

    <span class="text-body text-hover-primary"> - </span>

    <!-- Display total credit with creditor/debtor status -->
    <span class="text-body text-hover-primary">
        {{ number_format(abs($totalCredit), 2) }}
        {{ $totalCredit >= 0 ? \App\CPU\translate('مدين') : \App\CPU\translate('دائن') }}
    </span>

    <span class="text-body text-hover-primary"> = </span>

    <!-- Display net amount with net debtor/creditor status -->
    <span class="text-body text-hover-primary">
        {{ number_format(abs($netAmount), 2) }}
        {{ $netAmount >= 0 ? \App\CPU\translate('صافي دائن') : \App\CPU\translate('صافي مدين') }}
    </span>
</div>

</div>

    @if($supplier->id != 0)
        <hr>

        <div class="d-flex justify-content-between align-items-center">
            <h5>{{\App\CPU\translate('معلومات التواصل')}}</h5>
        </div>

        <ul class="list-unstyled list-unstyled-py-2">
            <li>
                <i class="tio-android-phone-vs mr-2"></i>
                {{$supplier['mobile']}}
            </li>
            @if ($supplier['email'])
                <li>
                    <i class="tio-online mr-2"></i>
                    {{$supplier['email']}}
                </li>
            @endif
        </ul>

        <hr>

        <div class="d-flex justify-content-between align-items-center">
            <h5>{{\App\CPU\translate('العنوان')}}</h5>
        </div>
        <ul class="list-unstyled list-unstyled-py-2">
            <li>{{\App\CPU\translate('المقاطعة')}}: {{$supplier['state']}}</li>
            <li>{{\App\CPU\translate('المدينة')}}: {{$supplier['city']}}</li>
            <li>{{\App\CPU\translate('كود المدينة')}}: {{$supplier['zip_code']}}</li>
            <li>{{\App\CPU\translate('العنوان')}}: {{$supplier['address']}}</li>
        </ul>


        </div>
    @endif
    @endif
                <!-- End Body -->
                </div>
                <!-- End Card -->
            </div>
        </div>

        <!-- End Row -->
    </div>
<div class="modal fade" id="print-invoice" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{\App\CPU\translate('طباعة')}} {{\App\CPU\translate('الفاتورة')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body row font-one-cv">
                    <div class="col-md-12">
                        <center>
                            <input type="button" class="btn btn-primary non-printable" onclick="printDiv('printableArea')"
                                value="{{\App\CPU\translate('Proceed, If thermal printer is ready')}}."/>
                            <a href="{{url()->previous()}}" class="btn btn-danger non-printable">{{\App\CPU\translate('عودة')}}</a>
                        </center>
                        <hr class="non-printable">
                    </div>
                    <div class="row m-auto" id="printableArea">

                    </div>

                </div>
            </div>
        </div>
    </div>
    </div>
@endsection
@push('script_2')
    <script>
        "use strict";
        function print_invoice(order_id) {
            $.get({
                url: '{{url('/')}}/admin/pos/generate_invoice_purchase/'+order_id,
                dataType: 'json',
                beforeSend: function () {
                    $('#loading').show();
                },
                success: function (data) {
                    console.log("success...")
                    $('#print-invoice').modal('show');
                    $('#printableArea').empty().html(data.view);
                },
                complete: function () {
                    $('#loading').hide();
                },
            });
        }
    </script>

    <script src={{asset("public/assets/admin/js/global.js")}}></script>
@endpush