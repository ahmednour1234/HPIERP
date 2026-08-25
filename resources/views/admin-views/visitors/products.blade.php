@extends('layouts.admin.app')

@section('title',\App\CPU\translate('stock_list'))

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
                        class="tio-filter-list"></i> {{\App\CPU\translate('sale_stock')}}
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
                            @foreach($stocks as $key => $stock)
                                <tr>
                                    <td>{{ $key+1 }}</td>
                                    <td>
                                        {{ $stock->product->name }}
                                    </td>
                                    <td>
                                        {{ $stock->stock }}
                                    </td>
                                    <td>
                                        {{ $stock->main_stock - $stock->stock }}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        @if(count($stocks)==0)
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
                        class="tio-filter-list"></i> {{\App\CPU\translate('remain_stock')}}
                    <span class="badge badge-soft-dark ml-2">{{$remain_stocks->count()}}</span>
                </h1>
            </div>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <!-- Card -->
                <div class="card">

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
                            @foreach($remain_stocks as $key => $stock)
                                <tr>
                                    <td>{{ $key+1 }}</td>
                                    <td>
                                        {{ $stock->product->name }}
                                    </td>
                                    <td>
                                        {{ $stock->main_stock }}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>

                        {{-- <div class="page-area">
                            <table>
                                <tfoot class="border-top">
                                {!! $stocks->links() !!}
                                </tfoot>
                            </table>
                        </div> --}}
                        @if(count($stocks)==0)
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
                        $seller = $stocks[0]->seller;
                        $orders = \App\Models\Order::where('owner_id', $seller->id)->get();
                    @endphp
                    @php 
                        $remain_stock = 0;
                        $total_stock = 0;
                        $total_cash = 0;
                        $refund_total = 0;
                        $total_credit = 0;
                        $order_count = 0;
                        $installment_total = 0;
                        
                        foreach($stocks as $st) {
                            $total_stock += $st->stock;
                            $remain_stock += $st->main_stock - $st->stock;
                            
                        }
                        
                        foreach($remain_stocks as $st) {
                            $remain_stock += $st->main_stock - $st->stock;
                            
                        }
                        
                        $order_ids = \App\Models\Order::where('owner_id', $seller->id)->pluck('id');
                        
                        $total_cash = \App\Models\HistoryTransection::whereIn('order_id', $order_ids)->where('account_id', 1)->sum('amount');
                        $total_credit = \App\Models\Order::where('owner_id', $seller->id)->where('type', 4)->sum('order_amount');
                        $refund_total = \App\Models\Order::where('owner_id', $seller->id)->where('type', 7)->sum('order_amount');
                        $installment_total = \App\Models\HistoryInstallment::where('seller_id', $seller->id)->sum('total_price');
                        
                    @endphp
                    <div class="col-sm-12 col-lg-4 mb-3 mt-3 mb-lg-5"><!-- Card -->
                        <a class="card card-hover-shadow h-100 color-one" href="#">
                            <div class="card-body">
                                <div class="flex-between align-items-center mb-1">
                                    <div>
                                        <h6 class="card-subtitle text-white">seller name:
                                            {{ $seller->f_name . ' ' . $seller->l_name }}</h6>
                                        <span class="card-title text-white">
                                            vehicle code: {{ \App\Models\Store::where('store_id', $seller->vehicle_code)->first()->store_code }}
                                        </span>
                                        <span class="card-title text-white">
                                            total cash: {{ number_format($total_cash, 2) }}
                                        </span>
                                        <span class="card-title text-white">
                                            total order credit: {{ number_format($total_credit, 2) }}
                                        </span>
                                        <span class="card-title text-white">
                                            total refund credit: {{ number_format($refund_total, 2) }}
                                        </span>
                                        <span class="card-title text-white">
                                            total installment: {{ number_format($installment_total, 2) }}
                                        </span>
                                        <span class="card-title text-white">
                                            orders count: {{ $orders->count() }}
                                        </span>
                                        <span class="card-title text-white">
                                            total sales stock: {{ $total_stock }}
                                        </span>
                                        <span class="card-title text-white">
                                            total remains stock: {{ $remain_stock }}
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
