@php
    $currencySymbol = \App\CPU\Helpers::currency_symbol();
    $totalSales = (float) ($account['total_income'] ?? 0) + (float) ($account['total_expense'] ?? 0);
    $cashSales = (float) ($account['total_income'] ?? 0);
    $creditSales = (float) ($account['total_expense'] ?? 0);
    $collections = (float) ($account['total_installment'] ?? 0);
    $refunds = (float) ($account['total_refund'] ?? 0);
    $netSales = $totalSales - $refunds;

    $metricCards = [
        [
            'label' => 'إجمالي المبيعات',
            'value' => number_format($totalSales, 2),
            'foot' => 'نقدي وآجل قبل المرتجعات',
            'icon' => 'tio-money-vs',
            'accent' => 'navy',
            'size' => 'wide',
        ],
        [
            'label' => 'المبيعات النقدية',
            'value' => number_format($cashSales, 2),
            'foot' => 'إجمالي أوامر البيع النقدية',
            'icon' => 'tio-wallet',
            'accent' => 'green',
            'size' => 'half',
        ],
        [
            'label' => 'المبيعات الآجلة',
            'value' => number_format($creditSales, 2),
            'foot' => 'إجمالي أوامر البيع الآجلة',
            'icon' => 'tio-receipt-outlined',
            'accent' => 'amber',
            'size' => 'half',
        ],
        [
            'label' => 'إجمالي التحصيلات',
            'value' => number_format($collections, 2),
            'foot' => 'تحصيلات وفواتير مسددة',
            'icon' => 'tio-done',
            'accent' => 'cyan',
            'size' => 'half',
        ],
        [
            'label' => 'إجمالي المرتجعات الآجلة',
            'value' => number_format($refunds, 2),
            'foot' => 'قيمة المرتجعات خلال الفترة',
            'icon' => 'tio-reply',
            'accent' => 'rose',
            'size' => 'wide',
        ],
        [
            'label' => 'صافي المبيعات',
            'value' => number_format($netSales, 2),
            'foot' => 'المبيعات بعد خصم المرتجعات',
            'icon' => 'tio-chart-pie-1',
            'accent' => 'violet',
            'size' => 'wide',
        ],
    ];
@endphp

<div class="dashboard-metrics-grid">
    @foreach($metricCards as $metric)
        <div class="dashboard-metric-card dashboard-metric-card--{{ $metric['accent'] }} dashboard-metric-card--{{ $metric['size'] }}">
            <div class="dashboard-metric-top">
                <div>
                    <div class="dashboard-metric-label">{{ $metric['label'] }}</div>
                    <div class="dashboard-metric-value">{{ $metric['value'] }} {{ $currencySymbol }}</div>
                </div>
                <span class="dashboard-metric-icon"><i class="{{ $metric['icon'] }}"></i></span>
            </div>
            <div class="dashboard-metric-foot">{{ $metric['foot'] }}</div>
        </div>
    @endforeach
</div>

<div class="dashboard-charts-grid">
    <div class="dashboard-panel dashboard-chart-card">
        <h3 class="dashboard-chart-title">المبيعات الشهرية</h3>
        <div class="dashboard-chart-box">
            <canvas id="salesMixedChart"></canvas>
        </div>
    </div>
    <div class="dashboard-panel dashboard-chart-card">
        <h3 class="dashboard-chart-title">زيارات المناديب</h3>
        <div class="dashboard-chart-box">
            <canvas id="visitorsComparisonChart"></canvas>
        </div>
    </div>
    <div class="dashboard-panel dashboard-chart-card">
        <h3 class="dashboard-chart-title">التحصيلات الشهرية</h3>
        <div class="dashboard-chart-box">
            <canvas id="installmentsChart"></canvas>
        </div>
    </div>
</div>
