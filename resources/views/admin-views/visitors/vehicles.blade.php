@extends('layouts.admin.app')

@section('title',\App\CPU\translate('stock_list'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/custom.css"/>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="card mb-3 bg-white">
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h4 class="card-header">
                            <div class="row justify-content-between align-items-center flex-grow-1">
                                <div class="col-12 col-sm-5">
                                    <span>{{\App\CPU\translate('vehicle_stocks')}}</span>
                                </div>
                                <div class="col-12 col-sm-7 col-md-6 col-lg-4 col-xl-6 mb-3 mb-sm-0">
                                    <form action="{{url()->current()}}" method="GET">
                                        <!-- Search -->
                                        <div class="input-group input-group-merge input-group-flush">
                                            <div class="input-group-prepend">
                                                <div class="input-group-text">
                                                    <i class="tio-search"></i>
                                                </div>
                                            </div>
                                            <input type="date" name="search" class="form-control" aria-label="Search" value="{{ $date }}" required>
                                            <button type="submit" class="btn btn-primary">{{\App\CPU\translate('search')}} </button>

                                        </div>
                                        <!-- End Search -->
                                    </form>
                                </div>
                            </div>
                        </h4>
                    </div>
                </div>
                <div class="row mb-4">
                    @foreach($sellers as $seller)
                        @php
                            if (Request::has('search'))
                            {
                                $stocks = \App\Models\ConfirmStock::where('seller_id', $seller->id)->where('created_at', 'LIKE', Request::get('search') . '%')->whereRaw('stock != 0');
                                $remain_stocks = \App\Models\ConfirmStock::where('seller_id', $seller->id)->whereRaw('stock = 0');
                            }
                            else {
                                $stocks = \App\Models\ConfirmStock::where('seller_id', $seller->id)->whereRaw('stock != 0');
                                $remain_stocks = \App\Models\ConfirmStock::where('seller_id', $seller->id)->whereRaw('stock = 0');
                            }
                            $orders = \App\Models\Order::where('owner_id', $seller->id);
                        @endphp
                        @if ($stocks->count() > 0 || $remain_stocks->count() > 0)
                            @php 
                                $remain_stock = 0;
                                $total_stock = 0;
                                $total_cash = 0;
                                $refund_total = 0;
                                $total_credit = 0;
                                $order_count = 0;
                                $installment_total = 0;
                                $product_count = $stocks->count();
                                $remain_product_count = $remain_stocks->count();
                                
                                foreach($stocks->get() as $st) {
                                    $remain_stock += $st->main_stock - $st->stock;
                                    $total_stock += $st->stock;
                                }
                                
                                foreach ($remain_stocks->get() as $st) {
                                    $remain_stock += $st->main_stock;
                                }
                                
                                
                                $order_ids = \App\Models\Order::where('owner_id', $seller->id)->pluck('id');
                                
                                $total_cash = \App\Models\HistoryTransection::whereIn('order_id', $order_ids)->where('account_id', 1)->sum('amount');
                                $total_credit = \App\Models\Order::where('owner_id', $seller->id)->where('type', 4)->sum('order_amount');
                                $refund_total = \App\Models\Order::where('owner_id', $seller->id)->where('type', 7)->sum('order_amount');
                                $installment_total = \App\Models\HistoryInstallment::where('seller_id', $seller->id)->sum('total_price');
                                
                            @endphp
                            <div class="col-sm-12 col-lg-4 mb-3 mt-3 mb-lg-5"><!-- Card -->
                                <a class="card card-hover-shadow h-100 color-one" 
                                    href="{{ route('admin.stock.products', $seller->id) }}">
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
                        @else
                            @continue
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script src={{asset("public/assets/admin/js/global.js")}}></script>
@endpush
