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
        // 1) مخزون وبديهيات العرض
        $stocks = \App\Models\Stock::where('seller_id', $seller->id)
                    ->whereRaw('main_stock != stock')
                    ->get();

        if ($stocks->isEmpty() && \App\Models\Stock::where('seller_id', $seller->id)
                ->whereRaw('main_stock = stock')->get()->isEmpty()) continue;

        $remain_stock = $stocks->sum('stock');
        $total_stock  = $stocks->sum(fn($st) => $st->main_stock - $st->stock);

        // 2) الطلبات والأموال (CurrentOrder كما في كودك)
        $order_count  = \App\Models\CurrentOrder::where('owner_id', $seller->id)->count();
        $total_cash   = \App\Models\CurrentOrder::where('owner_id', $seller->id)
                            ->where('type', 4)->where('cash', 1)->sum('order_amount');
        $total_credit = \App\Models\CurrentOrder::where('owner_id', $seller->id)
                            ->where('type', 4)->where('cash', 2)->sum('order_amount');
        $refund_total = \App\Models\CurrentOrder::where('owner_id', $seller->id)
                            ->where('type', 7)->sum('order_amount');

        // 3) استبعاد فواتير البيع التي تم إرجاعها بالكامل + IDs المرتجعات (لعدّ الإيصالات)
        $fullyReturnedOrderIds = \App\Models\Order::where('type', 4)
            ->whereIn('id', function ($query) {
                $query->select('parent_id')
                    ->from('orders as r')
                    ->whereNotNull('parent_id')
                    ->where('r.type', 7)
                    ->groupBy('parent_id')
                    ->havingRaw("
                        NOT EXISTS (
                            SELECT 1
                            FROM order_details od
                            WHERE od.order_id = parent_id
                            AND NOT EXISTS (
                                SELECT 1 FROM order_details rod
                                JOIN orders r2 ON rod.order_id = r2.id
                                WHERE r2.parent_id = parent_id
                                AND rod.product_id = od.product_id
                                GROUP BY rod.product_id
                                HAVING SUM(rod.quantity) >= od.quantity
                            )
                        )
                    ");
            })
            ->pluck('id');

        $returnedOrders = \App\Models\Order::where('type', 7)->get();
        $parentIds      = $returnedOrders->pluck('parent_id')->filter()->unique();
        $returnedIds    = $returnedOrders->pluck('id')->unique();
        $excludedOrderIds = $returnedIds->merge($fullyReturnedOrderIds)->unique();

        $collectedReceipts = \App\Models\CurrentOrder::where('owner_id', $seller->id)
            ->whereColumn('order_amount', '=', 'transaction_reference')
            ->when($excludedOrderIds->isNotEmpty(), fn($q) => $q->whereNotIn('id', $excludedOrderIds))
            ->count();

        $uncollectedReceipts = \App\Models\CurrentOrder::where('owner_id', $seller->id)
            ->whereColumn('order_amount', '!=', 'transaction_reference')
            ->when($excludedOrderIds->isNotEmpty(), fn($q) => $q->whereNotIn('id', $excludedOrderIds))
            ->count();

        // 4) «باقي الإحصائيات» لكل مندوب (نعتمد Order/OrderDetail)
        // مبالغ البيع/المرتجع/المحصّل
        $orderAmountType4      = \App\Models\Order::where('owner_id', $seller->id)->where('type', 4)->sum('order_amount');
        $orderAmountType7      = \App\Models\Order::where('owner_id', $seller->id)->where('type', 7)->sum('order_amount');
        $transactionRefType4   = \App\Models\Order::where('owner_id', $seller->id)->where('type', 4)->sum('transaction_reference');
        $amountDue             = $orderAmountType4 - $orderAmountType7 - $transactionRefType4;

        // تفاصيل المنتجات المباعة (type=4)
        $orderDetailsType4 = \App\Models\OrderDetail::whereHas('order', function($q) use ($seller){
                $q->where('owner_id', $seller->id)->where('type', 4);
            })->get();

        $productCount = $orderDetailsType4->groupBy('product_details->id')->count();
        $quantitySum  = $orderDetailsType4->sum('quantity');
        $priceSum     = $orderDetailsType4->sum(fn($d) => (float)($d->price ?? 0) * (float)($d->quantity ?? 0));

        // حساب الكميات المحصلة + عدادات الحالات
        $ordersType4 = \App\Models\Order::where('owner_id', $seller->id)
            ->where('type', 4)
            ->with('details')
            ->get();

        $returnsByParent = \App\Models\Order::where('type', 7)
            ->whereIn('parent_id', $ordersType4->pluck('id')->unique())
            ->with('details')
            ->get()
            ->groupBy('parent_id');

        $collectedUnits = 0;
        $invoiceStatusCounts = [
            'paid'             => 0,
            'unpaid'           => 0,
            'returned_fully'   => 0,
            'partial_paid'     => 0,
            'partial_returned' => 0,
            'partial_both'     => 0,
        ];

        foreach ($ordersType4 as $o) {
            $originalQty    = (int) $o->details->sum('quantity');
            $originalAmount = (float) $o->details->sum(fn($d) => (float)($d->price ?? 0) * (float)($d->quantity ?? 0));
            $paidAmount     = (float) $o->transaction_reference;
            $orderamount     = (float) $o->order_amount;

            $returns        = $returnsByParent->get($o->id, collect());
            $returnedQty    = (int) $returns->flatMap->details->sum('quantity');
            $returnedAmount = (float) $returns->flatMap->details->sum(fn($d) => (float)($d->price ?? 0) * (float)($d->quantity ?? 0));

            // تحديد الحالة
            if ($orderamount <= $paidAmount && $orderamount > 0) {
                $status = 'paid';
            } elseif ($paidAmount == 0 && $originalQty > 0 && $returnedQty >= $originalQty) {
                $status = 'returned_fully';
            } elseif ($paidAmount > 0 && ($orderamount - $paidAmount) > 0 && $returnedQty == 0) {
                $status = 'partial_paid';
            } elseif ($paidAmount == 0 && $returnedQty > 0 && $returnedQty < $originalQty) {
                $status = 'partial_returned';
            } elseif ($paidAmount > 0 && $returnedQty > 0) {
                $status = 'partial_both';
            } else {
                $status = 'unpaid';
            }

            $invoiceStatusCounts[$status] = ($invoiceStatusCounts[$status] ?? 0) + 1;

            // الكميات المُحصّلة (تقريب لأعلى في الجزئي)
            if ($status === 'paid') {
                $collectedUnits += $originalQty;
            } elseif ($status === 'partial_paid') {
                if ($orderamount > 0) {
                    $fraction = min(1, $paidAmount / $orderamount);
                    $collectedUnits += (int) ceil($fraction * $originalQty);
                }
            } elseif ($status === 'partial_both') {
                $netQty    = max(0, $originalQty - $returnedQty);
                $netAmount = max(0.0, $orderamount - $returnedAmount);
                if ($netAmount > 0) {
                    $fraction = min(1, $paidAmount / $netAmount);
                    $collectedUnits += (int) ceil($fraction * $netQty);
                }
            }
        }

        // إجمالي فواتير "محصلة أو فيها تحصيل" = paid + partial_paid + partial_both
        $collectedLikeCount = ($invoiceStatusCounts['paid'] ?? 0)
                            + ($invoiceStatusCounts['partial_paid'] ?? 0)
                            + ($invoiceStatusCounts['partial_both'] ?? 0);
    $paidInvoices = array_sum([
        $invoiceStatusCounts['paid'] ?? 0,
        $invoiceStatusCounts['partial_paid'] ?? 0,
        $invoiceStatusCounts['partial_both'] ?? 0,
    ]);

    $unpaidInvoices = array_sum([
        $invoiceStatusCounts['unpaid'] ?? 0,
        $invoiceStatusCounts['partial_returned'] ?? 0,
            $invoiceStatusCounts['partial_paid'] ?? 0,

    ]);
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
