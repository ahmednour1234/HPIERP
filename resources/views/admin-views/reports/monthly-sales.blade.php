@extends('layouts.admin.app')

@section('content')
<style>
    /* الألوان مأخوذة من الشكل المطلوب: عنوان كحلي وترويسة زرقاء فاتحة. */
    .ms-title {
        background: #1F3864;
        color: #fff;
        font-weight: 700;
        padding: 12px 40px;
        border-radius: 6px;
        display: inline-block;
        font-size: 20px;
    }
    .ms-table {
        border-collapse: collapse;
        width: 100%;
        font-size: 13px;
    }
    .ms-table th,
    .ms-table td {
        border: 1px solid #1F3864;
        padding: 8px 10px;
        text-align: center;
        vertical-align: middle;
    }
    .ms-table thead th {
        background: #9DC3E6;
        color: #1F3864;
        font-weight: 700;
    }
    .ms-table td.ms-label {
        background: #DEEBF7;
        color: #1F3864;
        font-weight: 700;
        text-align: right;
        white-space: nowrap;
    }
    .ms-table td.ms-serial {
        background: #9DC3E6;
        color: #1F3864;
        font-weight: 700;
        width: 34px;
    }
    /* عمود الإجمالي العام: مميّز بصريًا ليقرأ بسرعة. */
    .ms-table .ms-grand {
        background: #1F3864;
        color: #fff;
        font-weight: 700;
    }
    .ms-block { margin-bottom: 40px; }
    /* الجداول عريضة بطبيعتها؛ التمرير داخل الحاوية يمنع تمدد الصفحة. */
    .ms-scroll { overflow-x: auto; }
    @media print {
        .non-printable { display: none !important; }
        .ms-scroll { overflow: visible; }
    }
</style>

<div class="content container-fluid" dir="rtl">

    <div class="text-center mb-4">
        <span class="ms-title">تقرير ملخص المبيعات الشهري — {{ $month->format('Y/m') }}</span>
    </div>

    {{-- الفلاتر --}}
    <form method="GET" action="{{ route('admin.reports.monthly-sales') }}"
          class="card card-body mb-4 non-printable">
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
    <div class="ms-block">
        <div class="text-center mb-3">
            <span class="ms-title">تحصيلات المناطق</span>
        </div>

        <div class="row">
            <div class="col-lg-8">
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
            <div class="col-lg-4">
                <div class="text-center mb-3">
                    <span class="border border-dark px-4 py-2 d-inline-block font-weight-bold"
                          style="color:#1F3864;">نسبة التحقيق للمناطق</span>
                </div>
                <div class="row">
                    @foreach($collections as $row)
                        <div class="col-6 text-center mb-4">
                            <canvas class="ms-share-chart"
                                    data-labels="{{ json_encode($regions->pluck('name')->values(), JSON_UNESCAPED_UNICODE) }}"
                                    data-values="{{ json_encode(array_values($row['shares'])) }}"
                                    height="160"></canvas>
                            <div class="font-weight-bold mt-2" style="color:#1F3864;">
                                {{ $row['product']->name }}
                            </div>
                        </div>
                    @endforeach
                </div>
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

            var palette = ['#9DC3E6', '#ED7D31', '#1F3864', '#70AD47', '#FF0000', '#FFC000'];

            document.querySelectorAll('.ms-share-chart').forEach(function (canvas) {
                var labels = JSON.parse(canvas.dataset.labels || '[]');
                var values = JSON.parse(canvas.dataset.values || '[]');

                if (!values.length || values.every(function (v) { return !v; })) {
                    return;
                }

                new Chart(canvas.getContext('2d'), {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: values,
                            backgroundColor: labels.map(function (_, i) {
                                return palette[i % palette.length];
                            })
                        }]
                    },
                    options: {
                        responsive: true,
                        legend: { position: 'bottom', labels: { boxWidth: 12 } }
                    }
                });
            });
        });
    </script>
@endpush
