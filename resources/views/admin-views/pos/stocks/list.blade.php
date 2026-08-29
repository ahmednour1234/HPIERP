@extends('layouts.admin.app')
@section('title','Stock History')
@push('css_or_js')
    {{-- <meta name="csrf-token" content="{{ csrf_token() }}"> --}}
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/custom.css"/>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="">
            <div class="row align-items-center mb-3">
                <div class="col-sm">
                    <h1 class="page-header-title text-capitalize">{{\App\CPU\translate('pos')}} {{\App\CPU\translate('stocks')}}
                        <span
                            class="badge badge-soft-dark ml-2">{{$orders->total()}}</span></h1>
                </div>
            </div>
        </div>
        <!-- End Page Header -->
        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header">
                <div class="row justify-content-between align-items-center flex-grow-1">
                    <div class="col-sm-8 col-md-6 col-lg-6 mb-3 mb-lg-0">
                       <form action="{{ url()->current() }}" method="GET">
                            <!-- Search by Order ID -->
                            <div class="input-group input-group-merge input-group-flush">
                                <div class="input-group-prepend">
                                    <div class="input-group-text">
                                        <i class="tio-search"></i>
                                    </div>
                                </div>
                                <input type="search" name="search" class="form-control" placeholder="{{ \App\CPU\translate('search_by_customer_name_or_seller_name') }}" aria-label="Search" value="{{ $search }}" >
                                <button type="submit" class="btn btn-primary">{{ \App\CPU\translate('search') }}</button>

                    {{-- التصدير يحمل فلاتر الشاشة الحالية --}}
                    <x-export-button route="admin.pos.stocks.export" />
                            </div>
                            <!-- End Search by Order ID -->
                        
                            <!-- Search by Date Range -->
                            <div class="input-group mt-3">
                                <input type="date" name="from_date" class="form-control" placeholder="From Date" value="{{ $fromDate }}" aria-label="From Date">
                                <div class="input-group-append">
                                    <span class="input-group-text">-</span>
                                </div>
                                <input type="date" name="to_date" class="form-control" placeholder="To Date" value="{{ $toDate }}" aria-label="To Date">
                            </div>
                            <!-- End Search by Date Range -->
                        </form>

                    </div>

                    <div class="col-lg-6"></div>
                </div>
                <!-- End Row -->
            </div>
            <!-- End Header -->
            
            <!-- Table -->
            <div class="table-responsive ">
                <table
                    class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                >
                    <thead class="thead-light">
                    <tr>
                        <th class="">
                            {{\App\CPU\translate('#')}}
                        </th>
                        <th class="table-column-pl-0">{{\App\CPU\translate('stock')}}</th>
                        <th>{{\App\CPU\translate('seller_name')}}</th>
                        <th>{{\App\CPU\translate('vehicle_code')}}</th>
                        <th>{{\App\CPU\translate('product_count')}}</th>
                        <th>{{\App\CPU\translate('total_stock')}}</th>
                        <th>{{\App\CPU\translate('remain_stock')}}</th>
                        <th>{{\App\CPU\translate('total_cash')}}</th>
                        <th>{{\App\CPU\translate('total_credit')}}</th>
                        <th>{{\App\CPU\translate('order_count')}}</th>
                        <th>{{\App\CPU\translate('refund_total')}}</th>
                        <th>{{\App\CPU\translate('installment_total')}}</th>
                        <th>{{\App\CPU\translate('date')}}</th>
                        <th>{{\App\CPU\translate('actions')}}</th>
                    </tr>
                    </thead>

                    <tbody id="set-rows">
                    @foreach($orders as $key=>$order)
                        <tr class="status-{{$order['order_status']}} class-all">
                            <td class="">
                                {{$key+$orders->firstItem()}}
                            </td>
                            <td class="table-column-pl-0">
                                <a class="text-primary" href="#" onclick="print_invoice('{{$order->id}}')">{{$order['id']}}</a>
                            </td>
                            <td>
                                  {{ optional($order->seller)->f_name }} {{ optional($order->seller)->l_name }}
                            </td>
                            <td>
                                {{ optional(\App\Models\Store::where('store_id', $order->seller->vehicle_code??'')->first())->store_code ??'' }}
                            </td>
                            <td>
                                {{ $order->statistcs->product_count }}
                            </td>
                            <td>
                                {{ $order->statistcs->total_stock }}
                            </td>
                            <td>
                                {{ $order->statistcs->remain_stock }}
                            </td>
                            <td>
                                {{ number_format($order->statistcs->total_cash, 2) }}
                            </td>
                            <td>
                                {{ number_format($order->statistcs->total_credit, 2) }}
                            </td>
                            <td>
                                {{ $order->statistcs->order_count }}
                            </td>
                               <td>
                                {{ number_format($order->statistcs->refund_total, 2) }}
                            </td>
                               <td>
                                {{ number_format($order->statistcs->installment_total, 2) }}
                            </td>
                                 <td>{{date('d M Y',strtotime($order['created_at']))}}</td>
                            <td>
                                <button class="btn btn-sm btn-white" target="_blank" type="button"
                                        onclick="print_invoice('{{$order->id}}')"><i
                                        class="tio-download"></i> {{\App\CPU\translate('invoice')}}</button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <!-- End Table -->

            <!-- Footer -->
            <div class="card-footer">
                <!-- Pagination -->
                <div class="row justify-content-center justify-content-sm-between align-items-sm-center">
                    <div class="col-sm-auto">
                        <div class="d-flex justify-content-center justify-content-sm-end">
                            <!-- Pagination -->
                            {!! $orders->links() !!}
                        </div>
                    </div>
                </div>
                <!-- End Pagination -->
            </div>
            @if(count($orders)==0)
                <div class="text-center p-4">
                    <img class="mb-3 img-one-ol" src="{{asset('public/assets/admin')}}/svg/illustrations/sorry.svg"
                         alt="Image Description">
                    <p class="mb-0">{{ \App\CPU\translate('No_data_to_show')}}</p>
                </div>
        @endif
        <!-- End Footer -->
        </div>
        <!-- End Card -->
    </div>

    <div class="modal fade" id="print-invoice" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content modal-content1">
                <div class="modal-header">
                    <h5 class="modal-title">{{\App\CPU\translate('print')}} {{\App\CPU\translate('invoice')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span class="text-dark" aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body row">
                    <div class="col-md-12">
                        <center>
                            <input type="button" class="mt-2 btn btn-primary non-printable"
                                   onclick="printDiv('printableArea')"
                                   value="{{\App\CPU\translate('Proceed, If thermal printer is ready')}}."/>
                            <a href="{{url()->previous()}}"
                               class="mt-2 btn btn-danger non-printable">{{\App\CPU\translate('Back')}}</a>
                        </center>
                        <hr class="non-printable">
                    </div>
                    <div class="row m-auto" id="printableArea">

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
                url: '{{url('/')}}/admin/pos/stocks/invoice/' + order_id,
                dataType: 'json',
                beforeSend: function () {
                    $('#loading').show();
                },
                success: function (data) {
                    console.log(data)
                    //console.log("success...")
                    $('#print-invoice').modal('show');
                    $('#printableArea').empty().html(data.view);
                },
                error: function (error) {
                    console.log(error)
                },
                complete: function () {
                    $('#loading').hide();
                },
            });
        }
    </script>

    <script src={{asset("public/assets/admin/js/global.js")}}></script>
@endpush
