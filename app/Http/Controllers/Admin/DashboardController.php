<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\HistoryTransection;
use App\Models\Transection;
use App\CPU\Helpers;
use App\Exceptions\Handler;
use Carbon\Carbon;
use App\Models\Account;
use App\Models\AdminSeller;
use App\Models\Product;
use App\Models\Region;
use App\Models\Installment;
use App\Models\Order;
use App\Models\Stock;
use App\Models\Visitor;
use App\Models\ResultVisitor;
use App\Models\Seller;
use App\Models\HistoryInstallment;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use function App\CPU\translate;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(
        private Transection $transection,
        private HistoryInstallment $installment,
                private Installment $installmentall,
        private Account $account,
        private Product $product,
        private Seller $seller,
        private Order $order,
                private Visitor $visitor,
                                private ResultVisitor $result_visitor,

    ){}

public function dashboard(Request $request): Factory|View|Application
{
    $adminId = Auth::guard('admin')->id(); // Use the 'admin' guard to get the admin's ID

    // Optional filters from the request
    $from = $request->input('from_date');
    $to   = $request->input('to_date');

    // قائمة «نوع الإحصاءات» كانت بلا أثر: الواجهة تضبط التاريخين للأرباع
    // والنطاق المخصص فقط، وتمسحهما لباقي الخيارات، فكانت «اليوم» و«الشهر»
    // و«السنة» تعرض نفس أرقام «الإحصاءات الكلية» تمامًا.
    //
    // نشتق المدى هنا من نوع الإحصاءات حين لا تصل تواريخ صريحة، فيصبح
    // الاختيار فعّالًا دون تغيير الواجهة.
    if (!$from || !$to) {
        $range = match ($request->input('statistics_type')) {
            'today'    => [now()->startOfDay(), now()->endOfDay()],
            'month'    => [now()->startOfMonth(), now()->endOfMonth()],
            'year'     => [now()->startOfYear(), now()->endOfYear()],
            'quarter1' => [now()->startOfYear(), now()->startOfYear()->addMonths(3)->subDay()->endOfDay()],
            'quarter2' => [now()->startOfYear()->addMonths(3), now()->startOfYear()->addMonths(6)->subDay()->endOfDay()],
            'quarter3' => [now()->startOfYear()->addMonths(6), now()->startOfYear()->addMonths(9)->subDay()->endOfDay()],
            'quarter4' => [now()->startOfYear()->addMonths(9), now()->endOfYear()],
            default    => null, // overall / custom بلا تواريخ = بلا تقييد
        };

        if ($range) {
            [$from, $to] = [$range[0]->toDateTimeString(), $range[1]->toDateTimeString()];
        }
    }

    // Retrieve all seller IDs associated with the admin
    $sellerIds = AdminSeller::where('admin_id', $adminId)
                    ->pluck('seller_id');

    // Filter active installments, orders, and stocks by seller and optional date range
    $installments = Installment::where('active', 1)
        ->whereIn('seller_id', $sellerIds)
        ->when($from && $to, fn($q) => $q->whereBetween('created_at', [$from, $to]))
        ->get();

    $orders = Order::where('active', 1)
        ->whereIn('owner_id', $sellerIds)
        ->when($from && $to, fn($q) => $q->whereBetween('created_at', [$from, $to]))
        ->get();

    $stocks = Stock::where('active', 1)
        ->whereIn('seller_id', $sellerIds)
        ->when($from && $to, fn($q) => $q->whereBetween('updated_at', [$from, $to]))
        ->get();

    // Transactions filtering
    $transactions = $this->transection
        ->when($from && $to, fn($q) => $q->whereBetween('created_at', [$from, $to]));

    // Calculate payable and receivable totals using filtered transactions
    $total_payable_debit  = (clone $transactions)
        ->where('tran_type', '4')
        ->where('cash', 1)
        ->sum('amount');
    $total_payable_credit = (clone $transactions)
        ->where('tran_type', '4')
        ->where('cash', 2)
        ->sum('amount');
    $total_payable = $total_payable_credit - $total_payable_debit;

    $total_receivable_debit  = (clone $transactions)
        ->where('tran_type', '7')
        ->where('cash', 1)
        ->sum('amount');
    $total_receivable_credit = (clone $transactions)
        ->where('tran_type', '7')
        ->where('cash', 2)
        ->sum('amount');
    $total_receivable = $total_receivable_credit - $total_receivable_debit;
$total_income_collected  = (clone $this->order)
    ->whereIn('owner_id', $sellerIds)
    ->where('cash', 1)
    ->where('type', 4)
    ->when($from && $to, fn($q) => $q->whereBetween('created_at', [$from, $to]))
    ->sum('collected_cash');

$total_expense_collected = (clone $this->order)
    ->whereIn('owner_id', $sellerIds)
    ->where('cash', 2)
    ->where('type', 4)
    ->when($from && $to, fn($q) => $q->whereBetween('created_at', [$from, $to]))
    ->sum('collected_cash');

$total_installment_base = $installments->sum('total_price');
    // Aggregate account overview
    $account = [
        'total_income'      => (clone $this->order)
            ->whereIn('owner_id', $sellerIds)
            ->where('cash', 1)
            ->where('type', 4)
            ->when($from && $to, fn($q) => $q->whereBetween('created_at', [$from, $to]))
            ->sum('order_amount'),
        'total_expense'     => (clone $this->order)
            ->whereIn('owner_id', $sellerIds)
            ->where('cash', 2)
            ->where('type', 4)
            ->when($from && $to, fn($q) => $q->whereBetween('created_at', [$from, $to]))
            ->sum('order_amount'),
                'total_incomecollected'      => (clone $this->order)
            ->whereIn('owner_id', $sellerIds)
            ->where('cash', 1)
            ->where('type', 4)
            ->when($from && $to, fn($q) => $q->whereBetween('created_at', [$from, $to]))
            ->sum('collected_cash'),
        'total_expensecollected'     => (clone $this->order)
            ->whereIn('owner_id', $sellerIds)
            ->where('cash', 2)
            ->where('type', 4)
            ->when($from && $to, fn($q) => $q->whereBetween('created_at', [$from, $to]))
            ->sum('collected_cash'),
 'total_incomecollected'      => $total_income_collected,
    'total_expensecollected'     => $total_expense_collected,

    'total_installment' => $total_installment_base + $total_income_collected + $total_expense_collected,
        'total_refund'      => (clone $this->order)
            ->whereIn('owner_id', $sellerIds)
            ->where('type', 7)
            ->when($from && $to, fn($q) => $q->whereBetween('created_at', [$from, $to]))
            ->sum(DB::raw('order_amount')),
        'total_payable'     => $total_payable,
        'total_receivable'  => $total_receivable,
    ];

    // تعبيرا الشهر واليوم يختلفان بين MySQL وSQLite، فيُختاران حسب المحرّك
    // بدل تثبيت صيغة واحدة تنكسر على الآخر.
    $driver = \DB::connection()->getDriverName();
    $monthExpr = $driver === 'sqlite' ? "CAST(strftime('%m', created_at) AS INTEGER)" : 'MONTH(created_at)';
    $dayExpr   = $driver === 'sqlite' ? "date(created_at)" : 'DATE(created_at)';

    // مبيعات كل شهر، نقدي وآجل.
    //
    // كانت حلقة تنفّذ استعلامين لكل شهر (24 استعلامًا)، وكل واحد يمسح جدول
    // الطلبات من جديد. GROUP BY يعطي الاثني عشر شهرًا في استعلام واحد.
    $yearStart = now()->startOfYear();
    $yearEnd   = now()->endOfYear();

    $monthly_income  = array_fill(1, 12, 0);
    $monthly_expense = array_fill(1, 12, 0);

    $monthlyRows = (clone $this->order)
        ->whereIn('owner_id', $sellerIds)
        ->where('type', 4)
        ->whereIn('cash', [1, 2])
        ->whereBetween('created_at', [$yearStart, $yearEnd])
        ->when($from && $to, fn($q) => $q->whereBetween('created_at', [$from, $to]))
        ->selectRaw($monthExpr . ' as m, cash, SUM(collected_cash) as total')
        ->groupBy('m', 'cash')
        ->get();

    foreach ($monthlyRows as $row) {
        $m = (int) $row->m;
        if ($m < 1 || $m > 12) {
            continue;
        }

        if ((int) $row->cash === 1) {
            $monthly_income[$m] = (float) $row->total;
        } else {
            $monthly_expense[$m] = (float) $row->total;
        }
    }
    // الزيارات المخططة والمنفذة لكل شهر: استعلام واحد لكل جدول بدل 12.
    $monthly_visitors       = array_fill(1, 12, 0);
    $monthly_result_visitor = array_fill(1, 12, 0);

    $visitorRows = (clone $this->visitor)
        ->whereIn('seller_id', $sellerIds)
        ->whereBetween('created_at', [$yearStart, $yearEnd])
        ->when($from && $to, fn($q) => $q->whereBetween('created_at', [$from, $to]))
        ->selectRaw($monthExpr . ' as m, COUNT(*) as total')
        ->groupBy('m')
        ->pluck('total', 'm');

    foreach ($visitorRows as $m => $total) {
        if ((int) $m >= 1 && (int) $m <= 12) {
            $monthly_visitors[(int) $m] = (int) $total;
        }
    }

    $resultRows = (clone $this->result_visitor)
        ->whereIn('admin_id', $sellerIds)
        ->whereBetween('created_at', [$yearStart, $yearEnd])
        ->when($from && $to, fn($q) => $q->whereBetween('created_at', [$from, $to]))
        ->selectRaw($monthExpr . ' as m, COUNT(*) as total')
        ->groupBy('m')
        ->pluck('total', 'm');

    foreach ($resultRows as $m => $total) {
        if ((int) $m >= 1 && (int) $m <= 12) {
            $monthly_result_visitor[(int) $m] = (int) $total;
        }
    }
    // التحصيلات لكل شهر: استعلام واحد بدل 12.
    $monthly_installments = array_fill(1, 12, 0);

    $installmentRows = (clone $this->installmentall)
        ->whereIn('seller_id', $sellerIds)
        ->whereBetween('created_at', [$yearStart, $yearEnd])
        ->when($from && $to, fn($q) => $q->whereBetween('created_at', [$from, $to]))
        ->selectRaw($monthExpr . ' as m, SUM(total_price) as total')
        ->groupBy('m')
        ->pluck('total', 'm');

    foreach ($installmentRows as $m => $total) {
        if ((int) $m >= 1 && (int) $m <= 12) {
            $monthly_installments[(int) $m] = (float) $total;
        }
    }

    // Determine days for daily arrays
    $days = ($from && $to)
        ? now()->parse($from)->diffInDays(now()->parse($to)) + 1
        : now()->daysInMonth;

    // المبيعات اليومية.
    //
    // كانت حلقة باستعلامين لكل يوم (حتى 62 استعلامًا). والأهم أن قيد اليوم
    // كان داخل when($from && $to)، فبدون فلتر تاريخ لم يُطبَّق أصلًا وكانت
    // كل الأيام تعرض نفس الإجمالي. الآن التجميع بالتاريخ دائمًا.
    $rangeStart = ($from && $to) ? now()->parse($from)->startOfDay() : now()->startOfMonth();
    $rangeEnd   = $rangeStart->copy()->addDays($days - 1)->endOfDay();

    $last_month_income  = array_fill(1, $days, 0);
    $last_month_expense = array_fill(1, $days, 0);

    $dailyRows = (clone $this->order)
        ->whereIn('owner_id', $sellerIds)
        ->where('type', 4)
        ->whereIn('cash', [1, 2])
        ->whereBetween('created_at', [$rangeStart, $rangeEnd])
        ->selectRaw($dayExpr . ' as d, cash, SUM(collected_cash) as total')
        ->groupBy('d', 'cash')
        ->get();

    foreach ($dailyRows as $row) {
        // ترتيب اليوم داخل المدى المعروضة، لا رقم اليوم في الشهر.
        $slot = $rangeStart->diffInDays(now()->parse($row->d)) + 1;

        if ($slot < 1 || $slot > $days) {
            continue;
        }

        if ((int) $row->cash === 1) {
            $last_month_income[$slot] = (float) $row->total;
        } else {
            $last_month_expense[$slot] = (float) $row->total;
        }
    }

    // Low stock products and latest accounts
    $stock_limit = Helpers::get_business_settings('stock_limit');
    $products    = $this->product
        ->when($from && $to, fn($q) => $q->whereBetween('updated_at', [$from, $to]))
        ->where('quantity', '<', $stock_limit)
        ->orderBy('quantity')
        ->take(5)
        ->get();

    $accounts = $this->account->take(5)->get();
    $sellers  = $this->seller->where('role', 'seller')->get();

    // إحصائيات المناديب محسوبة هنا مرة واحدة بدل حسابها داخل حلقة القالب.
    $sellerStats = $this->sellerStats($sellerIds);

    return view('admin-views.dashboard', compact(
        'sellerStats',
        'account',
        'monthly_income',
        'monthly_expense',
        'accounts',
        'products',
        'last_month_income',
        'last_month_expense',
        'days',
        'sellers',
        'installments',
        'orders',
        'stocks',
        'monthly_visitors',
        'monthly_result_visitor',
        'monthly_installments'
        
    ));
}


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function account_stats(Request $request): JsonResponse
    {
        if($request->statistics_type=='overall')
        {
            $total_payable_debit = $this->transection->where('tran_type','Payable')->where('debit',1)->sum('amount');
            $total_payable_credit = $this->transection->where('tran_type','Payable')->where('credit',1)->sum('amount');
            $total_payable = $total_payable_credit - $total_payable_debit;

            $total_receivable_debit = $this->transection->where('tran_type','Receivable')->where('debit',1)->sum('amount');
            $total_receivable_credit = $this->transection->where('tran_type','Receivable')->where('credit',1)->sum('amount');
            $total_receivable = $total_receivable_credit - $total_receivable_debit;

            $account = [
                'total_income' => $this->transection->where('tran_type','Income')->sum('amount'),
                'total_expense' => $this->transection->where('tran_type','Expense')->sum('amount'),
                'total_payable' => $total_payable,
                'total_receivable' => $total_receivable,
            ];
        }elseif ($request->statistics_type=='today') {

            $total_payable_debit = $this->transection->where('tran_type','Payable')->whereDay('date', '=', Carbon::today())->where('debit',1)->sum('amount');
            $total_payable_credit = $this->transection->where('tran_type','Payable')->whereDay('date', '=', Carbon::today())->where('credit',1)->sum('amount');
            $total_payable = $total_payable_credit - $total_payable_debit;

            $total_receivable_debit = $this->transection->where('tran_type','Receivable')->whereDay('date', '=', Carbon::today())->where('debit',1)->sum('amount');
            $total_receivable_credit = $this->transection->where('tran_type','Receivable')->whereDay('date', '=', Carbon::today())->where('credit',1)->sum('amount');
            $total_receivable = $total_receivable_credit - $total_receivable_debit;

            $account = [
                'total_income' => $this->transection->where('tran_type','Income')->whereDay('date', '=', Carbon::today())->sum('amount'),
                'total_expense' => $this->transection->where('tran_type','Expense')->whereDay('date', '=', Carbon::today())->sum('amount'),
                'total_payable' => $total_payable,
                'total_receivable' => $total_receivable,
            ];
        }elseif ($request->statistics_type=='month') {

            $total_payable_debit = $this->transection->where('tran_type','Payable')->whereMonth('date', '=', Carbon::today())->where('debit',1)->sum('amount');
            $total_payable_credit = $this->transection->where('tran_type','Payable')->whereMonth('date', '=', Carbon::today())->where('credit',1)->sum('amount');
            $total_payable = $total_payable_credit - $total_payable_debit;

            $total_receivable_debit = $this->transection->where('tran_type','Receivable')->whereMonth('date', '=', Carbon::today())->where('debit',1)->sum('amount');
            $total_receivable_credit = $this->transection->where('tran_type','Receivable')->whereMonth('date', '=', Carbon::today())->where('credit',1)->sum('amount');
            $total_receivable = $total_receivable_credit - $total_receivable_debit;

            $account = [
                'total_income' => $this->transection->where('tran_type','Income')->whereMonth('date', '=', Carbon::today())->sum('amount'),
                'total_expense' => $this->transection->where('tran_type','Expense')->whereMonth('date', '=', Carbon::today())->sum('amount'),
                'total_payable' => $total_payable,
                'total_receivable' => $total_receivable,
            ];
        }
        return response()->json([
            'view'=> view('admin-views.partials._dashboard-balance-stats',compact('account'))->render()
        ],200);
    }

    public function regionList(): Factory|View|Application
    {
        $regions = Region::paginate(Helpers::pagination_limit());
        return view('admin-views.seller.regions.index', compact('regions'));
    }

    public function regionStore(Request $request): Factory|RedirectResponse|Application
    {
        $reg = new Region();
        $reg->name = $request->region_name;
        $reg->save();

        Toastr::success(translate('Region created successfully'));
        return back();
    }
    
    public function regionEdit($id): Factory|View|Application
    {
        $region = Region::find($id);
        return view('admin-views.seller.regions.edit', compact('region'));
    }
    
    public function regionUpdate(Request $request, $id): Factory|RedirectResponse|Application
    {
        $reg = Region::find($id);
        $reg->name = $request->region_name;
        $reg->update();

        Toastr::success(translate('Region updated successfully'));
        return redirect()->route('admin.regions.list');
    }

    public function regionDelete($id)
    {
        $reg = Region::find($id);
        $reg->delete();
        Toastr::success(translate('Region deleted successfully'));
        return back();
    }

    /**
     * إحصائيات كل مندوب، محسوبة دفعة واحدة.
     *
     * كانت القالب يحسبها داخل حلقة على المناديب: عشرة استعلامات لكل مندوب،
     * بالإضافة إلى تحميل كل فواتير المندوب وتفاصيلها إلى الذاكرة لتحديد حالة
     * كل فاتورة. مع 13 مندوبًا صار العرض 10 ثوانٍ و151 استعلامًا.
     *
     * هنا تُقرأ البيانات مرة واحدة لكل المناديب وتُجمَّع في الذاكرة، فيبقى
     * عدد الاستعلامات ثابتًا مهما زاد عدد المناديب.
     */
    private function sellerStats($sellerIds): array
    {
        $ids = collect($sellerIds)->filter()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        // مجاميع الطلبات لكل مندوب ونوع، في استعلام واحد.
        $orderTotals = \App\Models\Order::whereIn('owner_id', $ids)
            ->whereIn('type', [4, 7])
            ->groupBy('owner_id', 'type')
            ->selectRaw('owner_id, type, COUNT(*) as cnt, SUM(order_amount) as amount, SUM(transaction_reference) as paid')
            ->get();

        // مجاميع CurrentOrder لكل مندوب/نوع/نقدي.
        $currentTotals = \App\Models\CurrentOrder::whereIn('owner_id', $ids)
            ->groupBy('owner_id', 'type', 'cash')
            ->selectRaw('owner_id, type, cash, COUNT(*) as cnt, SUM(order_amount) as amount')
            ->get();

        // فواتير البيع وتفاصيلها ومرتجعاتها: تحميل واحد للجميع.
        $orders = \App\Models\Order::whereIn('owner_id', $ids)
            ->where('type', 4)
            ->with('details:id,order_id,product_id,quantity,price')
            ->get(['id', 'owner_id', 'order_amount', 'transaction_reference']);

        $returnsByParent = \App\Models\Order::where('type', 7)
            ->whereIn('parent_id', $orders->pluck('id'))
            ->with('details:id,order_id,product_id,quantity,price')
            ->get(['id', 'parent_id'])
            ->groupBy('parent_id');

        $stocks = \App\Models\Stock::whereIn('seller_id', $ids)
            ->get(['seller_id', 'main_stock', 'stock'])
            ->groupBy('seller_id');

        $stats = [];

        foreach ($ids as $sellerId) {
            $sellerOrders = $orders->where('owner_id', $sellerId);

            $collectedUnits = 0;
            $statusCounts = [
                'paid' => 0, 'unpaid' => 0, 'returned_fully' => 0,
                'partial_paid' => 0, 'partial_returned' => 0, 'partial_both' => 0,
            ];

            $productIds = [];
            $quantitySum = 0.0;
            $priceSum = 0.0;

            foreach ($sellerOrders as $o) {
                $originalQty = (float) $o->details->sum('quantity');
                $paidAmount  = (float) $o->transaction_reference;
                $orderAmount = (float) $o->order_amount;

                foreach ($o->details as $d) {
                    $productIds[$d->product_id] = true;
                    $quantitySum += (float) $d->quantity;
                    $priceSum    += (float) $d->price * (float) $d->quantity;
                }

                $returns     = $returnsByParent->get($o->id, collect());
                $returnedQty = (float) $returns->flatMap->details->sum('quantity');

                if ($orderAmount <= $paidAmount && $orderAmount > 0) {
                    $status = 'paid';
                } elseif ($paidAmount == 0 && $originalQty > 0 && $returnedQty >= $originalQty) {
                    $status = 'returned_fully';
                } elseif ($paidAmount > 0 && ($orderAmount - $paidAmount) > 0 && $returnedQty == 0) {
                    $status = 'partial_paid';
                } elseif ($paidAmount == 0 && $returnedQty > 0 && $returnedQty < $originalQty) {
                    $status = 'partial_returned';
                } elseif ($paidAmount > 0 && $returnedQty > 0) {
                    $status = 'partial_both';
                } else {
                    $status = 'unpaid';
                }

                $statusCounts[$status]++;

                if ($status === 'paid') {
                    $collectedUnits += (int) $originalQty;
                } elseif ($status === 'partial_paid' && $orderAmount > 0) {
                    $collectedUnits += (int) ceil(min(1, $paidAmount / $orderAmount) * $originalQty);
                } elseif ($status === 'partial_both') {
                    // نفس معادلة القالب الأصلي: النسبة تُحسب على الصافي بعد
                    // خصم المرتجع، لا على قيمة الفاتورة كاملة.
                    $returnedAmount = (float) $returns->flatMap->details
                        ->sum(fn ($d) => (float) $d->price * (float) $d->quantity);

                    $netQty    = max(0, $originalQty - $returnedQty);
                    $netAmount = max(0.0, $orderAmount - $returnedAmount);

                    if ($netAmount > 0) {
                        $collectedUnits += (int) ceil(min(1, $paidAmount / $netAmount) * $netQty);
                    }
                }
            }

            $sellerStocks = $stocks->get($sellerId, collect());
            $moved = $sellerStocks->filter(fn ($st) => $st->main_stock != $st->stock);

            $t4 = $orderTotals->first(fn ($r) => $r->owner_id == $sellerId && (int) $r->type === 4);
            $t7 = $orderTotals->first(fn ($r) => $r->owner_id == $sellerId && (int) $r->type === 7);

            $cur = fn ($type, $cash) => (float) optional($currentTotals->first(
                fn ($r) => $r->owner_id == $sellerId && (int) $r->type === $type && (int) $r->cash === $cash
            ))->amount;

            $stats[$sellerId] = [
                'has_stock'      => $sellerStocks->isNotEmpty(),
                // القالب يعرض العدد فقط، فلا داعي لتمرير الصفوف نفسها.
                'stock_line_count' => $moved->count(),
                'remain_stock'   => (float) $moved->sum('stock'),
                'total_stock'    => (float) $moved->sum(fn ($st) => $st->main_stock - $st->stock),
                'order_count'    => (int) $currentTotals->where('owner_id', $sellerId)->sum('cnt'),
                'total_cash'     => $cur(4, 1),
                'total_credit'   => $cur(4, 2),
                'refund_total'   => (float) $currentTotals
                                        ->where('owner_id', $sellerId)
                                        ->where('type', 7)->sum('amount'),
                'amount_type_4'  => (float) optional($t4)->amount,
                'amount_type_7'  => (float) optional($t7)->amount,
                'paid_type_4'    => (float) optional($t4)->paid,
                'amount_due'     => (float) optional($t4)->amount - (float) optional($t7)->amount - (float) optional($t4)->paid,
                'product_count'  => count($productIds),
                'quantity_sum'   => $quantitySum,
                'price_sum'      => $priceSum,
                'collected_units' => $collectedUnits,
                'status_counts'  => $statusCounts,
                // نفس تعريفَي القالب الأصلي حرفيًا. لاحظ أن partial_paid
                // يُحتسب في الاثنين معًا؛ أبقيناه كما هو حتى لا تتغيّر الأرقام
                // المعروضة، فتغييره قرار عمل لا قرار أداء.
                'collected_receipts'   => $statusCounts['paid']
                                          + $statusCounts['partial_paid']
                                          + $statusCounts['partial_both'],
                'uncollected_receipts' => $statusCounts['unpaid']
                                          + $statusCounts['partial_returned']
                                          + $statusCounts['partial_paid'],
            ];
        }

        return $stats;
    }
}
