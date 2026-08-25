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

    // Monthly cash and credit sales per month
    $monthly_income  = [];
    $monthly_expense = [];
    for ($i = 1; $i <= 12; $i++) {
        $start = now()->startOfYear()->addMonths($i - 1)->toDateString();
        $end   = now()->startOfYear()->addMonths($i - 1)->endOfMonth()->toDateString();

        // نقدي (cash=1, type=4)
        $monthly_income[$i] = $this->order
            ->whereIn('owner_id', $sellerIds)
            ->where('cash', 1)
            ->where('type', 4)
            ->when($from && $to, fn($q) => $q->whereBetween('created_at', [$from, $to]))
            ->whereBetween('created_at', [$start, $end])
            ->sum('collected_cash');

        // آجلة (cash=2, type=4)
        $monthly_expense[$i] = $this->order
            ->whereIn('owner_id', $sellerIds)
            ->where('cash', 2)
            ->where('type', 4)
            ->when($from && $to, fn($q) => $q->whereBetween('created_at', [$from, $to]))
            ->whereBetween('created_at', [$start, $end])
            ->sum('collected_cash');
    }
$monthly_visitors=[];
$monthly_result_visitor=[];
    for ($i = 1; $i <= 12; $i++) {
        $start = now()->startOfYear()->addMonths($i - 1)->toDateString();
        $end   = now()->startOfYear()->addMonths($i - 1)->endOfMonth()->toDateString();

        // نقدي (cash=1, type=4)
        $monthly_visitors[$i] = $this->visitor
            ->whereIn('seller_id', $sellerIds)
            ->when($from && $to, fn($q) => $q->whereBetween('created_at', [$from, $to]))
            ->whereBetween('created_at', [$start, $end])
            ->count();

        // آجلة (cash=2, type=4)
        $monthly_result_visitor[$i] = $this->result_visitor
                    ->whereIn('admin_id', $sellerIds)
            ->when($from && $to, fn($q) => $q->whereBetween('created_at', [$from, $to]))
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }
    $monthly_installments=[];
        for ($i = 1; $i <= 12; $i++) {
        $start = now()->startOfYear()->addMonths($i - 1)->toDateString();
        $end   = now()->startOfYear()->addMonths($i - 1)->endOfMonth()->toDateString();

        // نقدي (cash=1, type=4)
        $monthly_installments[$i] = $this->installmentall
            ->whereIn('seller_id', $sellerIds)
            ->when($from && $to, fn($q) => $q->whereBetween('created_at', [$from, $to]))
            ->whereBetween('created_at', [$start, $end])
            ->sum('total_price');
    }

    // Determine days for daily arrays
    $days = ($from && $to)
        ? now()->parse($from)->diffInDays(now()->parse($to)) + 1
        : now()->daysInMonth;

    // Daily cash & credit sales
    $last_month_income  = [];
    $last_month_expense = [];
    foreach (range(0, $days - 1) as $offset) {
        $day = ($from && $to)
            ? now()->parse($from)->addDays($offset)->toDateString()
            : now()->startOfMonth()->addDays($offset)->toDateString();

        $last_month_income[$offset + 1] = $this->order
            ->whereIn('owner_id', $sellerIds)
            ->where('cash', 1)
            ->where('type', 4)
            ->when($from && $to, fn($q) => $q->where('created_at', $day))
            ->sum('collected_cash');

        $last_month_expense[$offset + 1] = $this->order
            ->whereIn('owner_id', $sellerIds)
            ->where('cash', 2)
            ->where('type', 4)
            ->when($from && $to, fn($q) => $q->where('created_at', $day))
            ->sum('collected_cash');
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

    return view('admin-views.dashboard', compact(
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
}
