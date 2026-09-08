@extends('layouts.admin.app')

@section('content')
<style>
    .monthly-sales-page {
        background: #f4f8fb;
        min-height: calc(100vh - 70px);
        padding-top: 24px;
    }
    .ms-title {
        background: linear-gradient(135deg, #193866 0%, #285f8f 100%);
        color: #fff;
        font-weight: 700;
        padding: 13px 42px;
        border-radius: 8px;
        display: inline-block;
        font-size: 21px;
        box-shadow: 0 12px 28px rgba(31, 56, 100, .18);
    }
    .ms-filter-card,
    .ms-panel {
        background: #fff;
        border: 1px solid #d9e6f2;
        border-radius: 8px;
        box-shadow: 0 12px 30px rgba(22, 48, 76, .08);
    }
    .ms-filter-card {
        padding: 18px;
    }
    .ms-panel {
        overflow: hidden;
    }
    .ms-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 15px 18px;
        background: linear-gradient(135deg, #eef6fb 0%, #ffffff 100%);
        border-bottom: 1px solid #d9e6f2;
    }
    .ms-panel-title {
        color: #17365f;
        font-size: 18px;
        font-weight: 800;
        margin: 0;
    }
    .ms-panel-kicker {
        color: #5d7188;
        font-size: 12px;
        margin: 4px 0 0;
    }
    .ms-table {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
        font-size: 13px;
        background: #fff;
    }
    .ms-table th,
    .ms-table td {
        border: 0;
        border-left: 1px solid #d7e4f1;
        border-bottom: 1px solid #d7e4f1;
        padding: 10px 12px;
        text-align: center;
        vertical-align: middle;
    }
    .ms-table thead th {
        background: #9ec7e9;
        color: #123154;
        font-weight: 700;
        position: sticky;
        top: 0;
        z-index: 2;
    }
    .ms-table tbody tr:nth-child(even) td {
        background: #f7fbff;
    }
    .ms-table tbody tr:hover td {
        background: #eef7ff;
    }
    .ms-table td.ms-label {
        background: #e4f0fb;
        color: #17365f;
        font-weight: 700;
        text-align: right;
        white-space: nowrap;
        position: sticky;
        right: 38px;
        z-index: 1;
    }
    .ms-table td.ms-serial {
        background: #9ec7e9;
        color: #123154;
        font-weight: 700;
        min-width: 38px;
        position: sticky;
        right: 0;
        z-index: 1;
    }
    .ms-table .ms-grand {
        background: #17365f !important;
        color: #fff;
        font-weight: 700;
    }
    .ms-block { margin-bottom: 28px; }
    .ms-scroll {
        overflow: auto;
        max-height: 68vh;
    }
    .ms-collection-row {
        margin-right: 0;
        margin-left: 0;
        padding: 18px;
    }
    .ms-collection-col {
        min-width: 0;
    }
    .ms-chart-col {
        display: flex;
        min-width: 0;
    }
    .ms-chart-col .ms-chart-panel {
        width: 100%;
    }
    .ms-chart-panel {
        background: #f7fbff;
        border: 1px solid #d9e6f2;
        border-radius: 8px;
        max-height: 68vh;
        overflow: auto;
        padding: 14px;
    }
    .ms-chart-title {
        color: #17365f;
        font-weight: 800;
        text-align: center;
        margin-bottom: 14px;
    }
    .ms-chart-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 14px;
    }
    .ms-chart-card {
        min-height: 136px;
        background: #fff;
        border: 1px solid #d9e6f2;
        border-radius: 8px;
        padding: 12px;
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: flex-start;
        gap: 14px;
    }
    .ms-chart-canvas-wrap {
        flex: 0 0 112px;
        width: 112px;
        height: 112px;
    }
    .ms-chart-card canvas {
        width: 112px !important;
        height: 112px !important;
    }
    .ms-chart-name {
        color: #17365f;
        font-weight: 800;
        text-align: right;
        margin-top: 0;
        line-height: 1.5;
        min-height: 0;
    }
    .ms-empty-chart {
        width: 112px;
        height: 112px;
        border-radius: 50%;
        border: 12px solid #e8eef5;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #7a8da3;
        font-weight: 700;
    }
    @media (max-width: 575.98px) {
        .ms-title {
            width: 100%;
            padding: 12px 14px;
            font-size: 17px;
        }
        .ms-panel-header {
            align-items: flex-start;
            flex-direction: column;
        }
        .ms-chart-grid {
            grid-template-columns: 1fr;
        }
        .ms-chart-card {
            flex-direction: column;
            min-height: 196px;
        }
        .ms-chart-name {
            text-align: center;
        }
    }
    @media print {
        .monthly-sales-page { background: #fff; padding-top: 0; }
        .non-printable { display: none !important; }
        .ms-scroll { overflow: visible; max-height: none; }
        .ms-panel { box-shadow: none; }
    }
</style>

<div class="content container-fluid monthly-sales-page" dir="rtl">

    <div class="text-center mb-4">
        <span class="ms-title">تقرير ملخص المبيعات الشهري — {{ $month->format('Y/m') }}</span>
    </div>

    {{-- الفلاتر --}}
    <form method="GET" action="{{ route('admin.reports.monthly-sales') }}"
          class="ms-filter-card mb-4 non-printable">
        <div class="row">
            <div class="col-md-3 mb-2">
                <label class="font-weight-bold">الشهر</label>
                <input type="month" name="month" class="form-control"
                       value="{{ request('month', $month->format('Y-m')) }}">
            </div>

            <div class="col-md-4 mb-2">
                <label class="font-weight-bold">المناطق (يمكن اختيار أكثر من منطقة)</label>
                <select name="region_ids[]" class="form-control" multiple size="5">
                    @foreach($allRegions as $region)
                        <option value="{{ $region->id }}"
                            {{ in_array($region->id, $selectedRegionIds) ? 'selected' : '' }}>
                            {{ $region->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 mb-2">
                <label class="font-weight-bold">المنتجات (اتركها فارغة لعرض منتجات الشهر)</label>
                <select name="product_ids[]" class="form-control" multiple size="5">
                    @foreach($allProducts as $product)
                        <option value="{{ $product->id }}"
                            {{ in_array($product->id, $selectedProductIds) ? 'selected' : '' }}>
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-2">
            <button type="submit" class="btn btn-primary px-4">عرض التقرير</button>
            <a href="{{ route('admin.reports.monthly-sales') }}" class="btn btn-light border px-3">تصفية جديدة</a>
            <button type="button" class="btn btn-outline-secondary px-4" onclick="window.print()">طباعة</button>
            {{-- التصدير يحمل نفس فلاتر الشاشة --}}
            <a href="{{ route('admin.reports.monthly-sales.export', request()->query()) }}"
               class="btn btn-success px-4">تصدير اكسيل</a>
        </div>
    </form>

    @php
        $stockLabels = \App\Http\Controllers\Admin\MonthlySalesReportController::stockLabels();
        $salesLabels = \App\Http\Controllers\Admin\MonthlySalesReportController::salesLabels();
        $colCount    = $products->count();
    @endphp

    @if($colCount === 0)
        <div class="alert alert-warning text-center">
            لا توجد حركة منتجات خلال هذا الشهر. اختر شهرًا آخر أو حدّد منتجات بعينها.
        </div>
    @else

    {{-- ============ جداول كل منطقة على حدة ============ --}}
    @foreach($perRegion as $block)
        {{-- مخزون المنطقة --}}
        <div class="ms-block">
            <div class="text-center mb-3">
                <span class="ms-title">مخزون منطقة {{ $block['region']->name }}</span>
            </div>
            <div class="ms-scroll">
                <table class="ms-table">
                    <thead>
                        <tr>
                            <th>م</th>
                            <th>المقارنة \ المنتج</th>
                            @foreach($products as $product)
                                <th>{{ $product->name }}</th>
                            @endforeach
                            {{-- الإجمالي العام: مجموع كل المنتجات في الصف --}}
                            <th class="ms-grand">الإجمالي العام</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stockLabels as $key => $label)
                            <tr>
                                <td class="ms-serial">{{ $loop->iteration }}</td>
                                <td class="ms-label">{{ $label }}</td>
                                @php($rowTotal = 0)
                                @foreach($products as $product)
                                    @php($rowTotal += $block['stock'][$key][$product->id] ?? 0)
                                    <td>{{ number_format($block['stock'][$key][$product->id] ?? 0) }}</td>
                                @endforeach
                                <td class="ms-grand">{{ number_format($rowTotal) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- مبيعات المنطقة: لكل منتج عمودان (عدد عبوات + المبلغ) --}}
        <div class="ms-block">
            <div class="text-center mb-3">
                <span class="ms-title">مبيعات منطقة {{ $block['region']->name }}</span>
            </div>
            <div class="ms-scroll">
                <table class="ms-table">
                    <thead>
                        <tr>
                            <th rowspan="2">م</th>
                            <th rowspan="2">المقارنة \ المنتج</th>
                            @foreach($products as $product)
                                <th colspan="2">{{ $product->name }}</th>
                            @endforeach
                            {{-- الإجمالي العام: مجموع كل المنتجات في الصف --}}
                            <th colspan="2" class="ms-grand">الإجمالي العام</th>
                        </tr>
                        <tr>
                            @foreach($products as $product)
                                <th>عدد عبوات</th>
                                <th>المبلغ</th>
                            @endforeach
                            <th class="ms-grand">عدد عبوات</th>
                            <th class="ms-grand">المبلغ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($salesLabels as $key => $label)
                            <tr>
                                <td class="ms-serial">{{ $loop->iteration }}</td>
                                <td class="ms-label">{{ $label }}</td>
                                @php($rowQty = 0)
                                @php($rowAmount = 0)
                                @foreach($products as $product)
                                    @php($rowQty += $block['sales'][$key][$product->id]['qty'] ?? 0)
                                    @php($rowAmount += $block['sales'][$key][$product->id]['amount'] ?? 0)
                                    <td>{{ number_format($block['sales'][$key][$product->id]['qty'] ?? 0) }}</td>
                                    <td>{{ number_format($block['sales'][$key][$product->id]['amount'] ?? 0, 2) }}</td>
                                @endforeach
                                <td class="ms-grand">{{ number_format($rowQty) }}</td>
                                <td class="ms-grand">{{ number_format($rowAmount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    {{-- ============ التحصيلات: منتج × منطقة ============ --}}
    <div class="ms-block ms-panel">
        <div class="ms-panel-header">
            <div>
                <h2 class="ms-panel-title">تحصيلات المناطق</h2>
                <p class="ms-panel-kicker">توزيع التحصيل حسب المنتج والمنطقة خلال الشهر المحدد</p>
            </div>
            <span class="badge badge-soft-primary">{{ $products->count() }} منتج</span>
        </div>

        <div class="row ms-collection-row">
            <div class="col-xl-8 ms-collection-col mb-3 mb-xl-0">
                <div class="ms-scroll">
                    <table class="ms-table">
                        <thead>
                            <tr>
                                <th rowspan="2">م</th>
                                <th rowspan="2">المنتج \ المنطقة</th>
                                @foreach($regions as $region)
                                    <th colspan="2">{{ $region->name }}</th>
                                @endforeach
                                <th colspan="2">الإجمالي</th>
                            </tr>
                            <tr>
                                @foreach($regions as $region)
                                    <th>عدد العبوات</th>
                                    <th>المبلغ</th>
                                @endforeach
                                <th>عدد العبوات</th>
                                <th>المبلغ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($collections as $row)
                                <tr>
                                    <td class="ms-serial">{{ $loop->iteration }}</td>
                                    <td class="ms-label">{{ $row['product']->name }}</td>
                                    @foreach($regions as $region)
                                        <td>{{ number_format($row['regions'][$region->id]['qty'], 0) }}</td>
                                        <td>{{ number_format($row['regions'][$region->id]['amount'], 2) }}</td>
                                    @endforeach
                                    <td><strong>{{ number_format($row['total']['qty'], 0) }}</strong></td>
                                    <td><strong>{{ number_format($row['total']['amount'], 2) }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- نسبة التحقيق: حصة كل منطقة من تحصيل المنتج --}}
            <div class="col-xl-4 ms-chart-col">
            <aside class="ms-chart-panel">
                <div class="ms-chart-title">نسبة التحقيق للمناطق</div>
                <div class="ms-chart-grid">
                    @foreach($collections as $row)
                        <div class="ms-chart-card">
                            <div class="ms-chart-canvas-wrap">
                                <canvas class="ms-share-chart"
                                        data-labels="{{ json_encode($regions->pluck('name')->values(), JSON_UNESCAPED_UNICODE) }}"
                                        data-values="{{ json_encode(array_values($row['shares'])) }}"></canvas>
                            </div>
                            <div class="ms-chart-name">
                                {{ $row['product']->name }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </aside>
            </div>
        </div>
    </div>

    {{-- ============ الجداول المجمّعة ============ --}}
    <div class="ms-block">
        <div class="text-center mb-3">
            <span class="ms-title">إجمالي المخزون</span>
        </div>
        <div class="ms-scroll">
            <table class="ms-table">
                <thead>
                    <tr>
                        <th>م</th>
                        <th>المقارنة \ المنتج</th>
                        @foreach($products as $product)
                            <th>{{ $product->name }}</th>
                        @endforeach
                        {{-- الإجمالي العام: مجموع كل المنتجات في الصف --}}
                        <th class="ms-grand">الإجمالي العام</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stockLabels as $key => $label)
                        <tr>
                            <td class="ms-serial">{{ $loop->iteration }}</td>
                            <td class="ms-label">{{ $label }}</td>
                            @php($rowTotal = 0)
                            @foreach($products as $product)
                                @php($rowTotal += $totals['stock'][$key][$product->id] ?? 0)
                                <td>{{ number_format($totals['stock'][$key][$product->id] ?? 0) }}</td>
                            @endforeach
                            <td class="ms-grand">{{ number_format($rowTotal) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="ms-block">
        <div class="text-center mb-3">
            <span class="ms-title">إجمالي المبيعات</span>
        </div>
        <div class="ms-scroll">
            <table class="ms-table">
                <thead>
                    <tr>
                        <th rowspan="2">م</th>
                        <th rowspan="2">المقارنة \ المنتج</th>
                        @foreach($products as $product)
                            <th colspan="2">{{ $product->name }}</th>
                        @endforeach
                        {{-- الإجمالي العام: مجموع كل المنتجات في الصف --}}
                        <th colspan="2" class="ms-grand">الإجمالي العام</th>
                    </tr>
                    <tr>
                        @foreach($products as $product)
                            <th>عدد عبوات</th>
                            <th>المبلغ</th>
                        @endforeach
                        <th class="ms-grand">عدد عبوات</th>
                        <th class="ms-grand">المبلغ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($salesLabels as $key => $label)
                        <tr>
                            <td class="ms-serial">{{ $loop->iteration }}</td>
                            <td class="ms-label">{{ $label }}</td>
                            @php($rowQty = 0)
                            @php($rowAmount = 0)
                            @foreach($products as $product)
                                @php($rowQty += $totals['sales'][$key][$product->id]['qty'] ?? 0)
                                @php($rowAmount += $totals['sales'][$key][$product->id]['amount'] ?? 0)
                                <td>{{ number_format($totals['sales'][$key][$product->id]['qty'] ?? 0) }}</td>
                                <td>{{ number_format($totals['sales'][$key][$product->id]['amount'] ?? 0, 2) }}</td>
                            @endforeach
                            <td class="ms-grand">{{ number_format($rowQty) }}</td>
                            <td class="ms-grand">{{ number_format($rowAmount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @endif
</div>
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin/vendor/chart.js/dist/Chart.min.js') }}"></script>
    <script>
        "use strict";

        // الرسوم الدائرية لنسبة تحقيق كل منطقة. Chart.js قد لا يكون محمّلًا
        // في كل التوزيعات، فنتخطى الرسم بهدوء بدل كسر الصفحة كلها.
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart === 'undefined') {
                return;
            }

            var palette = ['#2563eb', '#f59e0b', '#16a34a', '#ef4444', '#7c3aed', '#0ea5e9', '#64748b'];

            document.querySelectorAll('.ms-share-chart').forEach(function (canvas) {
                var labels = JSON.parse(canvas.dataset.labels || '[]');
                var values = JSON.parse(canvas.dataset.values || '[]');

                if (!values.length || values.every(function (v) { return !v; })) {
                    var empty = document.createElement('div');
                    empty.className = 'ms-empty-chart';
                    empty.textContent = '0%';
                    canvas.parentNode.replaceChild(empty, canvas);
                    return;
                }

                new Chart(canvas.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: values,
                            borderColor: '#ffffff',
                            borderWidth: 2,
                            backgroundColor: labels.map(function (_, i) {
                                return palette[i % palette.length];
                            })
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutoutPercentage: 62,
                        legend: { display: false },
                        tooltips: {
                            callbacks: {
                                label: function (tooltipItem, data) {
                                    var label = data.labels[tooltipItem.index] || '';
                                    var value = data.datasets[0].data[tooltipItem.index] || 0;
                                    return label + ': ' + value + '%';
                                }
                            }
                        }
                    }
                });
            });
        });
    </script>
@endpush
