@extends('layouts.admin.app')
@section('title', \App\CPU\translate('Notification List'))
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
        {{-- الصفحة تعرض إشعارات مناديب الحساب الحالي فقط. حساب بلا مناديب
             كان يعرض جداول فارغة بلا سبب ظاهر، فيبدو الأمر كعطل. --}}
        @if($TransactionSellers->isEmpty() && $orders->isEmpty() && $refundOrders->isEmpty()
            && $installments->isEmpty() && $reserveProducts->isEmpty())
            <div class="alert alert-info text-center">
                {{ \App\CPU\translate('no_seller_assigned') }}
            </div>
        @endif

        {{-- تبويبات بدل ست جداول متتابعة تُطيل الصفحة --}}
        <ul class="nav nav-tabs mb-3" id="notifTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" href="#tab-transfers" role="tab">
                    {{ \App\CPU\translate('تحويلات المناديب') }}
                    <span class="badge badge-soft-primary ml-1">{{ $TransactionSellersCount ?? 0 }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#tab-orders" role="tab">
                    {{ \App\CPU\translate('الطلبات') }}
                    <span class="badge badge-soft-primary ml-1">{{ $orderCount ?? 0 }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#tab-refunds" role="tab">
                    {{ \App\CPU\translate('المرتجعات') }}
                    <span class="badge badge-soft-primary ml-1">{{ $refundOrderCount ?? 0 }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#tab-installments" role="tab">
                    {{ \App\CPU\translate('التحصيلات') }}
                    <span class="badge badge-soft-primary ml-1">{{ $installmentCount ?? 0 }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#tab-reserves" role="tab">
                    {{ \App\CPU\translate('حجوزات المنتجات') }}
                    <span class="badge badge-soft-primary ml-1">{{ $reserveProductCount ?? 0 }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#tab-rereserves" role="tab">
                    {{ \App\CPU\translate('إعادة الحجوزات') }}
                    <span class="badge badge-soft-primary ml-1">{{ $reReserveProductCount ?? 0 }}</span>
                </a>
            </li>
        </ul>

        <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-transfers" role="tabpanel">
         <div class="table-responsive mb-3">
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
                            <td>{{ $TransactionSellers->firstItem() + $key }}</td>
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
            @if ($TransactionSellers->hasPages())
                <div class="notif-pager d-flex justify-content-end mt-2">
                    {{ $TransactionSellers->links() }}
                </div>
            @endif
        </div>


        <!-- Orders Table -->
        </div>
        <div class="tab-pane fade" id="tab-orders" role="tabpanel">
        <div class="table-responsive mb-3">
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
                            <td>{{ $orders->firstItem() + $key }}</td>
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
            @if ($orders->hasPages())
                <div class="notif-pager d-flex justify-content-end mt-2">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>

        <!-- Refund Orders Table -->
        </div>
        <div class="tab-pane fade" id="tab-refunds" role="tabpanel">
        <div class="table-responsive mb-3">
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
                            <td>{{ $refundOrders->firstItem() + $key }}</td>
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
            @if ($refundOrders->hasPages())
                <div class="notif-pager d-flex justify-content-end mt-2">
                    {{ $refundOrders->links() }}
                </div>
            @endif
        </div>

        <!-- Installments Table -->
        </div>
        <div class="tab-pane fade" id="tab-installments" role="tabpanel">
        <div class="table-responsive mb-3">
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
                            <td>{{ $installments->firstItem() + $key }}</td>
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
            @if ($installments->hasPages())
                <div class="notif-pager d-flex justify-content-end mt-2">
                    {{ $installments->links() }}
                </div>
            @endif
        </div>

        <!-- Reserve Products Table -->
        </div>
        <div class="tab-pane fade" id="tab-reserves" role="tabpanel">
        <div class="table-responsive mb-3">
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
                            <td>{{ $reserveProducts->firstItem() + $key }}</td>
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
            @if ($reserveProducts->hasPages())
                <div class="notif-pager d-flex justify-content-end mt-2">
                    {{ $reserveProducts->links() }}
                </div>
            @endif
        </div>

        <!-- Re-Reserve Products Table -->
        </div>
        <div class="tab-pane fade" id="tab-rereserves" role="tabpanel">
        <div class="table-responsive mb-3">
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
                            <td>{{ $reReserveProducts->firstItem() + $key }}</td>
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
            @if ($reReserveProducts->hasPages())
                <div class="notif-pager d-flex justify-content-end mt-2">
                    {{ $reReserveProducts->links() }}
                </div>
            @endif
        </div>
        </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        "use strict";

        // تبديل التبويبات يدويًا بدل الاعتماد على إضافة Bootstrap:
        // القالب لا يحمّل bootstrap.js مستقلًا، وهذه الشاشة أول من يستخدم
        // التبويبات في المشروع، فلا نعتمد على وجود الإضافة.
        document.addEventListener('DOMContentLoaded', function () {
            var links = document.querySelectorAll('#notifTabs .nav-link');

            links.forEach(function (link) {
                link.addEventListener('click', function (e) {
                    e.preventDefault();

                    var target = document.querySelector(link.getAttribute('href'));
                    if (!target) { return; }

                    links.forEach(function (l) { l.classList.remove('active'); });
                    document.querySelectorAll('.tab-content .tab-pane').forEach(function (p) {
                        p.classList.remove('show', 'active');
                    });

                    link.classList.add('active');
                    target.classList.add('show', 'active');
                });
            });
        });
    </script>
@endpush
