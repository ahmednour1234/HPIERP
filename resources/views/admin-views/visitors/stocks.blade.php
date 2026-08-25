@extends('layouts.admin.app')

@section('title',\App\CPU\translate('product_list'))

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
                        class="tio-filter-list"></i> {{\App\CPU\translate('Sale Products And Remain Stock')}}
                    <span class="badge badge-soft-dark ml-2">{{$stocks->count()}}</span>
                </h1>
            </div>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <!-- Card -->
                    <!-- Table -->
                    <div class="table-responsive datatable-custom">
                        <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                            <tr>
                                <th>{{\App\CPU\translate('#')}}</th>
                                <th>{{\App\CPU\translate('product')}}</th>
                                <th>{{\App\CPU\translate('sale_stock')}}</th>
                                <th>{{\App\CPU\translate('remain_stock')}}</th>
                            </tr>
                            </thead>

                            <tbody id="set-rows">
                            @foreach($stocks->get() as $key => $stock)
                                <tr>
                                    <td>{{ $key+1 }}</td>
                                    <td>
                                        {{ $stock->product->name }}
                                    </td>
                                    <td>
                                        {{ $stock->main_stock - $stock->stock }}
                                    </td>
                                    <td>
                                        {{ $stock->stock }}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        @if($stocks->count() == 0)
                            <div class="text-center p-4">
                                <img class="mb-3 w-one-cl" src="{{asset('public/assets/admin')}}/svg/illustrations/sorry.svg" alt="{{\App\CPU\translate('Image Description')}}">
                                <p class="mb-0">{{ \App\CPU\translate('No_data_to_show')}}</p>
                            </div>
                        @endif
                    </div>
                    <!-- End Table -->
                </div>
                <!-- End Card -->
            </div>
        </div>
    </div>
    
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="row align-items-center mb-3">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title d-flex align-items-center g-2px text-capitalize"><i
                        class="tio-filter-list"></i> {{\App\CPU\translate('Refund Products And Remain Stock')}}
                    <span class="badge badge-soft-dark ml-2">{{$remain_stocks->count()}}</span>
                </h1>
            </div>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <!-- Card -->
                    <!-- Table -->
                    <div class="table-responsive datatable-custom">
                        <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                            <tr>
                                <th>{{\App\CPU\translate('#')}}</th>
                                <th>{{\App\CPU\translate('product')}}</th>
                                <th>{{\App\CPU\translate('stock')}}</th>
                            </tr>
                            </thead>

                            <tbody id="set-rows">
                            @foreach($remain_stocks->get() as $key => $stock)
                                <tr>
                                    <td>{{ $key+1 }}</td>
                                    <td>
                                        {{ $stock->product->name }}
                                    </td>
                                    <td>
                                        {{ $stock->stock }}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        @if($remain_stocks->count() == 0)
                            <div class="text-center p-4">
                                <img class="mb-3 w-one-cl" src="{{asset('public/assets/admin')}}/svg/illustrations/sorry.svg" alt="{{\App\CPU\translate('Image Description')}}">
                                <p class="mb-0">{{ \App\CPU\translate('No_data_to_show')}}</p>
                            </div>
                        @endif
                    </div>
                    <!-- End Table -->
                </div>
                <!-- End Card -->
            </div>
        </div>
    </div>

    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="row align-items-center mb-3">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title d-flex align-items-center g-2px text-capitalize"><i
                        class="tio-filter-list"></i> {{\App\CPU\translate('Vehicle Data')}}
                </h1>
            </div>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <!-- Card -->
                <div class="card">

                    @php
                        $remain_stock = 0;
                        $total_stock = 0;
                        $total_cash = 0;
                        $refund_total = 0;
                        $total_credit = 0;
                        $order_count = 0;
                        $product_count = $stocks->count();
                        $remain_product_count = $remain_stocks->count();
                        $installment_total = 0;
                        
                        foreach ($stocks->get() as $st) {
                            $remain_stock += $st->stock;
                            $total_stock += $st->main_stock - $st->stock;
                        }
                        
                        foreach ($remain_stocks->get() as $st) {
                            $remain_stock += $st->stock;
                        }
                        
                        $total_cash = \App\Models\Transection::where('seller_id', $seller->id)->where('account_id', 1)->sum('amount');
                        $total_credit = \App\Models\Transection::where('seller_id', $seller->id)->where('account_id', 9)->where('tran_type', 4)->sum('amount');
                        $refund_total = \App\Models\Transection::where('seller_id', $seller->id)->where('tran_type', 7)->sum('amount');
                        $installment_total = \App\Models\Installment::where('seller_id', $seller->id)->sum('total_price');

                    @endphp
                    <div class="col-sm-12 col-lg-4 mb-3 mt-3 mb-lg-5"><!-- Card -->
                        <a class="card card-hover-shadow h-100 color-one" href="#">
                            <div class="card-body">
                                <div class="flex-between align-items-center mb-1">
                                    <div>
                                        <h6 class="card-subtitle text-white">Seller Name: {{ $seller->f_name . ' ' . $seller->l_name }}</h6>
                                        <h6 class="card-subtitle text-white">Seller Code: {{ $seller->mandob_code }}</h6>
                                        
                                        <span class="card-title text-white">
                                            Car Code: {{ \App\Models\Store::where('store_id', $seller->vehicle_code)->first()->store_code }}
                                        </span>
                                        <span class="card-title text-white">
                                            Car Name: {{ \App\Models\Store::where('store_id', $seller->vehicle_code)->first()->store_name1 }}
                                        </span>
                                        
                                       <span class="card-title text-white">
                                            Cash Sales: {{ number_format($total_cash, 2) }}
                                        </span>
                                        <span class="card-title text-white">
                                            Credit Sales: {{ number_format($total_credit, 2) }}
                                        </span>
                                        <span class="card-title text-white">
                                            Refunds Sales: {{ number_format($refund_total, 2) }}
                                        </span>
                                        <span class="card-title text-white">
                                            Installment: {{ number_format($installment_total, 2) }}
                                        </span>
                                        <span class="card-title text-white">
                                            Orders Count: {{ $orders->count() }}
                                        </span>

                                        <span class="card-title text-white">
                                            Item Sale Count: {{ $product_count }}
                                        </span>
                                        <span class="card-title text-white">
                                            Remain Sale Count: {{ $remain_product_count }}
                                        </span>
                                        <span class="card-title text-white">
                                            Qty Sale Count: {{ $total_stock }}
                                        </span>
                                        <span class="card-title text-white">
                                            Remain Stock: {{ $remain_stock }}
                                        </span>
                                    </div>
                                </div>
                                <!-- End Row -->
                            </div>
                        </a>
                        <!-- End Card -->
                    </div>
                </div>
                <!-- End Card -->
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script src={{asset("public/assets/admin/js/global.js")}}></script>
@endpush
