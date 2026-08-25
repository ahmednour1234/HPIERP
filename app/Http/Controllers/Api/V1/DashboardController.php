<?php

namespace App\Http\Controllers\Api\V1;

use Carbon\Carbon;
use App\CPU\Helpers;
use App\Models\Product;
use App\Models\Transection;
use App\Models\Installment;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\StockLimitedProductsResource;

class DashboardController extends Controller
{
    public function __construct(
         private Transection $transection,
                private Order $order,
        private Installment $installment,
        private Product $product
    ){}

    /**
     * @param Request $request
     * @return JsonResponse|void
     */
public function getIndex(Request $request)
{
    // ───── حدود الفترة الزمنية (من أول الشهر حتى أول يوم من الشهر التالي) ─────
    $startOfMonth      = \Carbon\Carbon::now()->startOfMonth()->toDateString();           // 'YYYY-MM-01'
    $firstOfNextMonth  = \Carbon\Carbon::now()->addMonthNoOverflow()->startOfMonth();     // Carbon
    $endOfMonth30      = $firstOfNextMonth->toDateString();                               // 'YYYY-MM-01' (الشهر التالي)

    $authId = auth()->id();

    /* ───── المبالغ الدائنة/المدينة (Transactions) ───── */
    $total_payable_debit  = $this->transection
        ->where('tran_type', 4)
        ->whereBetween('created_at', [$startOfMonth, $endOfMonth30])
        ->sum('amount');

    $total_payable_credit = $this->transection
        ->where('tran_type', 7)
        ->whereBetween('created_at', [$startOfMonth, $endOfMonth30])
        ->sum('amount');

    $total_payable = $total_payable_credit - $total_payable_debit;

    /* ملاحظة: لو لديك تعريفات مختلفة لـ receivable غيّر tran_type هنا */
    $total_receivable_debit  = $this->transection
        ->where('tran_type', 4)
        ->whereBetween('created_at', [$startOfMonth, $endOfMonth30])
        ->sum('amount');

    $total_receivable_credit = $this->transection
        ->where('tran_type', 7)
        ->whereBetween('created_at', [$startOfMonth, $endOfMonth30])
        ->sum('amount');

    $total_receivable = $total_receivable_credit - $total_receivable_debit;

    /* ───── تحديد فواتير محصّلة/غير محصلة (مع استثناء الإرجاع الكامل) ───── */
    $fullyReturnedOrderIds = \App\Models\Order::where('type', 4)
        ->whereIn('id', function ($sub) {
            $sub->select('parent_id')
                ->from('orders')
                ->whereNotNull('parent_id')
                ->groupBy('parent_id')
                ->havingRaw('SUM((SELECT quantity FROM order_details WHERE orders.id = order_details.order_id)) >= 
                             (SELECT SUM(quantity) FROM order_details WHERE order_details.order_id = parent_id)');
        })->pluck('id');

    $collectedInvoicesQuery = \App\Models\Order::whereColumn('order_amount', '<=', 'transaction_reference')
        ->where('owner_id', $authId)
        ->where('type', 4)
        ->whereBetween('created_at', [$startOfMonth, $endOfMonth30]);

    $uncollectedInvoicesQuery = \App\Models\Order::whereColumn('order_amount', '>', 'transaction_reference')
        ->where('owner_id', $authId)
        ->where('type', 4);

    if ($fullyReturnedOrderIds->isNotEmpty()) {
        $collectedInvoicesQuery->whereNotIn('id', $fullyReturnedOrderIds);
        $uncollectedInvoicesQuery->whereNotIn('id', $fullyReturnedOrderIds);
    }

    $collectedInvoicesCount   = $collectedInvoicesQuery->count();
    $uncollectedInvoicesCount = $uncollectedInvoicesQuery->count();

    /* ───── إجماليات الدخل حسب وسيلة الدفع ───── */
    $total_income = $this->order
        ->where('active', 1)
        ->where('type', 4)
        ->where('cash', 1) // نقدي
        ->where('owner_id', $authId)
        ->whereBetween('created_at', [$startOfMonth, $endOfMonth30])
        ->sum('order_amount');

    $total_credit = $this->order
        ->where('active', 1)
        ->where('type', 4)
        ->where('cash', 2) // آجل
        ->where('owner_id', $authId)
        ->sum('order_amount');

    $total_credit_installment = $this->order
        ->where('active', 1)
        ->where('type', 4)
        ->where('cash', 2)
        ->where('owner_id', $authId)
        ->sum('transaction_reference');

    $total_shabaka = $this->order
        ->where('active', 1)
        ->where('type', 4)
        ->where('cash', 3) // شبكة
        ->where('owner_id', $authId)
        ->whereBetween('created_at', [$startOfMonth, $endOfMonth30])
        ->sum('order_amount');

    /* ───── المرتجعات ───── */
    $refund_total = $this->order
        ->where('active', 1)
        ->where('type', 7)
        ->where('owner_id', $authId)
        ->whereBetween('created_at', [$startOfMonth, $endOfMonth30])
        ->sum('order_amount');

    /* ───── الأقساط خلال الشهر ───── */
    $installment = $this->installment
        ->where('seller_id', $authId)
        ->whereBetween('created_at', [$startOfMonth, $endOfMonth30])
        ->sum('total_price');

    /* ───── زوار/نتائج الزيارات/رواتب/إجازات… ───── */
    $startOfMonthCarbon = \Carbon\Carbon::now()->startOfMonth();
    $endOfMonthCarbon   = \Carbon\Carbon::now()->endOfMonth();

    $total_visitors = \DB::table('admins')
        ->where('id', $authId)
        ->sum('visitors');

    $total_result_visitors = \DB::table('result_visitors')
        ->where('admin_id', $authId)
        ->whereBetween('created_at', [$startOfMonthCarbon, $endOfMonthCarbon])
        ->count();

    $salary   = \DB::table('admins')->where('id', $authId)->sum('salary');
    $holidays = \DB::table('admins')->where('id', $authId)->sum('holidays');
    $balance  = \DB::table('admins')->where('id', $authId)->sum('balance');
    $credit   = \DB::table('admins')->where('id', $authId)->sum('credit');

    $percentage = $total_visitors > 0
        ? round(($total_result_visitors / $total_visitors) * 100, 2)
        : 0;
    // يُرجّع نفس القيمة كما في كودك الأصلي (sprintf بدون فورمات)
    $valuevisit = sprintf($total_result_visitors , $percentage);

    /* ─────────────────────────────────────────────────────────────
       إحصائيات type=4 لنفس المستخدم/الفترة (بطريقة الكروت المعروضة)
       - productCount, quantitySum, collectedUnits, priceSum
       - orderAmountType4, orderAmountType7, transactionRefType4, amountDue
       - invoiceStatusCounts (paid/unpaid/returned_fully/partial_paid/partial_returned/partial_both)
    ───────────────────────────────────────────────────────────── */

    // إجمالي مبالغ البيع/المرتجع/المحصّل ضمن الفترة
    $orderAmountType4 = \App\Models\Order::where('type', 4)
        ->where('owner_id', $authId)
        ->sum('order_amount');

    $orderAmountType7 = \App\Models\Order::where('type', 7)
        ->where('owner_id', $authId)
        ->sum('order_amount');

    $transactionRefType4 = \App\Models\Order::where('type', 4)
        ->where('owner_id', $authId)
        ->sum('transaction_reference');

    $amountDue = $orderAmountType4 - $orderAmountType7 - $transactionRefType4;

    // تفاصيل المنتجات المباعة ضمن الفترة
    $orderDetailsType4 = \App\Models\OrderDetail::whereHas('order', function ($q) use ($authId, $startOfMonth, $endOfMonth30) {
            $q->where('type', 4)
              ->where('owner_id', $authId)
      ;
        })
        ->get();

    $productCount = $orderDetailsType4->groupBy('product_details->id')->count();
    $quantitySum  = $orderDetailsType4->sum('quantity');
    $priceSum     = $orderDetailsType4->sum(function ($d) {
        return (float)($d->price ?? 0) * (float)($d->quantity ?? 0);
    });

    // حساب collectedUnits + عدّادات الحالات
    $ordersType4 = \App\Models\Order::where('type', 4)
        ->where('owner_id', $authId)
        ->with('details')
        ->get();

    // إحضار المرتجعات المرتبطة بهذه الطلبات (type=7)
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

        // حساب الكميات المُحصّلة (تقريب لأعلى كما طلبت: 9.1 ⇒ 10)
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
        // الحالات الأخرى لا تضاف
    }

    /* ───── تجميع الاستجابة بنفس الشكل السابق مع إضافة العناصر الجديدة ───── */
    $revenueSummary = [
        [
            'type'  => 1,
            'ar'    => 'اجمالي المبيعات',
            'en'    => 'Total Sales',
            'value' => ($total_income + $total_credit + $total_shabaka) ?? 0,
        ],
        [
            'type'  => 1,
            'ar'    => 'إجمالي المرتجعات',
            'en'    => 'Total Refunds',
            'value' => $refund_total ?? 0,
        ],
        [
            'type'  => 1,
            'ar'    => 'مبالغ لم يتم تحصيلها (أجل)',
            'en'    => 'Total Credit Sales',
            'value' => ($total_credit - $total_credit_installment) ?? 0,
        ],
        [
            'type'  => 1,
            'ar'    => 'اجمالي التحصيلات من الأجل',
            'en'    => 'Total Installments',
            'value' => $installment ?? 0,
        ],
        [
            'type'  => 1,
            'ar'    => 'اجمالي التحصيلات النقدية',
            'en'    => 'Total Cash Sales',
            'value' => $total_income ?? 0,
        ],
        [
            'type'  => 1,
            'ar'    => 'اجمالي التحصيلات',
            'en'    => 'Total Collections',
            'value' => ($installment + $total_income) ?? 0,
        ],
        [
            'ar'       => 'عدد إيصالات محصلة',
            'en'       => 'Collected Invoices',
            'currency' => 0,
'value' => array_sum([
    $invoiceStatusCounts['paid'] ?? 0,
    $invoiceStatusCounts['partial_paid'] ?? 0,
    $invoiceStatusCounts['partial_both'] ?? 0,
]),
        ],
        [
            'ar'       => 'عدد إيصالات غير محصلة',
            'en'       => 'Uncollected Invoices',
            'currency' => 0,
            'value'    =>  array_sum([
    $invoiceStatusCounts['unpaid'] ?? 0,
    $invoiceStatusCounts['partial_paid'] ?? 0,
    $invoiceStatusCounts['partial_returned'] ?? 0,
]) ,
        ],
        [
            'ar'    => 'تحصيلات مطلوب تحويلها',
            'en'    => 'Debtor',
            'value' => $credit ?? 0,
        ],
        [
            'type'     => 1,
            'ar'       => 'اجمالي الزيارات المطلوبة',
            'en'       => 'Total Required Visits',
            'currency' => 0,
            'value'    => $total_visitors ?? 0,
        ],
        [
            'type'     => 1,
            'ar'       => 'عدد الزيارات المنفذة',
            'en'       => 'Completed Visits',
            'currency' => 0,
            'value'    => $valuevisit, // مطابق لسلوكك السابق
        ],
        [
            'ar'       => 'نسبة الزيارات المنفذة',
            'en'       => 'Percentage Visits',
            'currency' => 0,
            'value'    => $percentage,
        ],
        [
            'ar'       => 'رصيد الاجازات',
            'en'       => 'Holidays Balance',
            'currency' => 0,
            'value'    => $holidays ?? 0,
        ],
        [
            'ar'    => 'الراتب',
            'en'    => 'Salary',
            'value' => $salary ?? 0,
        ],

    ];

    // حساب أعلى/أقل قيمة (يحافظ على نفس شكل الاستجابة)
    $values   = array_map(function ($row) { return (float) ($row['value'] ?? 0); }, $revenueSummary);
    $maxValue = max($values);
    $minValue = min($values);

    return response()->json([
        'revenueSummary' => $revenueSummary,
        'maxValue' => [
            'ar'    => 'القيمة الأكبر',
            'en'    => 'Highest Value',
            'value' => $maxValue,
        ],
        'minValue' => [
            'ar'    => 'القيمة الأصغر',
            'en'    => 'Lowest Value',
            'value' => $minValue,
        ],
    ], 200);

    // ملاحظة: كود "today/month" في نسختك الأصلية يأتي بعد return وبالتالي لا يُنفّذ.
    // لو أردته فعّال، انقل شرط today/month قبل الـ return، أو أنشئ endpoints منفصلة.
}


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function productLimitedStockList(Request $request): JsonResponse
    {
        $limit = $request['limit'] ?? 10;
        $offset = $request['offset'] ?? 1;

        $stock_limit = Helpers::get_business_settings('stock_limit');
        $stock_limited_product = $this->product->with('unit', 'supplier')->where('quantity', '<', $stock_limit)->orderBy('quantity')->latest()->paginate($limit, ['*'], 'page', $offset);
        $stock_limited_products = StockLimitedProductsResource::collection($stock_limited_product);

        return response()->json([
            'total' => $stock_limited_products->total(),
            'offset' => $offset,
            'limit' => $limit,
            'stock_limited_products' => $stock_limited_products->items(),
        ], 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function quantityIncrease(Request $request): JsonResponse
    {
        DB::table('products')->where('id', $request->id)->update(['quantity' => $request->quantity]);
        return response()->json(['message' => 'Product quantity updated successsfully']);
    }

    /**
     * @param Request $request
     * @return JsonResponse|void
     */
    public function getFilter(Request $request)
    {
        if ($request->statistics_type == 'overall') {
            $total_payable_debit = $this->transection->where('tran_type', 'Payable')->where('debit', 1)->sum('amount');
            $total_payable_credit = $this->transection->where('tran_type', 'Payable')->where('credit', 1)->sum('amount');
            $total_payable = $total_payable_credit - $total_payable_debit;

            $total_receivable_debit = $this->transection->where('tran_type', 'Receivable')->where('debit', 1)->sum('amount');
            $total_receivable_credit = $this->transection->where('tran_type', 'Receivable')->where('credit', 1)->sum('amount');
            $total_receivable = $total_receivable_credit - $total_receivable_debit;
            $account = [
                'total_income' => $this->transection->where('tran_type', 'Income')->sum('amount'),
                'total_expense' => $this->transection->where('tran_type', 'Expense')->sum('amount'),
                'total_payable' => $total_payable,
                'total_receivable' => $total_receivable,
            ];
            return response()->json([
                'success' => true,
                'message' => "Overall Statistics",
                'data' => $account
            ], 200);
        } elseif ($request->statistics_type == 'today') {
            $total_payable_debit = $this->transection->where('tran_type', 'Payable')->whereDay('date', '=', Carbon::today())->where('debit', 1)->sum('amount');
            $total_payable_credit = $this->transection->where('tran_type', 'Payable')->whereDay('date', '=', Carbon::today())->where('credit', 1)->sum('amount');
            $total_payable = $total_payable_credit - $total_payable_debit;

            $total_receivable_debit = $this->transection->where('tran_type', 'Receivable')->whereDay('date', '=', Carbon::today())->where('debit', 1)->sum('amount');
            $total_receivable_credit = $this->transection->where('tran_type', 'Receivable')->whereDay('date', '=', Carbon::today())->where('credit', 1)->sum('amount');
            $total_receivable = $total_receivable_credit - $total_receivable_debit;

            $account = [
                'total_income' => $this->transection->where('tran_type', 'Income')->whereDay('date', '=', Carbon::today())->sum('amount'),
                'total_expense' => $this->transection->where('tran_type', 'Expense')->whereDay('date', '=', Carbon::today())->sum('amount'),
                'total_payable' => $total_payable,
                'total_receivable' => $total_receivable,
            ];
            return response()->json([
                'success' => true,
                'message' => "Today Statistics",
                'data' => $account
            ], 200);
        } elseif ($request->statistics_type == 'month') {

            $total_payable_debit = $this->transection->where('tran_type', 'Payable')->whereMonth('date', '=', Carbon::today())->where('debit', 1)->sum('amount');
            $total_payable_credit = $this->transection->where('tran_type', 'Payable')->whereMonth('date', '=', Carbon::today())->where('credit', 1)->sum('amount');
            $total_payable = $total_payable_credit - $total_payable_debit;

            $total_receivable_debit = $this->transection->where('tran_type', 'Receivable')->whereMonth('date', '=', Carbon::today())->where('debit', 1)->sum('amount');
            $total_receivable_credit = $this->transection->where('tran_type', 'Receivable')->whereMonth('date', '=', Carbon::today())->where('credit', 1)->sum('amount');
            $total_receivable = $total_receivable_credit - $total_receivable_debit;

            $account = [
                'total_income' => $this->transection->where('tran_type', 'Income')->whereMonth('date', '=', Carbon::today())->sum('amount'),
                'total_expense' => $this->transection->where('tran_type', 'Expense')->whereMonth('date', '=', Carbon::today())->sum('amount'),
                'total_payable' => $total_payable,
                'total_receivable' => $total_receivable,
            ];
            return response()->json([
                'success' => true,
                'message' => "Monthly Statistics",
                'data' => $account
            ], 200);
        }
    }

    /**
     * @return JsonResponse
     */
    public function incomeRevenue(): JsonResponse
    {
        $year_wise_expense = Transection::selectRaw("sum(`amount`) as 'total_amount', YEAR(`date`) as 'year', MONTH(`date`) as 'month'")->where(['tran_type' => 'Expense'])
            ->groupBy('month')
            ->orderBy('year')
            ->get();

        $year_wise_income = Transection::selectRaw("sum(`amount`) as 'total_amount', YEAR(`date`) as 'year', MONTH(`date`) as 'month'")->where(['tran_type' => 'Income'])
            ->groupBy('month')
            ->orderBy('year')
            ->get();

        return response()->json([
            'year_wise_expense' => $year_wise_expense,
            'year_wise_income' => $year_wise_income
        ], 200);
    }
}
