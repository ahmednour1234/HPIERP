@extends('layouts.admin.app')

@section('title', 'لوحة التحكم')

@section('content')
@php
    use App\Models\Customer;
    $counts = [
        'pharmacy'      => Customer::where('specialist', 1)->count(),
        'medical_center'=> Customer::where('specialist', 2)->count(),
        'hospital'      => Customer::where('specialist', 3)->count(),
        'doctor'        => Customer::wherein('specialist', [4,0])->count(),
    ];
@endphp

<div class="content container-fluid">

<div class="row gx-1 gy-2 mb-1">
    <!-- الصيدليات -->
    <div class="col-sm-6 col-md-3">
        <div class="card text-white shadow-sm h-100" style="background-color: #2596be;">
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <i class="tio-pharmacy fs-1 mb-2"></i>
                <h6 class="card-subtitle mb-1  text-dark">الصيدليات</h6>
                <span class="card-title h2">{{ $counts['pharmacy'] }}</span>
            </div>
        </div>
    </div>
    <!-- المراكز الطبية -->
    <div class="col-sm-6 col-md-3">
        <div class="card text-white shadow-sm h-100" style="background-color: #bee0ec;">
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <i class="tio-business-building fs-1 mb-2"></i>
                <h6 class="card-subtitle mb-1 text-dark">المراكز الطبية</h6>
                <span class="card-title h2">{{ $counts['medical_center'] }}</span>
            </div>
        </div>
    </div>
    <!-- المستشفيات -->
    <div class="col-sm-6 col-md-3">
        <div class="card text-white shadow-sm h-100" style="background-color: #66b6d2;">
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <i class="tio-hospital fs-1 mb-2"></i>
                <h6 class="card-subtitle mb-1  text-dark">المستشفيات</h6>
                <span class="card-title h2">{{ $counts['hospital'] }}</span>
            </div>
        </div>
    </div>
    <!-- الأطباء -->
    <div class="col-sm-6 col-md-3">
        <div class="card text-white shadow-sm h-100" style="background-color: #d3eaf2;">
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <i class="tio-stethoscope-outlined fs-1 mb-2"></i>
                <h6 class="card-subtitle mb-1  text-dark">الأطباء</h6>
                <span class="card-title h2">{{ $counts['doctor'] }}</span>
            </div>
        </div>
    </div>
</div>
    {{-- بطاقة إحصائيات النشاط --}}
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <h4 class="mb-0 text-primary">
                    <i class="tio-chart-bar-4"></i>
                    إحصائيات النشاط
                </h4>
                <span class="badge bg-success">
                    إصدار البرنامج: {{ env('SOFTWARE_VERSION') }}
                </span>
            </div>

            {{-- Form Filter --}}
            <form id="statsFilterForm" action="{{ url()->current() }}" method="GET" class="row align-items-center mb-3 gx-2">
                <div class="col-md-4">
                    <select id="statistics_type" name="statistics_type" class="form-select form-select-sm" onchange="updateDatesAndSubmit(this.value)">
                        <option value="overall" {{ request('statistics_type')=='overall' ? 'selected':'' }}>الإحصاءات الكلية</option>
                        <option value="today" {{ request('statistics_type')=='today' ? 'selected':'' }}>إحصائيات اليوم</option>
                        <option value="month" {{ request('statistics_type')=='month' ? 'selected':'' }}>إحصائيات هذا الشهر</option>
                        <option value="year" {{ request('statistics_type')=='year' ? 'selected':'' }}>إحصائيات هذه السنة</option>
                        <option value="quarter1" {{ request('statistics_type')=='quarter1' ? 'selected':'' }}>إحصائيات الربع الأول</option>
                        <option value="quarter2" {{ request('statistics_type')=='quarter2' ? 'selected':'' }}>إحصائيات الربع الثاني</option>
                        <option value="quarter3" {{ request('statistics_type')=='quarter3' ? 'selected':'' }}>إحصائيات الربع الثالث</option>
                        <option value="quarter4" {{ request('statistics_type')=='quarter4' ? 'selected':'' }}>إحصائيات الربع الرابع</option>
                        <option value="custom" {{ request('statistics_type')=='custom' ? 'selected':'' }}>نطاق مخصص</option>
                    </select>
                </div>
                <div class="col-md-8" id="custom_dates" style="display: {{ in_array(request('statistics_type'), ['custom']) ? 'flex':'none' }};">
                    <div class="input-group input-group-sm">
                        <input type="date" name="from_date" id="from_date" class="form-control" value="{{ request('from_date') }}" title="من التاريخ">
                        <input type="date" name="to_date" id="to_date" class="form-control" value="{{ request('to_date') }}" title="إلى التاريخ">
                        <button class="btn btn-sm btn-primary" type="submit">تطبيق</button>
                    </div>
                </div>
            </form>

            <div id="account_stats" class="row g-3">
                @include('admin-views.partials._dashboard-balance-stats', ['account' => $account])
            </div>
        </div>
    </div>

    {{-- بقية البطاقات كما هي --}}
    <div class="row g-3 mb-4">
        {{-- الحسابات --}}
        <div class="col-lg-6">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">الحسابات</h5>
                    <a href="{{ route('admin.account.list') }}" class="text-decoration-none">عرض الكل</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>الحساب</th>
                                    <th class="text-end">الرصيد</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($accounts as $account)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td><a href="{{ route('admin.account.list') }}" class="link-primary">{{ $account->account }}</a></td>
                                        <td class="text-end">{{ number_format($account->balance,2) }} {{ \App\CPU\Helpers::currency_symbol() }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center py-4 text-muted">لا توجد بيانات لعرضها</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        {{-- المنتجات ذات المخزون المحدود --}}
        <div class="col-lg-6">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">المنتجات ذات المخزون المحدود</h5>
                    <a href="{{ route('admin.stock.stock-limit') }}" class="text-decoration-none">عرض الكل</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>الاسم</th>
                                    <th class="text-end">الكمية</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $product)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td><a href="{{ route('admin.stock.stock-limit') }}" class="link-primary">{{ Str::limit($product->name, 50) }}</a></td>
                                        <td class="text-end">{{ $product->quantity }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center py-4 text-muted">لا توجد بيانات لعرضها</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- جدول مخزون المركبات --}}
<div class="card mb-4 shadow-sm border-0">
    <div class="card-body">
        <h5 class="mb-4 text-secondary">
            <i class="tio-car_sideview"></i>
            الرحلات الحالية
        </h5>
<div class="row row-cols-1 row-cols-md-3 g-3">
@foreach($sellers as $seller)
    @php
        // كانت هذه الكتلة تنفّذ نحو عشرة استعلامات لكل مندوب، وتحمّل كل
        // فواتيره وتفاصيلها إلى الذاكرة لتحديد حالة كل فاتورة. مع 13 مندوبًا
        // صار العرض 10 ثوانٍ و151 استعلامًا.
        //
        // الحساب انتقل إلى DashboardController::sellerStats() حيث يتم مرة
        // واحدة لكل المناديب. الأسماء هنا كما كانت حتى تبقى بقية القالب
        // دون تغيير.
        $stat = $sellerStats[$seller->id] ?? null;

        // مندوب بلا أي مخزون لم يكن يُعرض أصلًا.
        if (!$stat || !$stat['has_stock']) continue;

        $remain_stock         = $stat['remain_stock'];
        $order_count          = $stat['order_count'];
        $total_cash           = $stat['total_cash'];
        $total_credit         = $stat['total_credit'];
        $refund_total         = $stat['refund_total'];

        $orderAmountType4     = $stat['amount_type_4'];
        $orderAmountType7     = $stat['amount_type_7'];
        $transactionRefType4  = $stat['paid_type_4'];
        $amountDue            = $stat['amount_due'];

        $productCount         = $stat['product_count'];
        $quantitySum          = $stat['quantity_sum'];
        $priceSum             = $stat['price_sum'];
        $collectedUnits       = $stat['collected_units'];
        $invoiceStatusCounts  = $stat['status_counts'];

        $paidInvoices         = $stat['collected_receipts'];
        $unpaidInvoices       = $stat['uncollected_receipts'];

        // القالب يعرض عدد أصناف المخزون فقط، فيكفي العدد بدل تحميل الصفوف.
        $stocks = collect(range(1, (int) $stat['stock_line_count']));
    @endphp

    <div class="col">
      <a href="{{ route('admin.stock.products', $seller->id) }}" class="card h-100 shadow-sm text-decoration-none">
        <div class="card-body">
          <h6 class="card-title text-primary mb-1">
            {{ $seller->f_name }} {{ $seller->l_name }}
          </h6>
          <p class="mb-2 text-muted small">رمز المندوب: {{ $seller->mandob_code }}</p>

          {{-- Badges مختصرة --}}
          <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="badge bg-light text-dark">الطلبات: {{ $order_count }}</span>
            <span class="badge bg-light text-dark">عدد المنتجات بالمخزون: {{ $stocks->count() }}</span>
            <span class="badge bg-light text-dark">الكميات المتبقية: {{ $remain_stock }}</span>
            <span class="badge bg-light text-dark">الزيارات المطلوبة: {{ $seller->visitors }}</span>
            <span class="badge bg-light text-dark">الزيارات المنفذة: {{ $seller->result_visitors }}</span>
            <span class="badge bg-light text-dark">إيصالات محصلة: {{ $paidInvoices }}</span>
            <span class="badge bg-light text-dark">إيصالات غير محصلة: {{ $unpaidInvoices }}</span>
          </div>

          {{-- الصف الأول: (كما هو) نقدي / آجل / مرتجعات --}}
          <div class="row text-center g-2 mb-2">
            <div class="col">
              <div class="fw-bold text-success">{{ number_format($total_cash,2) }}</div>
              <div class="small text-muted">مبيعات نقدية</div>
            </div>
            <div class="col">
              <div class="fw-bold text-warning">{{ number_format($total_credit,2) }}</div>
              <div class="small text-muted">مبيعات آجل</div>
            </div>
            <div class="col">
              <div class="fw-bold text-danger">{{ number_format($refund_total,2) }}</div>
              <div class="small text-muted">مرتجعات</div>
            </div>
          </div>

          {{-- الصف الثاني: مبالغ البيع/المرتجع/المحصّل/المتبقي --}}
          <div class="row text-center g-2 mb-2">
            <div class="col-6 col-md-3">
              <div class="fw-bold">{{ number_format($orderAmountType4,2) }}</div>
              <div class="small text-muted">إجمالي مبالغ البيع</div>
            </div>
            <div class="col-6 col-md-3">
              <div class="fw-bold">{{ number_format($orderAmountType7,2) }}</div>
              <div class="small text-muted">إجمالي مبالغ المرتجع</div>
            </div>
            <div class="col-6 col-md-3">
              <div class="fw-bold">{{ number_format($transactionRefType4,2) }}</div>
              <div class="small text-muted">إجمالي مبالغ المحصلة</div>
            </div>
            <div class="col-6 col-md-3">
              <div class="fw-bold">{{ number_format($amountDue,2) }}</div>
              <div class="small text-muted">المتبقي بدون تحصيل</div>
            </div>
          </div>

          {{-- الصف الثالث: منتجات/كميات/محصّلة/مبالغ المنتجات --}}
          <div class="row text-center g-2 mb-2">
            <div class="col-6 col-md-3">
              <div class="fw-bold">{{ $productCount }}</div>
              <div class="small text-muted">عدد المنتجات المباعة</div>
            </div>
            <div class="col-6 col-md-3">
              <div class="fw-bold">{{ $quantitySum }}</div>
              <div class="small text-muted">إجمالي الكميات المباعة</div>
            </div>
            <div class="col-6 col-md-3">
              <div class="fw-bold">{{ $collectedUnits }}</div>
              <div class="small text-muted">إجمالي الكميات المُحصّلة</div>
            </div>
            <div class="col-6 col-md-3">
              <div class="fw-bold">{{ number_format($priceSum,2) }}</div>
              <div class="small text-muted">إجمالي مبالغ المنتجات</div>
            </div>
          </div>

          {{-- حالات الفواتير (عدادات) --}}
          <div class="d-flex flex-wrap gap-2 mt-2">
            <span class="badge bg-success-subtle text-success-emphasis" title="فواتير محصلة بالكامل">محصلة: {{ $invoiceStatusCounts['paid'] ?? 0 }}</span>
            <span class="badge bg-secondary-subtle text-secondary-emphasis" title="غير محصلة">غير محصلة: {{ $invoiceStatusCounts['unpaid'] ?? 0 }}</span>
            <span class="badge bg-warning-subtle text-warning-emphasis" title="محصلة عن طريق إرجاع كامل">إرجاع كامل: {{ $invoiceStatusCounts['returned_fully'] ?? 0 }}</span>
            <span class="badge bg-info-subtle text-info-emphasis" title="تحصيل جزئي فقط">تحصيل جزئي: {{ $invoiceStatusCounts['partial_paid'] ?? 0 }}</span>
            <span class="badge bg-info-subtle text-info-emphasis" title="إرجاع جزئي فقط">إرجاع جزئي: {{ $invoiceStatusCounts['partial_returned'] ?? 0 }}</span>
            <span class="badge bg-primary-subtle text-primary-emphasis" title="تحصيل جزئي وإرجاع جزئي">جزئي+جزئي: {{ $invoiceStatusCounts['partial_both'] ?? 0 }}</span>
          </div>
        </div>
      </a>
    </div>
@endforeach
</div>
    </div>
</div>

</div>
@endsection

@push('script_2')
<script src="{{ asset('public/assets/admin/vendor/chart.js/dist/Chart.min.js') }}"></script>
<script src="{{ asset('public/assets/admin/vendor/chart.js.extensions/chartjs-extensions.js') }}"></script>
<script src="{{ asset('public/assets/admin/vendor/chartjs-plugin-datalabels/dist/chartjs-plugin-datalabels.min.js') }}"></script>
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
            customDiv.style.display = 'flex';
        } else if (type === 'custom') {
            customDiv.style.display = 'flex';
        } else {
            customDiv.style.display = 'none';
            fromInput.value = '';
            toInput.value = '';
        }
        form.submit();
    }
</script>
<script src="{{ asset('public/assets/admin/js/global.js') }}"></script>
@endpush
