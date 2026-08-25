@extends('layouts.admin.app')
@section('title','Notification List')
@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin') }}/css/custom.css"/>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="row align-items-center mb-3">
            <div class="col-sm">
                <h1 class="page-header-title text-capitalize">
                    {{ \App\CPU\translate('notification') }} {{ \App\CPU\translate('list') }}
                </h1>
            </div>
        </div>
         <div class="table-responsive mb-3">
            <h5>{{ \App\CPU\translate('تحويلات المناديب') }}</h5>
            <table class="table table-hover table-borderless">
                <thead class="thead-light">
                    <tr>
                        <th>{{ \App\CPU\translate('SH') }}</th>
                        <th>{{ \App\CPU\translate('Type') }}</th>
                        <th>{{ \App\CPU\translate('Seller Name') }}</th>
                        <th>{{ \App\CPU\translate('Created At') }}</th>
                        <th>{{ \App\CPU\translate('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($TransactionSellers as $key => $order)
                        <tr>
                            <td>{{ $key+1 }}</td>
                            <td>{{ \App\CPU\translate('تحويل مندوب') }}</td>
                            <td>{{ $order->sellers->email ?? '' }}</td>
                            <td>{{ $order->created_at->format('Y-m-d H:i:s') }}</td>
                            <td>
                                <a href="{{ route('admin.admin.notifications.show', ['id' => $order->id, 'type' => 'TransactionSeller']) }}" class="btn btn-info btn-sm">
                                    {{ \App\CPU\translate('Show') }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>


        <!-- Orders Table -->
        <div class="table-responsive mb-3">
            <h5>{{ \App\CPU\translate('Orders') }}</h5>
            <table class="table table-hover table-borderless">
                <thead class="thead-light">
                    <tr>
                        <th>{{ \App\CPU\translate('SH') }}</th>
                        <th>{{ \App\CPU\translate('Type') }}</th>
                        <th>{{ \App\CPU\translate('Customer Name') }}</th>
                        <th>{{ \App\CPU\translate('Seller Name') }}</th>
                        <th>{{ \App\CPU\translate('Created At') }}</th>
                        <th>{{ \App\CPU\translate('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $key => $order)
                        <tr>
                            <td>{{ $key+1 }}</td>
                            <td>{{ \App\CPU\translate('Order') }}</td>
                            <td>{{ $order->customer->name ??'' }}</td>
                            <td>{{ $order->seller->f_name ?? '' }}</td>
                            <td>{{ $order->created_at->format('Y-m-d H:i:s') }}</td>
                            <td>
                                <a href="{{ route('admin.admin.notifications.show', ['id' => $order->id, 'type' => 'order']) }}" class="btn btn-info btn-sm">
                                    {{ \App\CPU\translate('Show') }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Refund Orders Table -->
        <div class="table-responsive mb-3">
            <h5>{{ \App\CPU\translate('Refund Orders') }}</h5>
            <table class="table table-hover table-borderless">
                <thead class="thead-light">
                    <tr>
                        <th>{{ \App\CPU\translate('SH') }}</th>
                        <th>{{ \App\CPU\translate('Type') }}</th>
                        <th>{{ \App\CPU\translate('Customer Name') }}</th>
                        <th>{{ \App\CPU\translate('Seller Name') }}</th>
                        <th>{{ \App\CPU\translate('Created At') }}</th>
                        <th>{{ \App\CPU\translate('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($refundOrders as $key => $refundOrder)
                        <tr>
                            <td>{{ $key+1 }}</td>
                            <td>{{ \App\CPU\translate('Refund Order') }}</td>
                            <td>{{ $refundOrder->customer->name }}</td>
                            <td>{{ $refundOrder->seller->f_name ?? '' }}</td>
                            <td>{{ $refundOrder->created_at->format('Y-m-d H:i:s') }}</td>
                            <td>
                                <a href="{{ route('admin.admin.notifications.show', ['id' => $refundOrder->id, 'type' => 'order']) }}" class="btn btn-info btn-sm">
                                    {{ \App\CPU\translate('Show') }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Installments Table -->
        <div class="table-responsive mb-3">
            <h5>{{ \App\CPU\translate('Installments') }}</h5>
            <table class="table table-hover table-borderless">
                <thead class="thead-light">
                    <tr>
                        <th>{{ \App\CPU\translate('SH') }}</th>
                        <th>{{ \App\CPU\translate('Type') }}</th>
                        <th>{{ \App\CPU\translate('Customer Name') }}</th>
                        <th>{{ \App\CPU\translate('Seller Name') }}</th>
                        <th>{{ \App\CPU\translate('Created At') }}</th>
                        <th>{{ \App\CPU\translate('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($installments as $key => $installment)
                        <tr>
                            <td>{{ $key+1 }}</td>
                            <td>{{ \App\CPU\translate('Installment') }}</td>
                            <td>{{ $installment->customer->name ??'' }}</td>
                            <td>{{ $installment->seller->f_name ?? '' }}</td>
                            <td>{{ $installment->created_at->format('Y-m-d H:i:s') }}</td>
                            <td>
                                <a href="{{ route('admin.admin.notifications.show', ['id' => $installment->id, 'type' => 'installment']) }}" class="btn btn-info btn-sm">
                                    {{ \App\CPU\translate('Show') }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Reserve Products Table -->
        <div class="table-responsive mb-3">
            <h5>{{ \App\CPU\translate('Reserve Products') }}</h5>
            <table class="table table-hover table-borderless">
                <thead class="thead-light">
                    <tr>
                        <th>{{ \App\CPU\translate('SH') }}</th>
                        <th>{{ \App\CPU\translate('Type') }}</th>
                        <th>{{ \App\CPU\translate('Customer Name') }}</th>
                        <th>{{ \App\CPU\translate('Seller Name') }}</th>
                        <th>{{ \App\CPU\translate('Created At') }}</th>
                        <th>{{ \App\CPU\translate('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reserveProducts as $key => $reserveProduct)
                        <tr>
                            <td>{{ $key+1 }}</td>
                            <td>{{ \App\CPU\translate('Reserve Product') }}</td>
                            <td>{{ $reserveProduct->customer->name ??'' }}</td>
                            <td>{{ $reserveProduct->seller->f_name ?? '' }}</td>
                            <td>{{ $reserveProduct->created_at->format('Y-m-d H:i:s') }}</td>
                            <td>
                                <a href="{{ route('admin.admin.notifications.show', ['id' => $reserveProduct->id, 'type' => 'reserveProduct']) }}" class="btn btn-info btn-sm">
                                    {{ \App\CPU\translate('Show') }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Re-Reserve Products Table -->
        <div class="table-responsive mb-3">
            <h5>{{ \App\CPU\translate('Re-Reserve Products') }}</h5>
            <table class="table table-hover table-borderless">
                <thead class="thead-light">
                    <tr>
                        <th>{{ \App\CPU\translate('SH') }}</th>
                        <th>{{ \App\CPU\translate('Type') }}</th>
                        <th>{{ \App\CPU\translate('Customer Name') }}</th>
                        <th>{{ \App\CPU\translate('Seller Name') }}</th>
                        <th>{{ \App\CPU\translate('Created At') }}</th>
                        <th>{{ \App\CPU\translate('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reReserveProducts as $key => $reReserveProduct)
                        <tr>
                            <td>{{ $key+1 }}</td>
                            <td>{{ \App\CPU\translate('Re-Reserve Product') }}</td>
                            <td>{{ $reReserveProduct->customer->name ??'' }}</td>
                            <td>{{ $reReserveProduct->seller->f_name ?? '' }}</td>
                            <td>{{ $reReserveProduct->created_at->format('Y-m-d H:i:s') }}</td>
                            <td>
                                <a href="{{ route('admin.admin.notifications.show', ['id' => $reReserveProduct->id, 'type' => 'reserveProduct']) }}" class="btn btn-info btn-sm">
                                    {{ \App\CPU\translate('Show') }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
