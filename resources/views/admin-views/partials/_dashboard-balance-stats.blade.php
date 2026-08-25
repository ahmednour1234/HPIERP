<div class="row gx-3">
    {{-- إجمالي المبيعات --}}
    <div class="col-sm-12 col-lg-6 mb-3">
        <a class="card h-100 text-decoration-none" href="#" style="background-color: #2596be;">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="card-subtitle mb-1 text-white">أجمالي المبيعات</h6>
                    <span class="card-title h2 text-white">
                        {{ round($account['total_income'] + $account['total_expense'], 2) }}
                    </span>
                </div>
                <i class="tio-money-vs text-white fs-1"></i>
            </div>
        </a>
    </div>

    {{-- المبيعات النقدية --}}
    <div class="col-sm-6 col-lg-6 mb-3">
        <a class="card h-100 text-decoration-none" href="#" style="background-color: #bee0ec;">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="card-subtitle mb-1 text-dark">أجمالي المبيعات النقدية</h6>
                    <span class="card-title h2 text-dark">
                        {{ number_format($account['total_income'], 2) }}
                    </span>
                </div>
                <i class="tio-money-vs text-dark fs-1"></i>
            </div>
        </a>
    </div>

    {{-- المبيعات الآجلة --}}
    <div class="col-sm-6 col-lg-6 mb-3">
        <a class="card h-100 text-decoration-none" href="#" style="background-color: #66b6d2;">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="card-subtitle mb-1 text-white">أجمالي المبيعات الآجلة</h6>
                    <span class="card-title h2 text-white">
                        {{ round($account['total_expense'], 2) }}
                    </span>
                </div>
                <i class="tio-money-vs text-white fs-1"></i>
            </div>
        </a>
    </div>

    {{-- إجمالي التحصيلات --}}
    <div class="col-sm-6 col-lg-6 mb-3">
        <a class="card h-100 text-decoration-none" href="#" style="background-color: #d3eaf2;">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="card-subtitle mb-1 text-dark">أجمالي التحصيلات</h6>
                    <span class="card-title h2 text-dark">
                        {{ $account['total_installment'] }}
                    </span>
                </div>
                <i class="tio-money-vs text-dark fs-1"></i>
            </div>
        </a>
    </div>

    {{-- المرتجعات الآجلة --}}
    <div class="col-sm-12 col-lg-12 mb-3">
        <a class="card h-100 text-decoration-none" href="#" style="background-color: #3ba1c5;">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="card-subtitle mb-1 text-white">أجمالي المرتجعات الآجلة</h6>
                    <span class="card-title h2 text-white">
                        {{ round($account['total_refund'], 2) }}
                    </span>
                </div>
                <i class="tio-money-vs text-white fs-1"></i>
            </div>
        </a>
    </div>

    {{-- صافي المبيعات --}}
    <div class="col-sm-12 col-lg-12 mb-3">
        <a class="card h-100 text-decoration-none" href="#" style="background-color: #ffffff; border: 1px solid #2596be;">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="card-subtitle mb-1 text-dark">صافي المبيعات</h6>
                    <span class="card-title h2 text-dark">
                        {{ round($account['total_income'] + $account['total_expense'] - $account['total_refund'], 2) }}
                    </span>
                </div>
                <i class="tio-money-vs text-primary fs-1"></i>
            </div>
        </a>
    </div>
</div>

{{-- مخطط مختلط واحد (rtl وتحكم بالحجم) --}}
<div class="row gx-3 mt-4" dir="rtl">
    <div class="col-12 col-lg-4 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-4">
                <h6 class="card-subtitle mb-4 text-center">المبيعات الشهرية (نقدي وآجل)</h6>
                <div style="position: relative; width:100%; height:350px;">
                    <canvas id="salesMixedChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-4 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-4">
                <h6 class="card-subtitle mb-4 text-center">مقارنة الزوار الشهرية</h6>
                <div style="position: relative; width:100%; height:350px;">
                    <canvas id="visitorsComparisonChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-4 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-4">
                <h6 class="card-subtitle mb-4 text-center">التحصيلات الشهرية</h6>
                <div style="position: relative; width:100%; height:350px;">
                    <canvas id="installmentsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@3"></script>
<script>
    Chart.defaults.locale = 'ar';

    const monthLabels = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
    const cashData     = @json(array_values($monthly_income));
    const creditData   = @json(array_values($monthly_expense));
    const requestedVisitors = @json(array_values($monthly_visitors));
    const executedVisitors  = @json(array_values($monthly_result_visitor));
    const installmentData   = @json(array_values($monthly_installments));

    const commonOptions = {
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top', align: 'start', labels: { usePointStyle: true, padding: 16, font: { size: 14, weight: '600' } } },
            tooltip: { mode: 'index', intersect: false, backgroundColor: '#fff', titleColor: '#333', bodyColor: '#333', borderColor: '#ddd', borderWidth: 1, padding: 12 }
        },
        scales: {
            x: { reversed: true, grid: { display: false }, ticks: { color: '#444', font: { size: 12 } } },
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { color: '#444', font: { size: 12 } } }
        }
    };

    // مخطط المبيعات المختلط
    const salesData = {
        labels: monthLabels,
        datasets: [
            { type: 'bar', label: 'نقدي', data: cashData, backgroundColor: 'rgba(37,150,190,0.8)', borderColor: '#2596be', borderWidth: 2, borderRadius: 8, barPercentage: 0.6 },
            { type: 'line', label: 'آجل', data: creditData, borderColor: '#e74c3c', backgroundColor: 'rgba(231,76,60,0.3)', borderWidth: 3, tension: 0.3, fill: true, pointRadius: 6, pointBackgroundColor: '#e74c3c' }
        ]
    };
    new Chart(document.getElementById('salesMixedChart'), { data: salesData, options: commonOptions });

    // مخطط مقارنة الزوار
    const visitorsData = {
        labels: monthLabels,
        datasets: [
            { label: 'الزيارات المطلوبة', data: requestedVisitors, backgroundColor: 'rgba(52,152,219,0.8)', borderColor: '#3498db', borderWidth: 2, borderRadius: 6, barPercentage: 0.5 },
            { label: 'الزيارات المنفذة', data: executedVisitors, backgroundColor: 'rgba(46,204,113,0.8)', borderColor: '#2ecc71', borderWidth: 2, borderRadius: 6, barPercentage: 0.5 }
        ]
    };
    new Chart(document.getElementById('visitorsComparisonChart'), { type: 'bar', data: visitorsData, options: commonOptions });

    // مخطط التحصيلات الشهرية
    const installmentsData = {
        labels: monthLabels,
        datasets: [
            { label: 'التحصيلات', data: installmentData, backgroundColor: 'rgba(155,89,182,0.8)', borderColor: '#9b59b6', borderWidth: 2, borderRadius: 6, barPercentage: 0.6 }
        ]
    };
    new Chart(document.getElementById('installmentsChart'), { type: 'bar', data: installmentsData, options: commonOptions });
</script>
