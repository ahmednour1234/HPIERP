@extends('layouts.admin.app')

@section('title', \App\CPU\translate('قائمة نتائج الزيارات'))

@push('css_or_js')
<link rel="stylesheet" href="{{ asset('public/assets/admin/css/custom.css') }}" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    /* Summary cards */
    .summary-card { border-radius: .75rem; box-shadow: 0 2px 6px rgba(0,0,0,0.1); transition: transform .2s; }
    .summary-card:hover { transform: translateY(-4px); }
    .summary-icon { font-size: 2.5rem; opacity: .15; }
    /* Visits summary cards */
    .visits-card { border-radius: .75rem; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
    /* Filter section */
    .filter-card { border-radius: 1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 1.5rem; }
    .filter-header { background: linear-gradient(90deg,#bee0ec,#2596be); color: #fff; padding: 1rem 1.5rem; font-weight: 600; border-radius: 1rem 1rem 0 0; }
    .filter-card .form-label { font-weight: 600; }
    .filter-card .select2-container--default .select2-selection--single,
    .filter-card .select2-container--default .select2-selection--multiple { border-radius: .5rem; min-height: 48px; }
    /* Chart container */
    .chart-container { position: relative; height: 300px; margin-bottom: 2rem; }
    /* Table styling */
    .card-table thead { background: #2596be; color: #fff; }
    .card-table th, .card-table td { border: none; vertical-align: middle; }
    /* Buttons */
    .btn-search { background: #2596be; border-color: #2596be; color: #fff; }
</style>
@endpush

@section('content')
<div class="content container-fluid" dir="rtl">
        <div class="card filter-card">
        <div class="filter-header">{{ \App\CPU\translate('فلترة النتائج') }}</div>
        <div class="card-body">
            <form action="{{ route('admin.visitor.showResultVisitors', ['seller_id' => $seller_id]) }}" method="GET">
                <div class="row gx-3 gy-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">{{ \App\CPU\translate('اسم العميل') }}</label>
                        {{-- كتابة بدل الاختيار: قائمة العملاء بالآلاف --}}
                        <input type="text" name="customer" class="form-control"
                               value="{{ request('customer') }}"
                               placeholder="{{ \App\CPU\translate('اكتب اسم العميل') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ \App\CPU\translate('التخصص') }}</label>
                        <select name="specialist" class="form-control select2">
                            <option value="">{{ \App\CPU\translate('اختر التخصص') }}</option>
                            <option value="1" {{ request()->specialist == '1' ? 'selected' : '' }}>{{ \App\CPU\translate('صيدلية') }}</option>
                            <option value="2" {{ request()->specialist == '2' ? 'selected' : '' }}>{{ \App\CPU\translate('مركز طبي') }}</option>
                            <option value="3" {{ request()->specialist == '3' ? 'selected' : '' }}>{{ \App\CPU\translate('مستشفى') }}</option>
                            <option value="4" {{ request()->specialist == '4' ? 'selected' : '' }}>{{ \App\CPU\translate('طبيب') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ \App\CPU\translate('المنطقة') }}
                            <small class="text-muted">({{ \App\CPU\translate('أكثر من منطقة') }})</small>
                        </label>
                        {{-- متعدد الاختيار --}}
                        <select name="region_id[]" class="form-control" multiple size="4" style="height:auto;">
                            @foreach(($regions ?? []) as $r)
                                <option value="{{ $r->id }}"
                                    @selected(in_array((string) $r->id, array_map('strval', (array) request('region_id', [])), true))>
                                    {{ $r->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">{{ \App\CPU\translate('من تاريخ') }}</label>
                        <input type="date" name="from_date" class="form-control" value="{{ request()->from_date }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ \App\CPU\translate('إلى تاريخ') }}</label>
                        <input type="date" name="to_date" class="form-control" value="{{ request()->to_date }}">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-search w-100">{{ \App\CPU\translate('بحث') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
            <div class="row mb-4">

    <div class="col-xl-6 col-md-6 mb-3">
            <div class="card visits-card text-center p-3">
                <div class="card-body">
                    <i class="fas fa-chart-line summary-icon text-info mb-2"></i>
                    <h6 class="mb-1">{{ \App\CPU\translate('إجمالي المبيعات') }}</h6>
                    <span class="h3 text-info">{{ $totalSales }}</span>
                </div>
            </div>
        </div>
        <div class="col-xl-6 col-md-6 mb-3">
            <div class="card visits-card text-center p-3">
                <div class="card-body">
                    <i class="fas fa-calendar-check summary-icon text-secondary mb-2"></i>
                    <h6 class="mb-1">{{ \App\CPU\translate('إجمالي المرتجعىات') }}</h6>
                    <span class="h3 text-secondary">{{ $totalReturned }}</span>
                </div>
            </div>
        </div>
</div>


    <!-- Summary Cards -->
        <div class="row mb-4">
        <div class="col-xl-3 col-md-4 mb-3">
            <div class="card summary-card text-center p-3">
                <div class="card-body">
                    <i class="fas fa-prescription-bottle summary-icon text-primary mb-2"></i>
                    <h6 class="mb-1">{{ \App\CPU\translate('إجمالي التحصيلات') }}</h6>
                    <span class="h3 text-primary">{{ $totalinstallment }}</span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-4 mb-3">
            <div class="card summary-card text-center p-3">
                <div class="card-body">
                    <i class="fas fa-clinic-medical summary-icon text-success mb-2"></i>
                    <h6 class="mb-1">{{ \App\CPU\translate('إجمالي المبيعات الأجل') }}</h6>
                    <span class="h3 text-success">{{ $totalCreditSales }}</span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-4 mb-3">
            <div class="card summary-card text-center p-3">
                <div class="card-body">
                    <i class="fas fa-hospital summary-icon text-warning mb-2"></i>
                    <h6 class="mb-1">{{ \App\CPU\translate('إجمالي المبيعات الكاش') }}</h6>
                    <span class="h3 text-warning">{{ $totalCashSales }}</span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-4 mb-3">
            <div class="card summary-card text-center p-3">
                <div class="card-body">
                    <i class="fas fa-user-md summary-icon text-danger mb-2"></i>
                    <h6 class="mb-1">{{ \App\CPU\translate('صافي المبيعات') }}</h6>
                    <span class="h3 text-danger">{{ $netSales }}</span>
                </div>
            </div>
        </div>
        
    </div>

    <div class="row mb-4">
        <div class="col-xl-3 col-md-4 mb-3">
            <div class="card summary-card text-center p-3">
                <div class="card-body">
                    <i class="fas fa-prescription-bottle summary-icon text-primary mb-2"></i>
                    <h6 class="mb-1">{{ \App\CPU\translate('صيدليات') }}</h6>
                    <span class="h3 text-primary">{{ $type1Count }}</span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-4 mb-3">
            <div class="card summary-card text-center p-3">
                <div class="card-body">
                    <i class="fas fa-clinic-medical summary-icon text-success mb-2"></i>
                    <h6 class="mb-1">{{ \App\CPU\translate('مراكز طبية') }}</h6>
                    <span class="h3 text-success">{{ $type2Count }}</span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-4 mb-3">
            <div class="card summary-card text-center p-3">
                <div class="card-body">
                    <i class="fas fa-hospital summary-icon text-warning mb-2"></i>
                    <h6 class="mb-1">{{ \App\CPU\translate('مستشفيات') }}</h6>
                    <span class="h3 text-warning">{{ $type3Count }}</span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-4 mb-3">
            <div class="card summary-card text-center p-3">
                <div class="card-body">
                    <i class="fas fa-user-md summary-icon text-danger mb-2"></i>
                    <h6 class="mb-1">{{ \App\CPU\translate('أطباء') }}</h6>
                    <span class="h3 text-danger">{{ $type4Count }}</span>
                </div>
            </div>
        </div>
        
    </div>
        <div class="row mb-4">

    <div class="col-xl-6 col-md-6 mb-3">
            <div class="card visits-card text-center p-3">
                <div class="card-body">
                    <i class="fas fa-chart-line summary-icon text-info mb-2"></i>
                    <h6 class="mb-1">{{ \App\CPU\translate('الزيارات الفعلية') }}</h6>
                    <span class="h3 text-info">{{ $totalActualVisits }}</span>
                </div>
            </div>
        </div>
        <div class="col-xl-6 col-md-6 mb-3">
            <div class="card visits-card text-center p-3">
                <div class="card-body">
                    <i class="fas fa-calendar-check summary-icon text-secondary mb-2"></i>
                    <h6 class="mb-1">{{ \App\CPU\translate('الزيارات المطلوبة') }}</h6>
                    <span class="h3 text-secondary">{{ $seller->visitors }}</span>
                </div>
            </div>
        </div>
</div>

    <!-- Filter Section -->

    <!-- Chart: Monthly Visits -->
    <div class="chart-container col-12">
        <canvas id="monthlyChart"></canvas>
    </div>

    <!-- Top 5 Lists -->
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header bg-light">{{ \App\CPU\translate('أكثر 5 أطباء زيارة') }}</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="thead-light"><tr><th>#</th><th>{{ \App\CPU\translate('الاسم') }}</th><th>{{ \App\CPU\translate('عدد الزيارات') }}</th></tr></thead>
                        <tbody>
                            @foreach($topDoctors as $idx => $doc)
                                <tr><td>{{ $idx + 1 }}</td><td>{{ $doc->name }}</td><td>{{ $doc->visits }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header bg-light">{{ \App\CPU\translate('أكثر 5 صيدليات مبيعا') }}</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="thead-light"><tr><th>#</th><th>{{ \App\CPU\translate('الاسم') }}</th><th>{{ \App\CPU\translate('عدد الزيارات') }}</th></tr></thead>
                        <tbody>
                            @foreach($topPharmacies as $idx => $ph)
                                <tr><td>{{ $idx + 1 }}</td><td>{{ $ph->name }}</td><td>{{ $ph->orders_count }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header bg-light">{{ \App\CPU\translate('أكثر 5 مراكز طبية زيارة') }}</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="thead-light"><tr><th>#</th><th>{{ \App\CPU\translate('الاسم') }}</th><th>{{ \App\CPU\translate('عدد الزيارات') }}</th></tr></thead>
                        <tbody>
                            @foreach($topMedicalCenters as $idx => $mc)
                                <tr><td>{{ $idx + 1 }}</td><td>{{ $mc->name }}</td><td>{{ $mc->visits }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header bg-light">{{ \App\CPU\translate('أكثر 5 مستشفيات زيارة') }}</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="thead-light"><tr><th>#</th><th>{{ \App\CPU\translate('الاسم') }}</th><th>{{ \App\CPU\translate('عدد الزيارات') }}</th></tr></thead>
                        <tbody>
                            @foreach($topHospitals as $idx => $hosp)
                                <tr><td>{{ $idx + 1 }}</td><td>{{ $hosp->name }}</td><td>{{ $hosp->visits }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Paginated Visits Table -->
    <div class="card">
        <div class="table-responsive">
<table class="table card-table table-hover table-borderless table-nowrap mb-0">
    <thead>
        <tr>
            <th>#</th>
            <th>{{ \App\CPU\translate('اسم العميل') }}</th>
            <th>{{ \App\CPU\translate('تفاصيل العميل') }}</th>
            <th>{{ \App\CPU\translate('المنطقة') }}</th>
            <th>{{ \App\CPU\translate('التخصص') }}</th>
            <th>{{ \App\CPU\translate('تاريخ الزيارة') }}</th>
            <th>{{ \App\CPU\translate('ملاحظة') }}</th>
            <th>{{ \App\CPU\translate('الموقع') }}</th> {{-- عمود جديد --}}
        </tr>
    </thead>
    <tbody>
        @forelse($visitors as $i => $v)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $v->customer->name ?? '-' }}</td>
                <td>{{ ($v->customer->address ?? '-') . ' / ' . ($v->customer->mobile ?? '-') }}</td>
                <td>{{ $v->customer->regions->name ?? '-' }}</td>
                <td>
                    @switch($v->customer->specialist ?? '')
                        @case(1) {{ \App\CPU\translate('صيدلية') }} @break
                        @case(2) {{ \App\CPU\translate('مركز طبي') }} @break
                        @case(3) {{ \App\CPU\translate('مستشفى') }} @break
                        @case(4) {{ \App\CPU\translate('طبيب') }} @break
                        @default - 
                    @endswitch
                </td>
                <td>{{ \Carbon\Carbon::parse($v->created_at)->format('Y-m-d') }}</td>
                <td>{{ $v->note }}</td>
                <td>
                    @if($v->customer && $v->lat && $v->lang)
                        <a href="https://www.google.com/maps?q={{ $v->lat }},{{ $v->lang }}"
                           class="btn btn-sm btn-outline-info"
                           target="_blank">
                            {{ \App\CPU\translate('عرض الموقع') }}
                        </a>
                    @else
                        -
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center py-4">
                    <img src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}"
                         alt="لا توجد بيانات"
                         style="width:120px;"
                         class="mb-3">
                    <p class="text-muted mb-0">{{ \App\CPU\translate('لاتوجد بيانات لعرضها') }}</p>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
        </div>
        <div class="d-flex justify-content-center mt-3">
            {!! $visitors->appends(request()->query())->links() !!}
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.full.min.js"></script>
<script>
    $(function(){ $('.select2-single').select2({ minimumResultsForSearch: Infinity, width: '100%' }); });

    const ctx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [{!! "'" . implode("','", array_map(fn($m)=> \Illuminate\Support\Carbon::create(null,$m,1)->translatedFormat('F'), range(1,12))) . "'" !!}],
            datasets: [
                {
                    label: '{{ \App\CPU\translate('زيارات فعلية') }}',
                    data: [{!! implode(',', $monthlyActualVisits) !!}],
                    borderColor: 'rgba(37,150,190,1)',
                    backgroundColor: 'rgba(37,150,190,0.2)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointBackgroundColor: 'rgba(37,150,190,1)'
                },
                {
                    label: '{{ \App\CPU\translate('زيارات مطلوبة') }}',
                    data: [{!! implode(',', $monthlyRequiredVisits) !!}],
                    borderColor: 'rgba(101,194,255,1)',
                    backgroundColor: 'rgba(101,194,255,0.2)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointBackgroundColor: 'rgba(101,194,255,1)'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, grid: { drawBorder: false } }
            }
        }
    });
</script>
@endpush

@push('script_2')
    {{-- إعادة تحميل jQuery هنا كانت تُنشئ نسخة جديدة تمسح ما رُبط قبلها،
         فتفقد عناصر الفلتر تهيئة select2 ويبدو الفلتر وكأنه لا يعمل.
         التخطيط يحمّل jQuery وselect2 بالفعل، فلا حاجة لإعادة تحميلهما. --}}
    <script>
        $(document).ready(function() {
            // .select2 قد تكون مُهيّأة سلفًا في الكتلة الأولى؛ التهيئة مرتين
            // تُفقد الحالة، فنتخطى ما هو مُهيّأ.
            $('.select2').not('.select2-hidden-accessible').select2();

            function calculateTotal() {
                let salary = parseFloat($('#salary').val()) || 0;
                let commission = parseFloat($('#commission').val()) || 0;
                let transportAmount = parseFloat($('#transport_amount').val()) || 0;
                let salaryOfVisitors = parseFloat($('#salary_of_visitors').val()) || 0;
                let discount = parseFloat($('#discount').val()) || 0;
                let other = parseFloat($('#other').val()) || 0;

                let total = salary + transportAmount + salaryOfVisitors + other - discount;
                $('#total').val(total.toFixed(2));
            }

            $('#seller_id').change(function() {
                var sellerId = $(this).val();
                if (sellerId) {
                    $.ajax({
                        url: '{{ route("admin.salaries.showsalary", "") }}/' + sellerId,
                        method: 'GET',
                        success: function(data) {
                            $('#salary').val(data.salary);
                            $('#commission').val(data.commission);
                            $('#score').val(data.score);
                            $('#number_of_visitors').val(data.visitors);
                            $('#result_of_visitors').val(data.result_visitors);
                            $('#notemanager').val(data.note || 'لا توجد ملاحظات');
                            $('#holidays').val(data.holidays);
                            $('#number_of_days').val(data.number_of_days);
                                let resultVisitors = parseFloat(data.result_visitors) || 0;
    let totalVisitors = parseFloat(data.visitors) || 0;
    let ratio = totalVisitors > 0 ? (resultVisitors / totalVisitors * 100).toFixed(2) + '%' : '0%';
    $('#visits_ratio').val(ratio);
                            calculateTotal();
                        },
                        error: function() {
                            alert('Error fetching salary details.');
                        }
                    });
                } else {
                    $('#salary, #commission, #score, #number_of_visitors, #result_of_visitors, #notemanager, #holidays, #number_of_days').val('');
                    calculateTotal();
                }
            });

            $('#salary_of_visitors, #transport_amount, #discount, #other').on('input', calculateTotal);
        });
    </script>
@endpush

