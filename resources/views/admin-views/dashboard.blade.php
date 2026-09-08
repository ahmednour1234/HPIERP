@extends('layouts.admin.app')

@section('title', 'لوحة التحكم')

@push('css_or_js')
<style>
    .dashboard-page {
        direction: rtl;
        background:
            linear-gradient(135deg, rgba(15, 23, 42, .05), rgba(20, 184, 166, .08)),
            #f4f8fb;
        min-height: calc(100vh - 4rem);
        padding-top: 2rem;
        padding-bottom: 2.5rem;
        color: #102a43;
    }

    .dashboard-shell {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .dashboard-hero {
        display: flex;
        align-items: stretch;
        justify-content: space-between;
        gap: 1rem;
        border-radius: 8px;
        padding: 1.5rem;
        color: #fff;
        background:
            linear-gradient(135deg, rgba(12, 18, 64, .96), rgba(15, 98, 146, .92)),
            #111857;
        box-shadow: 0 18px 45px rgba(17, 24, 86, .16);
        overflow: hidden;
        position: relative;
    }

    .dashboard-hero::before {
        content: "";
        position: absolute;
        inset: auto -5rem -7rem auto;
        width: 18rem;
        height: 18rem;
        border-radius: 50%;
        background: rgba(20, 184, 166, .22);
    }

    .dashboard-hero-content,
    .dashboard-hero-meta {
        position: relative;
        z-index: 1;
    }

    .dashboard-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        color: #c7fff6;
        font-weight: 800;
        font-size: .82rem;
        margin-bottom: .55rem;
    }

    .dashboard-hero h1 {
        font-size: 2rem;
        line-height: 1.25;
        margin: 0 0 .45rem;
        color: #fff;
        font-weight: 900;
    }

    .dashboard-hero p {
        max-width: 40rem;
        margin: 0;
        color: rgba(255, 255, 255, .78);
        font-weight: 600;
    }

    .dashboard-hero-meta {
        min-width: 16rem;
        border: 1px solid rgba(255, 255, 255, .2);
        background: rgba(255, 255, 255, .1);
        border-radius: 8px;
        padding: 1rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: .25rem;
    }

    .dashboard-hero-meta span,
    .dashboard-hero-meta small {
        color: rgba(255, 255, 255, .72);
        font-weight: 700;
    }

    .dashboard-hero-meta strong {
        color: #fff;
        font-size: 1.25rem;
        font-weight: 900;
    }

    .dashboard-kpi-grid,
    .dashboard-metrics-grid,
    .dashboard-quick-grid,
    .dashboard-seller-grid {
        display: grid;
        gap: 1rem;
    }

    .dashboard-kpi-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .dashboard-kpi-card,
    .dashboard-panel,
    .dashboard-metric-card,
    .dashboard-seller-card {
        border: 1px solid #d9e6f2;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .07);
    }

    .dashboard-kpi-card {
        min-height: 9.25rem;
        padding: 1.15rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
    }

    .dashboard-kpi-card::after {
        content: "";
        position: absolute;
        inset: auto auto -3.5rem -3.5rem;
        width: 8rem;
        height: 8rem;
        border-radius: 50%;
        background: currentColor;
        opacity: .09;
    }

    .dashboard-kpi-card--teal { color: #0f766e; }
    .dashboard-kpi-card--blue { color: #2563eb; }
    .dashboard-kpi-card--amber { color: #b45309; }
    .dashboard-kpi-card--rose { color: #be123c; }

    .dashboard-kpi-icon,
    .dashboard-metric-icon {
        width: 3rem;
        height: 3rem;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: currentColor;
        background: #f3f8ff;
        font-size: 1.35rem;
    }

    .dashboard-kpi-label,
    .dashboard-metric-label {
        color: #60758b;
        font-size: .86rem;
        font-weight: 800;
    }

    .dashboard-kpi-value {
        color: #102a43;
        font-size: 2rem;
        line-height: 1;
        font-weight: 900;
        margin-top: .35rem;
    }

    .dashboard-panel {
        overflow: hidden;
    }

    .dashboard-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.1rem 1.25rem;
        border-bottom: 1px solid #e6edf5;
        background: linear-gradient(90deg, #f8fbff, #eef7fb);
    }

    .dashboard-panel-title {
        display: flex;
        align-items: center;
        gap: .65rem;
        margin: 0;
        color: #132f52;
        font-size: 1.2rem;
        font-weight: 900;
    }

    .dashboard-title-icon {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #11245a;
        color: #fff;
    }

    .dashboard-panel-body {
        padding: 1.25rem;
    }

    .dashboard-version {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        border-radius: 999px;
        padding: .45rem .75rem;
        color: #075e54;
        background: #dcfce7;
        font-weight: 900;
        white-space: nowrap;
    }

    .dashboard-filter-form {
        display: grid;
        grid-template-columns: minmax(16rem, 24rem) 1fr;
        align-items: end;
        gap: .85rem;
        margin-bottom: 1.1rem;
    }

    .dashboard-field label {
        display: block;
        color: #52677f;
        font-weight: 800;
        margin-bottom: .4rem;
    }

    .dashboard-control {
        height: 3rem;
        border: 1px solid #d5e2ef;
        border-radius: 8px;
        background: #fff;
        color: #102a43;
        font-weight: 800;
        box-shadow: none;
    }

    .dashboard-custom-dates {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr)) auto;
        gap: .75rem;
    }

    .dashboard-btn {
        min-height: 3rem;
        border: 0;
        border-radius: 8px;
        padding: .75rem 1rem;
        background: #11245a;
        color: #fff;
        font-weight: 900;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .45rem;
        transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
    }

    .dashboard-btn:hover {
        color: #fff;
        background: #173174;
        transform: translateY(-1px);
        box-shadow: 0 12px 22px rgba(17, 36, 90, .2);
    }

    .dashboard-metrics-grid {
        grid-template-columns: repeat(6, minmax(0, 1fr));
    }

    .dashboard-metric-card {
        min-height: 9rem;
        padding: 1rem;
        color: var(--metric-color);
        border-top: 4px solid var(--metric-color);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        overflow: hidden;
    }

    .dashboard-metric-card--wide {
        grid-column: span 3;
    }

    .dashboard-metric-card--half {
        grid-column: span 2;
    }

    .dashboard-metric-card--navy { --metric-color: #11245a; }
    .dashboard-metric-card--green { --metric-color: #0f9f6e; }
    .dashboard-metric-card--amber { --metric-color: #d97706; }
    .dashboard-metric-card--cyan { --metric-color: #0891b2; }
    .dashboard-metric-card--rose { --metric-color: #e11d48; }
    .dashboard-metric-card--violet { --metric-color: #7c3aed; }

    .dashboard-metric-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .75rem;
    }

    .dashboard-metric-value {
        color: #102a43;
        font-size: 1.65rem;
        line-height: 1.2;
        font-weight: 900;
        margin-top: .55rem;
        word-break: break-word;
    }

    .dashboard-metric-foot {
        color: #7c8ea3;
        font-size: .78rem;
        font-weight: 800;
        margin-top: .8rem;
    }

    .dashboard-charts-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }

    .dashboard-chart-card {
        padding: 1rem;
    }

    .dashboard-chart-title {
        color: #132f52;
        font-size: 1rem;
        font-weight: 900;
        margin: 0 0 .8rem;
    }

    .dashboard-chart-box {
        position: relative;
        width: 100%;
        height: 20rem;
    }

    .dashboard-quick-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .dashboard-table {
        margin: 0;
        color: #506882;
    }

    .dashboard-table thead th {
        border: 0;
        background: #11245a;
        color: #fff;
        font-weight: 900;
        padding: .95rem;
        white-space: nowrap;
    }

    .dashboard-table tbody td {
        border-top: 1px solid #e7eef6;
        padding: .9rem .95rem;
        vertical-align: middle;
        font-weight: 700;
    }

    .dashboard-table tbody tr:hover {
        background: #f8fbff;
    }

    .dashboard-link {
        color: #11245a;
        font-weight: 900;
        text-decoration: none;
    }

    .dashboard-link:hover {
        color: #0f766e;
        text-decoration: none;
    }

    .dashboard-empty {
        padding: 2.5rem 1rem;
        color: #7c8ea3;
        text-align: center;
        font-weight: 800;
    }

    .dashboard-seller-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .dashboard-seller-card {
        display: block;
        height: 100%;
        padding: 1rem;
        color: inherit;
        text-decoration: none;
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }

    .dashboard-seller-card:hover {
        color: inherit;
        text-decoration: none;
        transform: translateY(-2px);
        border-color: #9fc5ff;
        box-shadow: 0 16px 35px rgba(17, 36, 90, .12);
    }

    .dashboard-seller-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .75rem;
        margin-bottom: 1rem;
    }

    .dashboard-seller-name {
        color: #132f52;
        margin: 0 0 .25rem;
        font-size: 1rem;
        font-weight: 900;
    }

    .dashboard-seller-code {
        color: #71869c;
        font-size: .78rem;
        font-weight: 800;
    }

    .dashboard-seller-badge {
        border-radius: 999px;
        padding: .4rem .65rem;
        background: #e0f2fe;
        color: #075985;
        font-weight: 900;
        white-space: nowrap;
    }

    .dashboard-mini-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .65rem;
        margin-bottom: .9rem;
    }

    .dashboard-mini-stat {
        border-radius: 8px;
        background: #f5f8fc;
        padding: .75rem;
        min-height: 4.4rem;
    }

    .dashboard-mini-stat strong {
        display: block;
        color: #102a43;
        font-size: 1rem;
        font-weight: 900;
        margin-bottom: .2rem;
        word-break: break-word;
    }

    .dashboard-mini-stat span {
        color: #6b7f95;
        font-size: .74rem;
        font-weight: 800;
    }

    .dashboard-progress-group {
        display: grid;
        gap: .7rem;
        margin-top: .9rem;
    }

    .dashboard-progress-label {
        display: flex;
        justify-content: space-between;
        gap: .75rem;
        color: #52677f;
        font-size: .78rem;
        font-weight: 900;
        margin-bottom: .3rem;
    }

    .dashboard-progress {
        height: .45rem;
        border-radius: 999px;
        background: #e7eef6;
        overflow: hidden;
    }

    .dashboard-progress span {
        display: block;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #14b8a6, #2563eb);
    }

    .dashboard-status-row {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
        margin-top: 1rem;
    }

    .dashboard-status-pill {
        border-radius: 999px;
        padding: .38rem .55rem;
        background: #eef4fb;
        color: #52677f;
        font-size: .74rem;
        font-weight: 900;
    }

    .dashboard-status-pill--success { background: #dcfce7; color: #166534; }
    .dashboard-status-pill--warning { background: #fef3c7; color: #92400e; }
    .dashboard-status-pill--danger { background: #ffe4e6; color: #be123c; }
    .dashboard-status-pill--info { background: #e0f2fe; color: #075985; }

    @media (max-width: 1199.98px) {
        .dashboard-kpi-grid,
        .dashboard-seller-grid,
        .dashboard-charts-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .dashboard-metrics-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .dashboard-metric-card--wide,
        .dashboard-metric-card--half {
            grid-column: span 1;
        }
    }

    @media (max-width: 767.98px) {
        .dashboard-page {
            padding-top: 1rem;
        }

        .dashboard-hero,
        .dashboard-panel-header {
            flex-direction: column;
            align-items: stretch;
        }

        .dashboard-hero-meta {
            min-width: 0;
        }

        .dashboard-kpi-grid,
        .dashboard-metrics-grid,
        .dashboard-quick-grid,
        .dashboard-seller-grid,
        .dashboard-charts-grid,
        .dashboard-filter-form,
        .dashboard-custom-dates,
        .dashboard-mini-grid {
            grid-template-columns: 1fr;
        }

        .dashboard-hero h1 {
            font-size: 1.65rem;
        }

        .dashboard-chart-box {
            height: 17rem;
        }
    }
</style>
@endpush

@section('content')
@php
    use App\Models\Customer;

    $counts = [
        'pharmacy' => Customer::where('specialist', 1)->count(),
        'medical_center' => Customer::where('specialist', 2)->count(),
        'hospital' => Customer::where('specialist', 3)->count(),
        'doctor' => Customer::whereIn('specialist', [4, 0])->count(),
    ];

    $statisticsLabels = [
        'overall' => 'الإحصاءات الكلية',
        'today' => 'إحصائيات اليوم',
        'month' => 'إحصائيات هذا الشهر',
        'year' => 'إحصائيات هذه السنة',
        'quarter1' => 'إحصائيات الربع الأول',
        'quarter2' => 'إحصائيات الربع الثاني',
        'quarter3' => 'إحصائيات الربع الثالث',
        'quarter4' => 'إحصائيات الربع الرابع',
        'custom' => 'نطاق مخصص',
    ];

    $currentStatisticsType = request('statistics_type', 'overall');
    $selectedStatisticsLabel = $statisticsLabels[$currentStatisticsType] ?? $statisticsLabels['overall'];
    $currencySymbol = \App\CPU\Helpers::currency_symbol();
    $visibleSellerCount = collect($sellers)->filter(fn ($seller) => isset($sellerStats[$seller->id]) && $sellerStats[$seller->id]['has_stock'])->count();
@endphp

<div class="content container-fluid dashboard-page">
    <div class="dashboard-shell">
        <section class="dashboard-hero">
            <div class="dashboard-hero-content">
                <span class="dashboard-eyebrow">
                    <i class="tio-dashboard-vs"></i>
                    نظام الإدارة
                </span>
                <h1>لوحة التحكم</h1>
                <p>ملخص سريع للمبيعات، التحصيلات، العملاء، المخزون، وحركة المناديب في شاشة واحدة مرتبة.</p>
            </div>
            <div class="dashboard-hero-meta">
                <span>الفترة الحالية</span>
                <strong>{{ $selectedStatisticsLabel }}</strong>
                <small>{{ now()->format('Y-m-d') }}</small>
            </div>
        </section>

        <section class="dashboard-kpi-grid">
            <div class="dashboard-kpi-card dashboard-kpi-card--teal">
                <span class="dashboard-kpi-icon"><i class="tio-pharmacy"></i></span>
                <div>
                    <div class="dashboard-kpi-label">الصيدليات</div>
                    <div class="dashboard-kpi-value">{{ number_format($counts['pharmacy']) }}</div>
                </div>
            </div>
            <div class="dashboard-kpi-card dashboard-kpi-card--blue">
                <span class="dashboard-kpi-icon"><i class="tio-clinic"></i></span>
                <div>
                    <div class="dashboard-kpi-label">المراكز الطبية</div>
                    <div class="dashboard-kpi-value">{{ number_format($counts['medical_center']) }}</div>
                </div>
            </div>
            <div class="dashboard-kpi-card dashboard-kpi-card--amber">
                <span class="dashboard-kpi-icon"><i class="tio-hospital"></i></span>
                <div>
                    <div class="dashboard-kpi-label">المستشفيات</div>
                    <div class="dashboard-kpi-value">{{ number_format($counts['hospital']) }}</div>
                </div>
            </div>
            <div class="dashboard-kpi-card dashboard-kpi-card--rose">
                <span class="dashboard-kpi-icon"><i class="tio-face-male"></i></span>
                <div>
                    <div class="dashboard-kpi-label">الأطباء</div>
                    <div class="dashboard-kpi-value">{{ number_format($counts['doctor']) }}</div>
                </div>
            </div>
        </section>

        <section class="dashboard-panel">
            <div class="dashboard-panel-header">
                <h2 class="dashboard-panel-title">
                    <span class="dashboard-title-icon"><i class="tio-chart-bar-4"></i></span>
                    إحصائيات النشاط
                </h2>
                <span class="dashboard-version">
                    <i class="tio-verified"></i>
                    إصدار البرنامج: {{ env('SOFTWARE_VERSION') }}
                </span>
            </div>
            <div class="dashboard-panel-body">
                <form id="statsFilterForm" action="{{ url()->current() }}" method="GET" class="dashboard-filter-form">
                    <div class="dashboard-field">
                        <label for="statistics_type">نوع الإحصائيات</label>
                        <select id="statistics_type" name="statistics_type" class="form-control dashboard-control" onchange="updateDatesAndSubmit(this.value)">
                            @foreach($statisticsLabels as $value => $label)
                                <option value="{{ $value }}" {{ $currentStatisticsType === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="custom_dates" class="dashboard-custom-dates" style="display: {{ $currentStatisticsType === 'custom' ? 'grid' : 'none' }};">
                        <div class="dashboard-field">
                            <label for="from_date">من تاريخ</label>
                            <input type="date" name="from_date" id="from_date" class="form-control dashboard-control" value="{{ request('from_date') }}">
                        </div>
                        <div class="dashboard-field">
                            <label for="to_date">إلى تاريخ</label>
                            <input type="date" name="to_date" id="to_date" class="form-control dashboard-control" value="{{ request('to_date') }}">
                        </div>
                        <button class="dashboard-btn" type="submit">
                            <i class="tio-filter-list"></i>
                            تطبيق
                        </button>
                    </div>
                </form>

                <div id="account_stats">
                    @include('admin-views.partials._dashboard-balance-stats', ['account' => $account])
                </div>
            </div>
        </section>

        <section class="dashboard-quick-grid">
            <div class="dashboard-panel">
                <div class="dashboard-panel-header">
                    <h2 class="dashboard-panel-title">
                        <span class="dashboard-title-icon"><i class="tio-wallet"></i></span>
                        الحسابات
                    </h2>
                    <a href="{{ route('admin.account.list') }}" class="dashboard-link">عرض الكل</a>
                </div>
                <div class="table-responsive">
                    <table class="table dashboard-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الحساب</th>
                                <th class="text-left">الرصيد</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($accounts as $dashboardAccount)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><a href="{{ route('admin.account.list') }}" class="dashboard-link">{{ $dashboardAccount->account }}</a></td>
                                    <td class="text-left">{{ number_format($dashboardAccount->balance, 2) }} {{ $currencySymbol }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="dashboard-empty">لا توجد بيانات لعرضها</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="dashboard-panel">
                <div class="dashboard-panel-header">
                    <h2 class="dashboard-panel-title">
                        <span class="dashboard-title-icon"><i class="tio-archive"></i></span>
                        مخزون محدود
                    </h2>
                    <a href="{{ route('admin.stock.stock-limit') }}" class="dashboard-link">عرض الكل</a>
                </div>
                <div class="table-responsive">
                    <table class="table dashboard-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>المنتج</th>
                                <th class="text-left">الكمية</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><a href="{{ route('admin.stock.stock-limit') }}" class="dashboard-link">{{ \Illuminate\Support\Str::limit($product->name, 50) }}</a></td>
                                    <td class="text-left">{{ number_format($product->quantity) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="dashboard-empty">لا توجد بيانات لعرضها</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="dashboard-panel">
            <div class="dashboard-panel-header">
                <h2 class="dashboard-panel-title">
                    <span class="dashboard-title-icon"><i class="tio-car"></i></span>
                    الرحلات الحالية
                </h2>
                <span class="dashboard-version">{{ $visibleSellerCount }} مندوب نشط</span>
            </div>
            <div class="dashboard-panel-body">
                <div class="dashboard-seller-grid">
                    @if($visibleSellerCount === 0)
                        <div class="dashboard-empty">لا توجد رحلات لعرضها</div>
                    @else
                    @foreach($sellers as $seller)
                        @php
                            $stat = $sellerStats[$seller->id] ?? null;
                        @endphp

                        @if(!$stat || !$stat['has_stock'])
                            @continue
                        @endif

                        @php
                            $remain_stock = $stat['remain_stock'];
                            $order_count = $stat['order_count'];
                            $total_cash = $stat['total_cash'];
                            $total_credit = $stat['total_credit'];
                            $refund_total = $stat['refund_total'];
                            $orderAmountType4 = $stat['amount_type_4'];
                            $orderAmountType7 = $stat['amount_type_7'];
                            $transactionRefType4 = $stat['paid_type_4'];
                            $amountDue = $stat['amount_due'];
                            $productCount = $stat['product_count'];
                            $quantitySum = $stat['quantity_sum'];
                            $priceSum = $stat['price_sum'];
                            $collectedUnits = $stat['collected_units'];
                            $invoiceStatusCounts = $stat['status_counts'];
                            $paidInvoices = $stat['collected_receipts'];
                            $unpaidInvoices = $stat['uncollected_receipts'];
                            $stockLineCount = (int) $stat['stock_line_count'];
                            $collectionPercent = $orderAmountType4 > 0 ? min(100, round(($transactionRefType4 / $orderAmountType4) * 100)) : 0;
                            $visitPercent = $seller->visitors > 0 ? min(100, round(($seller->result_visitors / $seller->visitors) * 100)) : 0;
                        @endphp

                        <a href="{{ route('admin.stock.products', $seller->id) }}" class="dashboard-seller-card">
                            <div class="dashboard-seller-head">
                                <div>
                                    <h3 class="dashboard-seller-name">{{ $seller->f_name }} {{ $seller->l_name }}</h3>
                                    <div class="dashboard-seller-code">كود المندوب: {{ $seller->mandob_code }}</div>
                                </div>
                                <span class="dashboard-seller-badge">{{ $stockLineCount }} صنف</span>
                            </div>

                            <div class="dashboard-mini-grid">
                                <div class="dashboard-mini-stat">
                                    <strong>{{ number_format($order_count) }}</strong>
                                    <span>الطلبات</span>
                                </div>
                                <div class="dashboard-mini-stat">
                                    <strong>{{ number_format($remain_stock) }}</strong>
                                    <span>الكمية المتبقية</span>
                                </div>
                                <div class="dashboard-mini-stat">
                                    <strong>{{ number_format($productCount) }}</strong>
                                    <span>منتجات مباعة</span>
                                </div>
                                <div class="dashboard-mini-stat">
                                    <strong>{{ number_format($total_cash, 2) }}</strong>
                                    <span>مبيعات نقدية</span>
                                </div>
                                <div class="dashboard-mini-stat">
                                    <strong>{{ number_format($total_credit, 2) }}</strong>
                                    <span>مبيعات آجلة</span>
                                </div>
                                <div class="dashboard-mini-stat">
                                    <strong>{{ number_format($refund_total, 2) }}</strong>
                                    <span>مرتجعات</span>
                                </div>
                            </div>

                            <div class="dashboard-progress-group">
                                <div>
                                    <div class="dashboard-progress-label">
                                        <span>نسبة التحصيل</span>
                                        <span>{{ $collectionPercent }}%</span>
                                    </div>
                                    <div class="dashboard-progress"><span style="width: {{ $collectionPercent }}%"></span></div>
                                </div>
                                <div>
                                    <div class="dashboard-progress-label">
                                        <span>تنفيذ الزيارات</span>
                                        <span>{{ $visitPercent }}%</span>
                                    </div>
                                    <div class="dashboard-progress"><span style="width: {{ $visitPercent }}%"></span></div>
                                </div>
                            </div>

                            <div class="dashboard-mini-grid mt-3">
                                <div class="dashboard-mini-stat">
                                    <strong>{{ number_format($orderAmountType4, 2) }}</strong>
                                    <span>إجمالي البيع</span>
                                </div>
                                <div class="dashboard-mini-stat">
                                    <strong>{{ number_format($transactionRefType4, 2) }}</strong>
                                    <span>المحصل</span>
                                </div>
                                <div class="dashboard-mini-stat">
                                    <strong>{{ number_format($amountDue, 2) }}</strong>
                                    <span>المتبقي</span>
                                </div>
                                <div class="dashboard-mini-stat">
                                    <strong>{{ number_format($orderAmountType7, 2) }}</strong>
                                    <span>مبالغ المرتجع</span>
                                </div>
                                <div class="dashboard-mini-stat">
                                    <strong>{{ number_format($quantitySum) }}</strong>
                                    <span>كميات مباعة</span>
                                </div>
                                <div class="dashboard-mini-stat">
                                    <strong>{{ number_format($collectedUnits) }}</strong>
                                    <span>كميات محصلة</span>
                                </div>
                            </div>

                            <div class="dashboard-status-row">
                                <span class="dashboard-status-pill dashboard-status-pill--success">محصلة: {{ $invoiceStatusCounts['paid'] ?? 0 }}</span>
                                <span class="dashboard-status-pill">غير محصلة: {{ $invoiceStatusCounts['unpaid'] ?? 0 }}</span>
                                <span class="dashboard-status-pill dashboard-status-pill--warning">إرجاع كامل: {{ $invoiceStatusCounts['returned_fully'] ?? 0 }}</span>
                                <span class="dashboard-status-pill dashboard-status-pill--info">تحصيل جزئي: {{ $invoiceStatusCounts['partial_paid'] ?? 0 }}</span>
                                <span class="dashboard-status-pill dashboard-status-pill--danger">إرجاع جزئي: {{ $invoiceStatusCounts['partial_returned'] ?? 0 }}</span>
                                <span class="dashboard-status-pill dashboard-status-pill--info">جزئي + جزئي: {{ $invoiceStatusCounts['partial_both'] ?? 0 }}</span>
                                <span class="dashboard-status-pill">إيصالات محصلة: {{ $paidInvoices }}</span>
                                <span class="dashboard-status-pill">إيصالات غير محصلة: {{ $unpaidInvoices }}</span>
                                <span class="dashboard-status-pill">قيمة المنتجات: {{ number_format($priceSum, 2) }}</span>
                            </div>
                        </a>
                    @endforeach
                    @endif
                </div>
            </div>
        </section>
    </div>
</div>
@endsection

@push('script_2')
<script src="{{ asset('public/assets/admin/vendor/chart.js/dist/Chart.min.js') }}"></script>
<script>
    function updateDatesAndSubmit(type) {
        const year = new Date().getFullYear();
        const form = document.getElementById('statsFilterForm');
        const fromInput = document.getElementById('from_date');
        const toInput = document.getElementById('to_date');
        const customDiv = document.getElementById('custom_dates');
        const quarters = {
            quarter1: [`${year}-01-01`, `${year}-03-31`],
            quarter2: [`${year}-04-01`, `${year}-06-30`],
            quarter3: [`${year}-07-01`, `${year}-09-30`],
            quarter4: [`${year}-10-01`, `${year}-12-31`]
        };

        if (quarters[type]) {
            fromInput.value = quarters[type][0];
            toInput.value = quarters[type][1];
            customDiv.style.display = 'grid';
            form.submit();
            return;
        }

        if (type === 'custom') {
            customDiv.style.display = 'grid';
            return;
        }

        customDiv.style.display = 'none';
        fromInput.value = '';
        toInput.value = '';
        form.submit();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const monthLabels = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
        const cashData = @json(array_values($monthly_income));
        const creditData = @json(array_values($monthly_expense));
        const requestedVisitors = @json(array_values($monthly_visitors));
        const executedVisitors = @json(array_values($monthly_result_visitor));
        const installmentData = @json(array_values($monthly_installments));

        Chart.defaults.global.defaultFontFamily = "'Cairo', 'Tahoma', sans-serif";
        Chart.defaults.global.defaultFontColor = '#52677f';

        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                position: 'top',
                align: 'start',
                labels: {
                    usePointStyle: true,
                    boxWidth: 8,
                    padding: 18,
                    fontStyle: 'bold'
                }
            },
            tooltips: {
                mode: 'index',
                intersect: false,
                backgroundColor: '#102a43',
                titleFontStyle: 'bold',
                bodyFontStyle: 'bold',
                xPadding: 12,
                yPadding: 12,
                cornerRadius: 6
            },
            scales: {
                xAxes: [{
                    gridLines: { display: false },
                    ticks: { reverse: true, fontStyle: 'bold' }
                }],
                yAxes: [{
                    ticks: { beginAtZero: true, fontStyle: 'bold' },
                    gridLines: { color: 'rgba(82, 103, 127, .12)', zeroLineColor: 'rgba(82, 103, 127, .16)' }
                }]
            }
        };

        const salesCanvas = document.getElementById('salesMixedChart');
        if (salesCanvas) {
            new Chart(salesCanvas, {
                type: 'bar',
                data: {
                    labels: monthLabels,
                    datasets: [
                        {
                            type: 'bar',
                            label: 'نقدي',
                            data: cashData,
                            backgroundColor: 'rgba(20, 184, 166, .78)',
                            borderColor: '#0f766e',
                            borderWidth: 1,
                            barPercentage: .56
                        },
                        {
                            type: 'line',
                            label: 'آجل',
                            data: creditData,
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, .12)',
                            borderWidth: 3,
                            pointRadius: 4,
                            pointBackgroundColor: '#2563eb',
                            lineTension: .32,
                            fill: true
                        }
                    ]
                },
                options: commonOptions
            });
        }

        const visitorsCanvas = document.getElementById('visitorsComparisonChart');
        if (visitorsCanvas) {
            new Chart(visitorsCanvas, {
                type: 'bar',
                data: {
                    labels: monthLabels,
                    datasets: [
                        {
                            label: 'الزيارات المطلوبة',
                            data: requestedVisitors,
                            backgroundColor: 'rgba(245, 158, 11, .78)',
                            borderColor: '#d97706',
                            borderWidth: 1,
                            barPercentage: .48
                        },
                        {
                            label: 'الزيارات المنفذة',
                            data: executedVisitors,
                            backgroundColor: 'rgba(15, 159, 110, .78)',
                            borderColor: '#0f9f6e',
                            borderWidth: 1,
                            barPercentage: .48
                        }
                    ]
                },
                options: commonOptions
            });
        }

        const installmentsCanvas = document.getElementById('installmentsChart');
        if (installmentsCanvas) {
            new Chart(installmentsCanvas, {
                type: 'bar',
                data: {
                    labels: monthLabels,
                    datasets: [
                        {
                            label: 'التحصيلات',
                            data: installmentData,
                            backgroundColor: 'rgba(124, 58, 237, .76)',
                            borderColor: '#7c3aed',
                            borderWidth: 1,
                            barPercentage: .56
                        }
                    ]
                },
                options: commonOptions
            });
        }
    });
</script>
<script src="{{ asset('public/assets/admin/js/global.js') }}"></script>
@endpush
