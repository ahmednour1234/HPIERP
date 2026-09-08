@extends('layouts.admin.app')
@section('title','Order List')
@push('css_or_js')
    {{-- <meta name="csrf-token" content="{{ csrf_token() }}"> --}}
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/custom.css"/>
    <style>
        .pos-orders-page {
            direction: rtl;
            background: linear-gradient(135deg, rgba(17, 36, 90, .05), rgba(20, 184, 166, .08)), #f4f8fb;
            min-height: calc(100vh - 4rem);
            padding-top: 2rem;
            padding-bottom: 2.5rem;
            color: #102a43;
        }

        .pos-orders-shell {
            display: flex;
            flex-direction: column;
            gap: 1.15rem;
        }

        .pos-orders-hero,
        .pos-orders-card,
        .pos-orders-filter-card {
            border: 1px solid #d9e6f2;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 14px 32px rgba(15, 23, 42, .08);
            overflow: hidden;
        }

        .pos-orders-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.35rem 1.5rem;
            background: linear-gradient(135deg, #111857, #0f766e);
            color: #fff;
        }

        .pos-orders-title {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin: 0;
            color: #fff;
            font-size: 1.65rem;
            font-weight: 900;
        }

        .pos-orders-title span {
            width: 3rem;
            height: 3rem;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, .14);
        }

        .pos-orders-hero p {
            margin: .4rem 3.75rem 0 0;
            color: rgba(255, 255, 255, .76);
            font-weight: 700;
        }

        .pos-orders-count {
            border-radius: 999px;
            padding: .55rem .9rem;
            background: #facc15;
            color: #111857;
            font-weight: 900;
            white-space: nowrap;
        }

        .pos-orders-filter-card {
            margin-bottom: 1rem !important;
        }

        .pos-orders-filter-card .card-header {
            border-bottom: 1px solid #e7eef6;
            background: linear-gradient(90deg, #f8fbff, #eef7fb) !important;
            padding: 1rem 1.15rem;
        }

        .pos-orders-filter-title {
            display: flex;
            align-items: center;
            gap: .65rem;
            margin: 0;
            color: #132f52;
            font-weight: 900;
        }

        .pos-orders-filter-title span {
            width: 2.4rem;
            height: 2.4rem;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #11245a;
            color: #fff;
        }

        .pos-orders-filter-card .card-body {
            background: #fff !important;
            padding: 1.25rem !important;
        }

        .pos-orders-filter-form label {
            color: #52677f !important;
            font-weight: 900 !important;
            margin-bottom: .45rem;
        }

        .pos-orders-filter-form .form-control,
        .pos-orders-filter-form .custom-select,
        .pos-orders-filter-form .bootstrap-select > .dropdown-toggle {
            min-height: 3.15rem;
            border: 1px solid #d5e2ef !important;
            border-radius: 8px !important;
            background: #fff !important;
            color: #102a43 !important;
            font-weight: 800;
            box-shadow: 0 8px 18px rgba(15, 23, 42, .04) !important;
        }

        .pos-orders-filter-form .input-group {
            border: 1px solid #d5e2ef;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 8px 18px rgba(15, 23, 42, .04) !important;
        }

        .pos-orders-filter-form .input-group .form-control,
        .pos-orders-filter-form .input-group .input-group-text {
            border: 0 !important;
            box-shadow: none !important;
        }

        .pos-orders-actions {
            gap: .6rem;
        }

        .pos-orders-btn {
            min-height: 3.1rem;
            border-radius: 8px !important;
            padding: .75rem 1.15rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            font-weight: 900;
            box-shadow: 0 10px 20px rgba(15, 23, 42, .07);
        }

        .pos-orders-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
            margin: 0 0 1rem !important;
        }

        .pos-orders-summary > [class*="col-"] {
            max-width: none;
            padding: 0;
        }

        .pos-orders-summary-card {
            min-height: 6.8rem;
            border: 1px solid #dbe7f2;
            border-radius: 8px;
            background: #fff;
            padding: 1rem;
            box-shadow: 0 10px 22px rgba(15, 23, 42, .05);
            position: relative;
            overflow: hidden;
        }

        .pos-orders-summary-card::after {
            content: "";
            position: absolute;
            inset: auto auto -2.75rem -2.75rem;
            width: 6rem;
            height: 6rem;
            border-radius: 50%;
            background: currentColor;
            opacity: .1;
        }

        .pos-orders-summary-label {
            display: block;
            color: #60758b;
            font-size: .82rem;
            font-weight: 900;
            margin-bottom: .55rem;
        }

        .pos-orders-summary-value {
            color: #102a43;
            font-size: 1.25rem;
            font-weight: 900;
            word-break: break-word;
        }

        .pos-orders-summary-card--sales { color: #11245a; }
        .pos-orders-summary-card--cash { color: #0f9f6e; }
        .pos-orders-summary-card--products { color: #d97706; }
        .pos-orders-summary-card--qty { color: #7c3aed; }

        .pos-orders-table-wrap {
            border: 1px solid #e0e9f3;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
        }

        .pos-orders-table {
            margin: 0;
            color: #506882;
        }

        .pos-orders-table thead th {
            border: 0;
            background: #11245a;
            color: #fff;
            padding: 1rem;
            font-weight: 900;
            white-space: nowrap;
            text-align: right;
        }

        .pos-orders-table tbody td {
            border-top: 1px solid #e7eef6;
            padding: .95rem 1rem;
            vertical-align: middle;
            font-weight: 700;
        }

        .pos-orders-table tbody tr:hover {
            background: #f8fbff;
        }

        .pos-orders-invoice-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 4.5rem;
            border-radius: 999px;
            padding: .4rem .7rem;
            background: #e0f2fe;
            color: #075985;
            font-weight: 900;
            text-decoration: none;
        }

        .pos-orders-invoice-link:hover {
            color: #11245a;
            text-decoration: none;
        }

        .pos-orders-type-pill,
        .pos-orders-payment-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: .38rem .65rem;
            font-size: .8rem;
            font-weight: 900;
            white-space: nowrap;
        }

        .pos-orders-type-pill {
            background: #dcfce7;
            color: #166534;
        }

        .pos-orders-type-pill--refund {
            background: #ffe4e6;
            color: #be123c;
        }

        .pos-orders-payment-pill {
            background: #eef4fb;
            color: #52677f;
        }

        .pos-orders-collection {
            min-width: 12rem;
        }

        .pos-orders-collection-total {
            color: #102a43;
            font-weight: 900;
        }

        .pos-orders-collection small {
            font-weight: 800;
        }

        .pos-orders-collection .btn {
            border-radius: 8px;
            font-weight: 900;
            margin-top: .35rem;
        }

        .pos-orders-image {
            width: 3rem;
            height: 3rem;
            border-radius: 8px;
            object-fit: cover;
            cursor: pointer;
            border: 1px solid #dbe7f2;
        }

        .pos-orders-empty {
            padding: 3rem 1rem;
            text-align: center;
            color: #71869c;
            font-weight: 800;
        }

        .pos-orders-empty img {
            width: 7rem;
            margin-bottom: 1rem;
        }

        .pos-orders-pagination {
            border-top: 1px solid #e7eef6 !important;
            background: #fff;
        }

        @media (max-width: 1199.98px) {
            .pos-orders-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .pos-orders-page {
                padding-top: 1rem;
            }

            .pos-orders-hero {
                flex-direction: column;
                align-items: stretch;
            }

            .pos-orders-hero p {
                margin-right: 0;
            }

            .pos-orders-summary {
                grid-template-columns: 1fr;
            }
        }

        @media print {
            .pos-orders-filter-card,
            .pos-orders-pagination,
            .non-printable,
            .none {
                display: none !important;
            }

            .pos-orders-page {
                background: #fff;
                padding: 0;
            }

            .pos-orders-card,
            .pos-orders-summary-card,
            .pos-orders-table-wrap {
                box-shadow: none;
            }
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid pos-orders-page">
        <div class="pos-orders-shell">
        <!-- Page Header -->
        <section class="pos-orders-hero">
            <div>
                <h1 class="pos-orders-title">
                    <span><i class="tio-receipt-outlined"></i></span>
                    فواتير المبيعات
                </h1>
                <p>بحث وتصفية ومتابعة التحصيلات لكل فواتير نقطة البيع من شاشة واحدة مرتبة.</p>
            </div>
            <div class="pos-orders-count">{{ number_format($orders->total()) }} فاتورة</div>
        </section>
        <div class="d-none">
            <div class="row align-items-center mb-3">
                <div class="col-sm">
                    <h1 class="page-header-title text-capitalize">{{\App\CPU\translate('pos')}} {{\App\CPU\translate('المبيعات')}}
                        <span class="badge badge-soft-dark ml-2">{{$orders->total()}}</span></h1>
                </div>
            </div>
        </div>
        <!-- End Page Header -->
        <!-- Card -->
        <div class="card pos-orders-card">
            <!-- Header -->
<div class="card pos-orders-filter-card shadow-sm border-0 mb-4">
    <div class="card-header">
        <h5 class="pos-orders-filter-title">
            <span><i class="tio-search"></i></span>
            بحث وتصفية
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ url()->current() }}" method="GET" class="filter-panel-v2 pos-orders-filter-form">

            {{-- Row 1: the free-text search gets its own line — it is the
                 control people reach for first and benefits from the width. --}}
            <div class="row g-3 mb-3">
                <div class="col-lg-6">
                    <label class="form-label text-secondary fw-semibold small">
                        {{ \App\CPU\translate('بحث') }}
                    </label>
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white border-0">
                            <i class="tio-search text-muted"></i>
                        </span>
                        <input type="search" name="search" class="form-control border-0"
                               placeholder="{{ \App\CPU\translate('رقم الفاتورة، اسم العميل أو البائع') }}"
                               value="{{ $search }}">
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <label class="form-label text-secondary fw-semibold small">
                        {{ \App\CPU\translate('من تاريخ') }}
                    </label>
                    <input type="date" name="from_date" class="form-control shadow-sm" value="{{ $fromDate }}">
                </div>

                <div class="col-lg-3 col-md-6">
                    <label class="form-label text-secondary fw-semibold small">
                        {{ \App\CPU\translate('إلى تاريخ') }}
                    </label>
                    <input type="date" name="to_date" class="form-control shadow-sm" value="{{ $toDate }}">
                </div>
            </div>

            {{-- Row 2: the region picker is five rows tall, so it sits in its own column
                 with the short selects stacked beside it. In a single flat row the
                 short controls floated against the top of the tall one. --}}
            <div class="row g-3 mb-3 filter-row-aligned align-items-start">
                <div class="col-lg-4 col-md-6">
                    <label class="form-label text-secondary fw-semibold small d-block">
                        {{ \App\CPU\translate('المنطقة') }}
                        <span class="text-muted fw-normal">({{ \App\CPU\translate('اختيار متعدد') }})</span>
                    </label>
                    {{-- region_id[] posts an array; applyRegionFilter() also accepts
                         a single value, so older links keep working. --}}
                    <select name="region_id[]" class="selectpicker form-control" multiple
                            data-live-search="true"
                            data-actions-box="true"
                            data-selected-text-format="count > 2"
                            data-none-selected-text="اختار المنطقة"
                            data-count-selected-text="{0} مناطق مختارة"
                            title="اختار المنطقة">
                        @foreach($regions as $region)
                            {{-- The count tells you a region is empty before you filter by it. --}}
                            <option value="{{ $region->id }}"
                                @selected(in_array((string) $region->id, (array) $regionId))
                                class="{{ (isset($region->invoice_count) && $region->invoice_count === 0) ? 'text-muted' : '' }}">
                                {{ $region->name }}
                                @isset($region->invoice_count) ({{ $region->invoice_count }}) @endisset
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted d-block mt-1">اكتب للبحث، ويمكن اختيار أكثر من منطقة مباشرة.</small>
                </div>

                <div class="col-lg-8 col-md-6">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label text-secondary fw-semibold small d-block">
                                {{ \App\CPU\translate('المندوب') }}
                            </label>
                            <select name="seller_id" class="custom-select shadow-sm">
                                <option value="">{{ \App\CPU\translate('الكل') }}</option>
                                @foreach (($sellers ?? []) as $s)
                                    <option value="{{ $s->id }}"
                                        @selected((string) request('seller_id') === (string) $s->id)>
                                        {{ trim($s->f_name . ' ' . $s->l_name) }}@if ($s->mandob_code) ({{ $s->mandob_code }})@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label text-secondary fw-semibold small d-block">
                                {{ \App\CPU\translate('طريقة الدفع') }}
                            </label>
                            <select name="cash" class="custom-select shadow-sm">
                                <option value="">{{ \App\CPU\translate('الكل') }}</option>
                                <option value="1" @selected(request('cash') === '1')>{{ \App\CPU\translate('كاش') }}</option>
                                <option value="2" @selected(request('cash') === '2')>{{ \App\CPU\translate('آجل') }}</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label text-secondary fw-semibold small d-block">
                                {{ \App\CPU\translate('حالة التحصيل') }}
                            </label>
                            <select name="done" class="custom-select shadow-sm">
                                <option value="">{{ \App\CPU\translate('الكل') }}</option>
                                <option value="1" @selected(request('done') === '1')>{{ \App\CPU\translate('محصّلة بالكامل') }}</option>
                                <option value="0" @selected(request('done') === '0')>{{ \App\CPU\translate('عليها متبقي') }}</option>
                                {{-- فواتير صدر عليها مرتجع --}}
                                <option value="returned" @selected(request('done') === 'returned')>{{ \App\CPU\translate('عليها مرتجع') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

{{-- Row 3: apply and reset on one side, the outputs on the other. --}}
            <div class="d-flex flex-wrap justify-content-between align-items-center pt-3 border-top pos-orders-actions">
                <div class="d-flex flex-wrap pos-orders-actions">
                    <button type="submit" class="btn btn-primary pos-orders-btn">
                        <i class="tio-filter-list"></i> {{ \App\CPU\translate('تطبيق') }}
                    </button>
                    <a href="{{ url()->current() }}" class="btn btn-outline-secondary pos-orders-btn">
                        {{ \App\CPU\translate('إعادة تعيين') }}
                    </a>
                </div>

                <div class="d-flex flex-wrap pos-orders-actions">
                    {{-- Both carry the current filters, so what is downloaded or
                         printed matches what is on screen. --}}
                    <a href="{{ route('admin.pos.orders.export', request()->query()) }}"
                       class="btn btn-success pos-orders-btn">
                        <i class="tio-file-outlined"></i> {{ \App\CPU\translate('تصدير CSV') }}
                    </a>
                    <button type="button" class="btn btn-outline-secondary pos-orders-btn" onclick="printTable()">
                        <i class="tio-print"></i> {{ \App\CPU\translate('طباعة') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card-body" id="product-table">
    <!-- Total Sales -->
    <div class="row mb-3 pos-orders-summary">
        <div class="col-md-3">
            <div class="pos-orders-summary-card pos-orders-summary-card--sales">
                <span class="pos-orders-summary-label">إجمالي المبيعات</span>
                <span class="pos-orders-summary-value">{{ number_format($orderAmountSum, 2) }}</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="pos-orders-summary-card pos-orders-summary-card--cash">
                <span class="pos-orders-summary-label">إجمالي المبالغ المحصلة</span>
                <span class="pos-orders-summary-value">{{ number_format($collectedCashSum, 2) }}</span>
            </div>
        </div>
        <div class="col-md-3">  
            <div class="pos-orders-summary-card pos-orders-summary-card--products">
                <span class="pos-orders-summary-label">إجمالي عدد المنتجات المباعة</span>
                <span class="pos-orders-summary-value">{{ number_format($productCount) }}</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="pos-orders-summary-card pos-orders-summary-card--qty">
                <span class="pos-orders-summary-label">إجمالي كميات المنتجات المباعة</span>
                <span class="pos-orders-summary-value">{{ number_format($quantitySum) }}</span>
            </div>
        </div>
    </div>
            <!-- End Header -->

            <!-- Table -->
            <div class="table-responsive pos-orders-table-wrap">
                <table
                    class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table pos-orders-table"
                >
                    <thead class="thead-light">
                    <tr>
                        <th class="">
                            {{\App\CPU\translate('#')}}
                        </th>
                        <th class="table-column-pl-0">{{\App\CPU\translate('مبيعات')}}</th>
                        <th>{{\App\CPU\translate('اسم البائع')}}</th>
                        <th>{{\App\CPU\translate('اسم العميل')}}</th>
                        <th>{{\App\CPU\translate('المنطقة')}}</th>
                        <th>{{\App\CPU\translate('تاريخ')}}</th>
                        <th>{{\App\CPU\translate(' نوع')}}</th>
                        <th>{{\App\CPU\translate('طريقة الدفع')}}</th>
                        <th>{{\App\CPU\translate('اسم الحساب')}}</th>
                        <th>{{\App\CPU\translate('اجمالي الفاتورة')}}</th>
                        <th>{{\App\CPU\translate('خصم اضافي')}}</th>
                        <th>{{\App\CPU\translate('ضريبة')}}</th>
                        <th>{{\App\CPU\translate('المبلغ المدفوع')}}</th>
                <th>{{\App\CPU\translate('المبلغ المحصل')}}</th>
                        <th>{{\App\CPU\translate('التحصيلات')}}</th>
                        <th class="none">{{\App\CPU\translate('صورة الفاتورة')}}</th>
                        <th class="none">{{\App\CPU\translate('روؤية الفاتورة')}}</th>
                    </tr>
                    </thead>

                    <tbody id="set-rows">
                    @foreach($orders as $key=>$order)
                        <tr class="status-{{$order['order_status']}} class-all">
                            <td class="">
                                {{$key+$orders->firstItem()}}
                            </td>
                            <td class="table-column-pl-0">
                                <a class="pos-orders-invoice-link" href="#" onclick="print_invoice('{{$order->id}}')">{{$order['id']}}</a>
                            </td>
                            <td>
{{$order->seller->email ?? ''}}                            
</td>
                           <td>
    {{ optional($order->customer)->name ?? '' }}
</td>
<td>
    {{ optional(optional($order->customer)->regions)->name ?? '' }}
</td>

                            <td>{{date('d M Y',strtotime($order['created_at']))}}</td>
                            <td>
                                <span class="pos-orders-type-pill {{ $order['type'] == 4 ? '' : 'pos-orders-type-pill--refund' }}">{{ $order['type'] == 4 ? 'مبيع' : 'مرتجع' }}</span>
                            </td>
                            <td><span class="pos-orders-payment-pill">{{ $order['cash'] == 2 ? 'أجل' : 'كاش' }}</span></td>
                            <td>
                                {{ ($order->payment_id != 0) ? ($order->account ? $order->account->account : \App\CPU\translate('account_deleted')): 'Customer balance' }}
                            </td>
                            <td>
                                {{ number_format($order->order_amount , 2) }}
                            </td>
                        <td>{{ $order->extra_discount ? number_format($order->extra_discount, 2) : 0  }}</td>
                            <td>{{ number_format($order['total_tax'], 2) }}</td>
<td>
    @if($order->cash == 1)
        {{ number_format($order->collected_cash, 2) }}
    @else
        {{ number_format($order->collected_cash, 2) }}
    @endif
</td>
<td>
    @if($order->cash == 1)
        {{ number_format($order->transaction_reference, 2) }}
    @else
        {{ number_format($order->transaction_reference, 2) }}
    @endif
</td>
                            {{-- Collections: what has come in against this invoice, what is
                                 left, and the control to reverse a collection. --}}
<td class="pos-orders-collection">
                                @php
                                    $invoiceTotal = (float) $order->order_amount;
                                    $collected    = (float) $order->collected_cash;
                                    $remaining    = max($invoiceTotal - $collected, 0);
                                @endphp

                                <div class="mb-1">
                                    <span class="pos-orders-collection-total">{{ number_format($collected, 2) }}</span>
                                    <small class="text-muted">/ {{ number_format($invoiceTotal, 2) }}</small>
                                </div>

                                @if ($remaining > 0)
                                    <span class="badge badge-soft-warning d-inline-block mb-1">
                                        {{ \App\CPU\translate('متبقي') }} {{ number_format($remaining, 2) }}
                                    </span>
                                @else
                                    <span class="badge badge-soft-success d-inline-block mb-1">
                                        {{ \App\CPU\translate('محصّلة') }}
                                    </span>
                                @endif

                                {{-- تحصيل من الويب: يظهر فقط إن كان على الفاتورة متبقٍ.
                                     نفس خدمة التطبيق، فالقيود واحدة. --}}
                                @php($remaining = round((float) $order->order_amount - (float) $order->collected_cash, 2))

                                @if ($remaining > 0)
                                    <button type="button"
                                            class="btn btn-sm btn-outline-success btn-block pos-orders-collect-btn"
                                            data-toggle="modal"
                                            data-target="#collectModal-{{ $order->id }}"
                                            title="{{ \App\CPU\translate('تحصيل مبلغ على هذه الفاتورة') }}">
                                        <i class="tio-dollar"></i> {{ \App\CPU\translate('تحصيل') }}
                                    </button>

                                    <div class="modal fade" id="collectModal-{{ $order->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <form action="{{ route('admin.pos.orders.collect', [$order->id]) }}"
                                                      method="post" enctype="multipart/form-data">
                                                    @csrf
                                                    <div class="modal-header bg-light">
                                                        <h5 class="modal-title">
                                                            {{ \App\CPU\translate('تحصيل فاتورة') }} #{{ $order->id }}
                                                        </h5>
                                                        <button type="button" class="close" data-dismiss="modal">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body text-right">
                                                        <p class="mb-3">
                                                            {{ \App\CPU\translate('المتبقي') }}:
                                                            <strong>{{ number_format($remaining, 2) }}</strong>
                                                        </p>

                                                        <div class="form-group">
                                                            <label class="small">{{ \App\CPU\translate('المبلغ') }}</label>
                                                            <input type="number" step="0.01" min="0.01"
                                                                   max="{{ $remaining }}"
                                                                   name="amount" class="form-control" required>
                                                        </div>

                                                        <div class="form-group">
                                                            <label class="small">{{ \App\CPU\translate('الحساب') }}</label>
                                                            <select name="account_id" class="form-control" required>
                                                                @foreach (($accounts ?? []) as $acc)
                                                                    <option value="{{ $acc->id }}">{{ $acc->account }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div class="form-group">
                                                            <label class="small">{{ \App\CPU\translate('التاريخ') }}</label>
                                                            <input type="date" name="date" class="form-control"
                                                                   value="{{ now()->toDateString() }}">
                                                        </div>

                                                        <div class="form-group">
                                                            <label class="small">{{ \App\CPU\translate('ملاحظة') }}</label>
                                                            <input type="text" name="note" class="form-control" maxlength="255">
                                                        </div>

                                                        <div class="form-group mb-0">
                                                            <label class="small">{{ \App\CPU\translate('صورة الإيصال') }}</label>
                                                            <input type="file" name="img" class="form-control" accept="image/*">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                                            {{ \App\CPU\translate('إلغاء') }}
                                                        </button>
                                                        <button type="submit" class="btn btn-success">
                                                            {{ \App\CPU\translate('تأكيد التحصيل') }}
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if ($collected > 0)
                                    {{-- A real button: this was an <a><small> with no button
                                         class, so it read as plain text under the badge. --}}
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger btn-block btn-reverse-collection pos-orders-reverse-btn"
                                            title="{{ \App\CPU\translate('رد مبلغ محصّل على هذه الفاتورة') }}"
                                            onclick="reverseCollection({{ $order->id }}, {{ $collected }})">
                                        <i class="tio-undo"></i> {{ \App\CPU\translate('رد التحصيل') }}
                                    </button>
                                    <form action="{{ route('admin.pos.orders.reverse', [$order->id]) }}"
                                          method="post" id="reverse-{{ $order->id }}" class="d-none">
                                        @csrf
                                        <input type="hidden" name="amount" value="">
                                        <input type="hidden" name="note" value="">
                                    </form>
                                @endif
                            </td>
   <td class="none">
    @if (!empty($order['img']))
        <img 
        src="{{ asset('storage/shop/'.$order['img']) }}" 
        alt="Image Description" 
        class="pos-orders-image"
        data-toggle="modal" 
        data-target="#imageModal{{ $order['id'] }}">
    @else
        <span class="text-muted">-</span>
    @endif
</td>

<!-- Modal -->
<div class="modal fade none" id="imageModal{{ $order['id'] }}" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel{{ $order['id'] }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel{{ $order['id'] }}">Image Preview</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                @if (!empty($order['img']))
        <img 
                    src="{{ asset('storage/shop/'.$order['img']) }}" 
                    alt="Image Description" 
                    style="max-width: 100%; height: auto;">
    @else
        <span class="text-muted">-</span>
    @endif
            </div>
        </div>
    </div>
</div>

                            <td class="none">
                                <button class="btn btn-sm btn-white" target="_blank" type="button"
                                        onclick="print_invoice('{{$order->id}}')"><i
                                        class="tio-download"></i> {{\App\CPU\translate('الفاتورة')}}</button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <!-- End Table -->

            <!-- Footer -->
            <div class="card-footer none pos-orders-pagination">
                <!-- Pagination -->
                <div class="row justify-content-center justify-content-sm-between align-items-sm-center">
                    <div class="col-sm-auto">
                        <div class="d-flex justify-content-center justify-content-sm-end">
                            <!-- Pagination -->
                            {!! $orders->withQueryString()->links() !!}
                        </div>
                    </div>
                </div>
                <!-- End Pagination -->
            </div>
            @if(count($orders)==0)
                <div class="pos-orders-empty">
                    <img class="mb-3 img-one-ol" src="{{asset('public/assets/admin')}}/svg/illustrations/sorry.svg"
                         alt="Image Description">
                    <p class="mb-0">{{ \App\CPU\translate('No_data_to_show')}}</p>
                </div>
        @endif
        <!-- End Footer -->
        </div>
        <!-- End Card -->
        </div>
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
<script>
    function printTable() {
        const tableContent = document.getElementById('product-table').innerHTML;


        const printWindow = window.open('', '_blank', 'width=800,height=600');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>{{ \App\CPU\translate('تقرير المبيعات') }}</title>
                <style>
                
                    body {
                        font-family: Arial, sans-serif;
                        margin: 20px;
                    }
                     body {
                            font-family: 'Cairo', Arial, sans-serif;
                            margin: 0;
                            background-color: #f9f9f9;
                            color: #333;
                            direction: rtl;
                        }

                        h1 {
                            text-align: center;
                            color: #003366;
                            font-weight: bold;
                            font-size: 28px;
                            margin-bottom: 20px;
                        }

                        .header-section {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            border-bottom: 2px solid #003366;
                            padding: 10px 0;
                            margin-bottom: 30px;
                            flex-wrap: wrap;
                        }

                        .header-section .left,
                        .header-section .right,
                        .header-section .logo {
                            width: 32%;
                            text-align: center;
                        }

                        .header-section p {
                            margin: 5px 0;
                            line-height: 1.6;
                            font-size: 16px;
                        }

                        .logo img {
                            max-width: 150px;
                            height: auto;
                        }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-bottom: 20px;
                    }
                    table th, table td {
                        border: 1px solid #ddd;
                        padding: 8px;
                        text-align: left;
                    }
                    table th {
                        background-color: #f2f2f2;
                        font-weight: bold;
                    }
                    .row {
                        display: flex;
                        flex-wrap: wrap;
                        margin-bottom: 10px;
                    }
                    .col-md-3 {
                        flex: 0 0 25%;
                        max-width: 25%;
                        padding: 5px;
                        box-sizing: border-box;
                    }
                    .none{
                        display:none;
                    }
                    strong {
                        font-weight: bold;
                    }
                    input[type="search"][aria-controls="DataTables_Table_0"] {
    display: none;
}
label:has(input[type="search"][aria-controls="DataTables_Table_0"]) {
    display: none;
}
label:has(input[type="search"][aria-controls="DataTables_Table_1"]) {
    display: none;
}
label:has(input[type="search"][aria-controls="DataTables_Table_2"]) {
    display: none;
}
label:has(input[type="search"][aria-controls="DataTables_Table_3"]) {
    display: none;
}
label:has(input[type="search"][aria-controls="DataTables_Table_4"]) {
    display: none;
}
label:has(input[type="search"][aria-controls="DataTables_Table_5"]) {
    display: none;
}
#DataTables_Table_0_info{
        display: none;

}
#DataTables_Table_1_info{
            display: none;

}
#DataTables_Table_2_info{
            display: none;

}
#DataTables_Table_3_info{
            display: none;

}
#DataTables_Table_4_info{
            display: none;

}
#DataTables_Table_5_info{
            display: none;

}
#links{
    display: block;
}
                </style>
            </head>
            <body>
            <div class="header-section">
                        <div class="left">
                            <p><strong>رقم السجل التجاري:</strong> {{ optional(\App\Models\BusinessSetting::where(["key" => "vat_reg_no"])->first())->value??'' }}</p>
                            <p><strong>الرقم الضريبي:</strong> {{ optional(\App\Models\BusinessSetting::where(["key" => "number_tax"])->first())->value ??''}}</p>
                            <p><strong>البريد الإلكتروني:</strong> {{ optional(\App\Models\BusinessSetting::where(["key" => "shop_email"])->first())->value }}</p>
                        </div>
                        <div class="logo">
                            <img src="{{ asset('storage/shop/' . optional(\App\Models\BusinessSetting::where(['key' => 'shop_logo'])->first())->value) }}" alt="شعار المتجر">
                        </div>
                        <div class="right">
                            <p><strong>اسم المؤسسة:</strong> {{ optional(\App\Models\BusinessSetting::where(["key" => "shop_name"])->first())->value }}</p>
                            <p><strong>العنوان:</strong> {{ optional(\App\Models\BusinessSetting::where(["key" => "shop_address"])->first())->value }}</p>
                            <p><strong>رقم الجوال:</strong> {{ optional(\App\Models\BusinessSetting::where(["key" => "shop_phone"])->first())->value }}</p>
                        </div>
                    </div>
                    
                <h2>{{ \App\CPU\translate('تقرير   المبيعات') }}</h2>
                
                ${tableContent}
                <hr>
                <script>
                    window.onload = function() {
                        window.print();
                        window.close();
                    };
                <\/script>
            </body>
            </html>
        `);

        printWindow.document.close();
    }
</script>

<script>
        function openPopupImage(imageUrl) {
    // Set the src attribute of the modal image to the clicked image's URL
    document.getElementById('popupImage').src = imageUrl;
}
</script>
@push('script_2')
    <script>


        "use strict";
        function print_invoice(order_id) {
            $.get({
                url: '{{url('/')}}/admin/pos/invoice/' + order_id,
                dataType: 'json',
                beforeSend: function () {
                    $('#loading').show();
                },
                success: function (data) {
                    //console.log("success...")
                    $('#print-invoice').modal('show');
                    $('#printableArea').empty().html(data.view);
                },
                complete: function () {
                    $('#loading').hide();
                },
                error: function (error) {
                    console.log(error)
                }
            });
        }
    </script>

    <script src={{asset("public/assets/admin/js/global.js")}}></script>
@endpush
@push('script')
    <script>
        // Ask how much to reverse, cap it at what was collected, then submit
        // that row's hidden form.
        function reverseCollection(id, collected) {
            const raw = prompt('{{ \App\CPU\translate("المبلغ المراد رده") }} (' + collected + ')', collected);
            if (raw === null) return;

            const amount = parseFloat(raw);

            if (isNaN(amount) || amount <= 0) {
                alert('{{ \App\CPU\translate("أدخل مبلغاً صحيحاً") }}');
                return;
            }
            if (amount > collected) {
                alert('{{ \App\CPU\translate("المبلغ أكبر من المحصّل") }} (' + collected + ')');
                return;
            }

            const note = prompt('{{ \App\CPU\translate("سبب الرد (اختياري)") }}', '');

            const form = document.getElementById('reverse-' + id);
            form.querySelector('input[name="amount"]').value = amount;
            form.querySelector('input[name="note"]').value = note || '';
            form.submit();
        }
    </script>
@endpush
