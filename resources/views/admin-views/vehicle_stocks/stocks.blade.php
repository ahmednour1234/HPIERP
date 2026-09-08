@extends('layouts.admin.app')

@section('title', 'تقرير مخزون السيارة')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .vehicle-stock-report {
            background: #f4f8fb;
            min-height: calc(100vh - 70px);
            padding-top: 24px;
            padding-bottom: 32px;
        }
        .vs-report-shell {
            max-width: 1480px;
            margin: 0 auto;
        }
        .vs-hero,
        .vs-card,
        .vs-panel {
            background: #fff;
            border: 1px solid #d9e6f2;
            border-radius: 8px;
            box-shadow: 0 12px 30px rgba(22, 48, 76, .08);
        }
        .vs-hero {
            padding: 18px 20px;
            margin-bottom: 18px;
        }
        .vs-title {
            color: #142f51;
            font-size: 24px;
            font-weight: 800;
            margin: 0;
        }
        .vs-subtitle {
            color: #61758d;
            font-size: 13px;
            margin: 6px 0 0;
        }
        .vs-print-meta {
            display: none;
        }
        .vs-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }
        .vs-card {
            min-height: 116px;
            padding: 16px;
            position: relative;
            overflow: hidden;
        }
        .vs-card::before {
            content: "";
            position: absolute;
            inset-inline-start: 0;
            top: 0;
            width: 5px;
            height: 100%;
            background: #2563eb;
        }
        .vs-card.vs-green::before { background: #16a34a; }
        .vs-card.vs-amber::before { background: #f59e0b; }
        .vs-card.vs-red::before { background: #dc2626; }
        .vs-card.vs-purple::before { background: #7c3aed; }
        .vs-card-label {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .vs-card-value {
            color: #102b4c;
            font-size: 24px;
            font-weight: 800;
            line-height: 1.2;
        }
        .vs-card-note {
            color: #7890a8;
            font-size: 12px;
            margin-top: 8px;
        }
        .vs-info-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }
        .vs-info-item {
            background: #f8fbfe;
            border: 1px solid #e1edf7;
            border-radius: 8px;
            padding: 12px;
        }
        .vs-info-label {
            color: #70849c;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .vs-info-value {
            color: #15345b;
            font-size: 15px;
            font-weight: 800;
            word-break: break-word;
        }
        .vs-panel {
            overflow: hidden;
            margin-top: 18px;
        }
        .vs-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            border-bottom: 1px solid #d9e6f2;
            background: linear-gradient(135deg, #eef6fb 0%, #fff 100%);
        }
        .vs-panel-title {
            color: #17365f;
            font-size: 18px;
            font-weight: 800;
            margin: 0;
        }
        .vs-panel-kicker {
            color: #637992;
            font-size: 12px;
            margin: 4px 0 0;
        }
        .vs-table-wrap {
            overflow: auto;
        }
        .vs-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            min-width: 980px;
            margin: 0;
        }
        .vs-table th,
        .vs-table td {
            border-bottom: 1px solid #dbe7f3;
            border-left: 1px solid #dbe7f3;
            padding: 11px 12px;
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
        }
        .vs-table thead th {
            background: #9ec7e9;
            color: #102f51;
            font-size: 12px;
            font-weight: 800;
            position: sticky;
            top: 0;
            z-index: 2;
        }
        .vs-table tbody tr:nth-child(even) td {
            background: #f7fbff;
        }
        .vs-table tbody tr:hover td {
            background: #eef7ff;
        }
        .vs-product-name {
            color: #15345b;
            font-weight: 800;
            text-align: right !important;
        }
        .vs-muted {
            color: #7a8da3;
            font-size: 12px;
        }
        .vs-progress {
            background: #e8eef5;
            border-radius: 999px;
            height: 8px;
            min-width: 92px;
            overflow: hidden;
        }
        .vs-progress-bar {
            background: #16a34a;
            height: 100%;
        }
        .vs-status {
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 82px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 800;
        }
        .vs-status-good {
            background: #dcfce7;
            color: #166534;
        }
        .vs-status-warning {
            background: #fef3c7;
            color: #92400e;
        }
        .vs-status-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        .vs-empty {
            padding: 42px 16px;
            text-align: center;
        }
        .vs-empty img {
            max-width: 130px;
            margin-bottom: 12px;
        }
        @media (max-width: 991.98px) {
            .vs-info-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .vs-actions {
                justify-content: flex-start;
            }
        }
        @media (max-width: 575.98px) {
            .vehicle-stock-report {
                padding-top: 12px;
            }
            .vs-title {
                font-size: 19px;
            }
            .vs-info-grid {
                grid-template-columns: 1fr;
            }
            .vs-panel-header {
                align-items: flex-start;
                flex-direction: column;
            }
        }
        @media print {
            @page {
                size: A4 landscape;
                margin: 8mm;
            }
            html,
            body {
                background: #fff !important;
                color: #111827 !important;
                height: auto !important;
                margin: 0 !important;
                overflow: visible !important;
                width: auto !important;
            }
            #headerMain,
            #headerFluid,
            #headerDouble,
            #sidebarMain,
            .navbar,
            .navbar-vertical-aside,
            .direction-toggle,
            .footer,
            footer,
            #loading,
            .modal,
            .modal-backdrop,
            .non-printable {
                display: none !important;
            }
            body,
            #content {
                direction: rtl !important;
            }
            #content,
            main#content,
            .main {
                background: #fff !important;
                display: block !important;
                margin: 0 !important;
                max-width: none !important;
                min-height: 0 !important;
                padding: 0 !important;
                position: static !important;
                transform: none !important;
                width: 100% !important;
            }
            .vehicle-stock-report {
                background: #fff !important;
                display: block !important;
                margin: 0 !important;
                max-width: none !important;
                padding: 0 !important;
                width: 100% !important;
            }
            .vs-report-shell {
                max-width: none !important;
                width: 100% !important;
            }
            .vs-hero,
            .vs-panel {
                border: 1px solid #9fb5cb !important;
                border-radius: 4px !important;
                box-shadow: none !important;
                break-inside: avoid;
                margin: 0 0 5mm !important;
                overflow: visible !important;
            }
            .vs-hero {
                background: #17365f !important;
                color: #fff !important;
                padding: 5mm 6mm !important;
            }
            .vs-card::before,
            .vs-panel-kicker,
            .non-printable {
                display: none !important;
            }
            .vs-title {
                color: #fff !important;
                font-size: 18px !important;
                line-height: 1.4 !important;
                margin: 0 !important;
            }
            .vs-subtitle,
            .vs-print-meta {
                color: #e5eef8 !important;
                display: block !important;
                font-size: 10px !important;
                margin: 2px 0 0 !important;
            }
            .vs-print-meta span {
                display: inline-block;
                margin-left: 12px;
            }
            .vs-panel-header {
                background: #eef5fb !important;
                border-bottom: 1px solid #9fb5cb !important;
                padding: 5px 7px !important;
            }
            .vs-panel-title {
                color: #17365f !important;
                font-size: 12px !important;
                margin: 0 !important;
            }
            .vs-info-grid {
                display: grid !important;
                grid-template-columns: repeat(4, 1fr) !important;
                gap: 0 !important;
            }
            .vs-info-item {
                background: #fff !important;
                border: 0 !important;
                border-left: 1px solid #cad8e6 !important;
                border-bottom: 1px solid #cad8e6 !important;
                border-radius: 0 !important;
                padding: 5px 7px !important;
            }
            .vehicle-stock-report .p-3 {
                padding: 0 !important;
            }
            .vehicle-stock-report > .vs-report-shell > .row {
                display: grid !important;
                grid-template-columns: repeat(4, 1fr) !important;
                gap: 3mm !important;
                margin: 0 0 3mm !important;
            }
            .vehicle-stock-report > .vs-report-shell > .row > [class*="col-"] {
                display: block !important;
                max-width: none !important;
                padding: 0 !important;
                width: auto !important;
            }
            .vs-card {
                border: 1px solid #b4c7da !important;
                border-radius: 4px !important;
                box-shadow: none !important;
                min-height: 0 !important;
                padding: 6px 7px !important;
            }
            .vs-card-label,
            .vs-info-label {
                color: #64748b !important;
                font-size: 8px !important;
                margin-bottom: 2px !important;
            }
            .vs-card-value,
            .vs-info-value {
                color: #0f2d4f !important;
                font-size: 10px !important;
                line-height: 1.25 !important;
            }
            .vs-card-note {
                color: #6f849b !important;
                font-size: 8px !important;
                margin-top: 3px !important;
            }
            .vs-table-wrap {
                overflow: visible !important;
            }
            .vs-table {
                border-collapse: collapse !important;
                font-size: 7.2px;
                min-width: 0;
                width: 100% !important;
            }
            .vs-table thead {
                display: table-header-group;
            }
            .vs-table tr {
                page-break-inside: avoid;
            }
            .vs-table th,
            .vs-table td {
                border: 1px solid #9fb5cb !important;
                padding: 2.4px 3px;
                white-space: normal !important;
            }
            .vs-table thead th {
                background: #dcebf7 !important;
                color: #111 !important;
                position: static !important;
            }
            .vs-product-name {
                color: #111827 !important;
                font-weight: 700 !important;
                min-width: 95px;
            }
            .vs-muted,
            .vs-progress {
                display: none !important;
            }
            .vs-status {
                border-radius: 3px !important;
                min-width: 0 !important;
                padding: 1px 4px !important;
                font-size: 7px !important;
            }
            .vs-empty {
                padding: 10px !important;
            }
            .vs-empty img {
                display: none !important;
            }
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $currency = \App\CPU\Helpers::currency_symbol();
        $money = fn ($value) => number_format((float) $value, 2) . ' ' . $currency;
        $qty = fn ($value) => number_format((float) $value, 0);
    @endphp

    <div class="content container-fluid vehicle-stock-report" dir="rtl">
        <div class="vs-report-shell">
            <div class="vs-hero">
                <div class="row align-items-center">
                    <div class="col-lg-8 mb-3 mb-lg-0">
                        <h1 class="vs-title">تقرير مخزون السيارة والمبيعات المتبقية</h1>
                        <p class="vs-subtitle">
                            ملخص حركة المخزون مع المندوب حتى {{ now()->format('Y-m-d H:i') }}
                        </p>
                        <div class="vs-print-meta">
                            <span>المندوب: {{ $summary['seller_name'] ?: '-' }}</span>
                            <span>كود المندوب: {{ $summary['seller_code'] ?: '-' }}</span>
                            <span>السيارة: {{ $summary['vehicle_code'] ?: '-' }}</span>
                        </div>
                    </div>
                    <div class="col-lg-4 non-printable">
                        <div class="vs-actions">
                            <a href="{{ route('admin.stock.vehicles') }}" class="btn btn-outline-secondary">
                                <i class="tio-arrow-backward"></i> رجوع
                            </a>
                            <button type="button" class="btn btn-primary" onclick="window.print()">
                                <i class="tio-print"></i> طباعة
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="vs-panel">
                <div class="vs-panel-header">
                    <div>
                        <h2 class="vs-panel-title">بيانات المندوب والسيارة</h2>
                        <p class="vs-panel-kicker">بيانات التعريف الأساسية الخاصة بالمخزون الحالي.</p>
                    </div>
                </div>
                <div class="p-3">
                    <div class="vs-info-grid">
                        <div class="vs-info-item">
                            <div class="vs-info-label">اسم المندوب</div>
                            <div class="vs-info-value">{{ $summary['seller_name'] ?: '-' }}</div>
                        </div>
                        <div class="vs-info-item">
                            <div class="vs-info-label">كود المندوب</div>
                            <div class="vs-info-value">{{ $summary['seller_code'] ?: '-' }}</div>
                        </div>
                        <div class="vs-info-item">
                            <div class="vs-info-label">كود السيارة</div>
                            <div class="vs-info-value">{{ $summary['vehicle_code'] ?: '-' }}</div>
                        </div>
                        <div class="vs-info-item">
                            <div class="vs-info-label">اسم السيارة</div>
                            <div class="vs-info-value">{{ $summary['vehicle_name'] ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-6 col-xl-3 mb-3">
                    <div class="vs-card">
                        <div class="vs-card-label">إجمالي الكمية المصروفة</div>
                        <div class="vs-card-value">{{ $qty($summary['issued_qty']) }}</div>
                        <div class="vs-card-note">كل الكميات المحملة على السيارة</div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3 mb-3">
                    <div class="vs-card vs-green">
                        <div class="vs-card-label">إجمالي الكمية المباعة</div>
                        <div class="vs-card-value">{{ $qty($summary['sold_qty']) }}</div>
                        <div class="vs-card-note">نسبة البيع {{ $summary['sell_through_percent'] }}%</div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3 mb-3">
                    <div class="vs-card vs-amber">
                        <div class="vs-card-label">إجمالي الكمية المتبقية</div>
                        <div class="vs-card-value">{{ $qty($summary['remaining_qty']) }}</div>
                        <div class="vs-card-note">قيمة تقريبية: {{ $money($summary['remaining_value']) }}</div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3 mb-3">
                    <div class="vs-card vs-purple">
                        <div class="vs-card-label">عدد الفواتير</div>
                        <div class="vs-card-value">{{ $qty($summary['orders_count']) }}</div>
                        <div class="vs-card-note">المنتجات التي انتهت: {{ $qty($summary['sold_out_count']) }}</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-6 col-xl-3 mb-3">
                    <div class="vs-card vs-green">
                        <div class="vs-card-label">مبيعات نقدية</div>
                        <div class="vs-card-value">{{ $money($summary['cash_sales']) }}</div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3 mb-3">
                    <div class="vs-card">
                        <div class="vs-card-label">مبيعات آجلة</div>
                        <div class="vs-card-value">{{ $money($summary['credit_sales']) }}</div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3 mb-3">
                    <div class="vs-card vs-red">
                        <div class="vs-card-label">مرتجعات</div>
                        <div class="vs-card-value">{{ $money($summary['refund_sales']) }}</div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3 mb-3">
                    <div class="vs-card vs-amber">
                        <div class="vs-card-label">أقساط</div>
                        <div class="vs-card-value">{{ $money($summary['installments']) }}</div>
                    </div>
                </div>
            </div>

            <div class="vs-panel">
                <div class="vs-panel-header">
                    <div>
                        <h2 class="vs-panel-title">المنتجات التي تم البيع منها</h2>
                        <p class="vs-panel-kicker">الكميات المصروفة والمباعة والمتبقية وقيمتها حسب سعر البيع الحالي.</p>
                    </div>
                    <span class="badge badge-soft-primary">{{ $stocks->count() }} منتج</span>
                </div>
                <div class="vs-table-wrap">
                    <table class="vs-table">
                        <thead>
                            <tr>
                                <th>م</th>
                                <th>المنتج</th>
                                <th>كود المنتج</th>
                                <th>المصروف</th>
                                <th>المباع</th>
                                <th>المتبقي</th>
                                <th>سعر البيع</th>
                                <th>قيمة المباع</th>
                                <th>قيمة المتبقي</th>
                                <th>نسبة البيع</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stocks as $stock)
                                @php
                                    $issued = (float) $stock->main_stock;
                                    $remaining = (float) $stock->stock;
                                    $sold = max($issued - $remaining, 0);
                                    $price = (float) optional($stock->product)->selling_price;
                                    $percent = $issued > 0 ? round(($sold / $issued) * 100, 1) : 0;
                                    $statusClass = $remaining <= 0 ? 'vs-status-danger' : ($percent >= 70 ? 'vs-status-good' : 'vs-status-warning');
                                    $statusText = $remaining <= 0 ? 'نفد' : ($percent >= 70 ? 'جيد' : 'متبقي');
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="vs-product-name">
                                        {{ optional($stock->product)->name ?? '-' }}
                                        @if(optional($stock->product)->name_en)
                                            <div class="vs-muted">{{ $stock->product->name_en }}</div>
                                        @endif
                                    </td>
                                    <td>{{ optional($stock->product)->product_code ?? '-' }}</td>
                                    <td>{{ $qty($issued) }}</td>
                                    <td>{{ $qty($sold) }}</td>
                                    <td>{{ $qty($remaining) }}</td>
                                    <td>{{ $money($price) }}</td>
                                    <td>{{ $money($sold * $price) }}</td>
                                    <td>{{ $money($remaining * $price) }}</td>
                                    <td>
                                        <div class="d-flex align-items-center justify-content-center">
                                            <span class="ml-2">{{ $percent }}%</span>
                                            <div class="vs-progress">
                                                <div class="vs-progress-bar" style="width: {{ min($percent, 100) }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="vs-status {{ $statusClass }}">{{ $statusText }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11">
                                        <div class="vs-empty">
                                            <img src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}" alt="">
                                            <div>لا توجد منتجات تم البيع منها لهذا المندوب.</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="vs-panel">
                <div class="vs-panel-header">
                    <div>
                        <h2 class="vs-panel-title">منتجات لم يتم البيع منها</h2>
                        <p class="vs-panel-kicker">منتجات ما زالت كميتها على السيارة كما تم صرفها.</p>
                    </div>
                    <span class="badge badge-soft-secondary">{{ $remain_stocks->count() }} منتج</span>
                </div>
                <div class="vs-table-wrap">
                    <table class="vs-table">
                        <thead>
                            <tr>
                                <th>م</th>
                                <th>المنتج</th>
                                <th>كود المنتج</th>
                                <th>الكمية على السيارة</th>
                                <th>سعر البيع</th>
                                <th>قيمة الرصيد</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($remain_stocks as $stock)
                                @php
                                    $price = (float) optional($stock->product)->selling_price;
                                    $remaining = (float) $stock->stock;
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="vs-product-name">
                                        {{ optional($stock->product)->name ?? '-' }}
                                        @if(optional($stock->product)->name_en)
                                            <div class="vs-muted">{{ $stock->product->name_en }}</div>
                                        @endif
                                    </td>
                                    <td>{{ optional($stock->product)->product_code ?? '-' }}</td>
                                    <td>{{ $qty($remaining) }}</td>
                                    <td>{{ $money($price) }}</td>
                                    <td>{{ $money($remaining * $price) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="vs-empty">
                                            <img src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}" alt="">
                                            <div>لا توجد منتجات كاملة الرصيد بدون مبيعات.</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin/js/global.js') }}"></script>
@endpush
