<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\CPU\Helpers;
use App\Models\Seller;
use App\Models\Stock;
use App\Models\SellerRegion;
use App\Models\Visitor;
use App\Models\Region;
use App\Models\ResultVisitor;
use App\Models\Customer;
use App\Models\Category;
use App\Models\AdminSeller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB; // Add this line
use function App\CPU\translate;
use Illuminate\Support\Facades\Auth;
use Rap2hpoutre\FastExcel\FastExcel;
use Carbon\Carbon;


class VisitorController extends Controller
{
    public function __construct(
        private Visitor $visitor,
                private Region $regions,
        private Seller $seller,
        private ResultVisitor $resultVisitor,
        private Customer $customer,
    ){}

    /**
     * @param Request $request
     * @return Application|Factory|View
     */
public function index(Request $request)
{
    

    // Retrieve the ID of the authenticated admin
    $adminId = Auth::guard('admin')->id();

    // Retrieve the seller IDs associated with the authenticated admin from the 'admin_sellers' table
    $sellerIds = AdminSeller::where('admin_id', $adminId)->pluck('seller_id');

    // Retrieve sellers associated with the authenticated admin
    $sellers = Seller::whereIn('id', $sellerIds)->where('role', 'seller')->get();    
    $regions = $this->regions->get();

    // Initialize query for Visitor model
    $query = Visitor::query();

    // Filter by seller_id if provided
    if ($request->has('seller_id') && $request->seller_id) {
        $query->where('seller_id', $request->seller_id);
    }

    // Filter by region_id if provided
    if ($request->has('region_id') && $request->region_id) {
        $query->whereHas('customer', function ($q) use ($request) {
            $q->where('region_id', $request->region_id);
        });
    }

    // Filter by date range if both from_date and to_date are provided
    if ($request->has('from_date') && $request->from_date && $request->has('to_date') && $request->to_date) {
        $query->whereBetween('date', [$request->from_date, $request->to_date]);
    } elseif ($request->has('from_date') && $request->from_date) {
        // If only from_date is provided, filter visitors after the from_date
        $query->whereDate('date', '>=', $request->from_date);
    } elseif ($request->has('to_date') && $request->to_date) {
        // If only to_date is provided, filter visitors before the to_date
        $query->whereDate('date', '<=', $request->to_date);
    }

    // Fetch the filtered visitors and paginate the result
    $visitors = $query->with('seller', 'customer')->paginate(10);

    // Return the view with visitors, sellers, and regions
    return view('admin-views.visitors.index', compact('visitors', 'sellers', 'regions'));
}

public function showResultVisitors(Request $request, $seller_id)
{
    // 1) Fetch seller
    $seller = $this->seller->find($seller_id);
    if (! $seller) {
        return redirect()->back()->withErrors(['error' => 'Seller not found']);
    }

    // 2) Base queries (مؤهلة بأسماء الجداول)
    $visitQuery        = $this->resultVisitor->where('admin_id', $seller_id); // Eloquent على result_visitors
    $requiredQuery     = DB::table('visitors')->where('visitors.seller_id', $seller_id);
    $ordersQuery       = DB::table('orders')->where('orders.owner_id', $seller_id);
    $installmentsQuery = DB::table('installments')->where('installments.seller_id', $seller_id);

    // مناطق الفلتر.
    $regions = \App\Models\Region::orderBy('name')->get(['id', 'name']);

    // 3) Shared filters
    if ($cust = $request->customer_id) {
        $visitQuery         = $visitQuery->where('customer_id', $cust);
        $requiredQuery      = $requiredQuery->where('visitors.customer_id', $cust);
        $ordersQuery        = $ordersQuery->where('orders.user_id', $cust);
        $installmentsQuery  = $installmentsQuery->where('installments.customer_id', $cust);
    }

    // بحث العميل بالكتابة بدل الاختيار من قائمة طويلة.
    if ($name = trim((string) $request->input('customer'))) {
        $matched = DB::table('customers')
            ->where('name', 'like', "%{$name}%")
            ->pluck('id');

        $visitQuery        = $visitQuery->whereIn('customer_id', $matched);
        $requiredQuery     = $requiredQuery->whereIn('visitors.customer_id', $matched);
        $ordersQuery       = $ordersQuery->whereIn('orders.user_id', $matched);
        $installmentsQuery = $installmentsQuery->whereIn('installments.customer_id', $matched);
    }

    // فلتر المنطقة، متعدد الاختيار. المنطقة على العميل لا على الزيارة،
    // فنجمع عملاء المناطق المختارة ثم نقصر كل استعلام عليهم.
    $regionIds = array_filter((array) $request->input('region_id'), fn ($v) => $v !== '' && $v !== null);

    if ($regionIds) {
        $regionCustomers = DB::table('customers')->whereIn('region_id', $regionIds)->pluck('id');

        $visitQuery        = $visitQuery->whereIn('customer_id', $regionCustomers);
        $requiredQuery     = $requiredQuery->whereIn('visitors.customer_id', $regionCustomers);
        $ordersQuery       = $ordersQuery->whereIn('orders.user_id', $regionCustomers);
        $installmentsQuery = $installmentsQuery->whereIn('installments.customer_id', $regionCustomers);
    }

    if ($spec = $request->specialist) {
        // نتيجة الزيارات: whereHas على علاقة الزبون
        $visitQuery = $visitQuery->whereHas('customer', fn($q) => $q->where('specialist', $spec));

        // الزوار المطلوبون
        $requiredQuery = $requiredQuery
            ->join('customers', 'visitors.customer_id', '=', 'customers.id')
            ->where('customers.specialist', $spec);

        // الطلبات
        $ordersQuery = $ordersQuery
            ->join('customers', 'orders.user_id', '=', 'customers.id') // حسب كودك السابق
            ->where('customers.specialist', $spec);

        // الأقساط
        $installmentsQuery = $installmentsQuery
            ->join('customers', 'installments.customer_id', '=', 'customers.id')
            ->where('customers.specialist', $spec);
    }

    if ($from = $request->from_date) {
        $visitQuery         = $visitQuery->whereDate('result_visitors.created_at', '>=', $from);
        $requiredQuery      = $requiredQuery->whereDate('visitors.created_at', '>=', $from);
        $ordersQuery        = $ordersQuery->whereDate('orders.created_at', '>=', $from);
        $installmentsQuery  = $installmentsQuery->whereDate('installments.created_at', '>=', $from);
    }

    if ($to = $request->to_date) {
        $visitQuery         = $visitQuery->whereDate('result_visitors.created_at', '<=', $to);
        $requiredQuery      = $requiredQuery->whereDate('visitors.created_at', '<=', $to);
        $ordersQuery        = $ordersQuery->whereDate('orders.created_at', '<=', $to);
        $installmentsQuery  = $installmentsQuery->whereDate('installments.created_at', '<=', $to);
    }

    // 4) Paginated actual visits
    $visitors = $visitQuery->paginate(10);

    // 5) Totals under filters
    $totalActualVisits   = $visitQuery->count();
    $totalRequiredVisits = $requiredQuery->count();

    // 6) Invoice collection totals (type = 4)
    $collectedInvoicesCount = (clone $ordersQuery)
        ->where('orders.type', 4)
        ->whereColumn('orders.order_amount', '<=', 'orders.transaction_reference')
        ->count();

    $uncollectedInvoicesCount = (clone $ordersQuery)
        ->where('orders.type', 4)
        ->whereColumn('orders.order_amount', '>', 'orders.transaction_reference')
        ->count();

    // مبيعات/نقدي/آجل/مرتجع وصافي مبيعات
    $totalSales = (clone $ordersQuery)
        ->where('orders.type', 4)
        ->sum('orders.order_amount');

    $totalCashSales = (clone $ordersQuery)
        ->where('orders.type', 4)
        ->where('orders.cash', 1)
        ->sum('orders.order_amount');

    $totalCreditSales = (clone $ordersQuery)
        ->where('orders.type', 4)
        ->where('orders.cash', 2)
        ->sum('orders.order_amount');

    $totalReturned = (clone $ordersQuery)
        ->where('orders.type', 7)
        ->sum('orders.order_amount');

    $netSales = $totalSales - $totalReturned;

    $totalinstallment = (clone $installmentsQuery)->sum('installments.total_price');

    // 7) Monthly breakdown (current year)
    $year = \Carbon\Carbon::now()->year;

    // MONTH() is MySQL-only. Pick the equivalent for the active driver so this
    // runs on SQLite too; both yield the month number as an integer.
    $monthOf = function (string $column) {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', {$column}) AS INTEGER)"
            : "MONTH({$column})";
    };

    $actualMonth   = $monthOf('result_visitors.created_at');
    $requiredMonth = $monthOf('visitors.created_at');

    $actualBuckets = $visitQuery
        ->whereYear('result_visitors.created_at', $year)
        ->select(
            DB::raw($actualMonth . ' AS month_num'),
            DB::raw('COUNT(*) AS total')
        )
        ->groupBy(DB::raw($actualMonth))
        ->pluck('total', 'month_num')
        ->toArray();

    $requiredBuckets = $requiredQuery
        ->whereYear('visitors.created_at', $year)
        ->select(
            DB::raw($requiredMonth . ' AS month_num'),
            DB::raw('COUNT(*) AS total')
        )
        ->groupBy(DB::raw($requiredMonth))
        ->pluck('total', 'month_num')
        ->toArray();

    $monthlyActualVisits   = [];
    $monthlyRequiredVisits = [];
    for ($m = 1; $m <= 12; $m++) {
        $monthlyActualVisits[$m]   = $actualBuckets[$m]   ?? 0;
        $monthlyRequiredVisits[$m] = $requiredBuckets[$m] ?? 0;
    }

    // 8) Top-5 most-visited (من result_visitors)
    $baseTop = $visitQuery
        ->join('customers', 'result_visitors.customer_id', '=', 'customers.id')
        ->select('customers.id', 'customers.name', DB::raw('COUNT(*) AS visits'))
        ->groupBy('customers.id', 'customers.name')
        ->orderBy('visits', 'desc')
        ->limit(5);

    $topDoctors        = (clone $baseTop)->where('customers.specialist', 4)->where('customers.active', 1)->get();
    $topMedicalCenters = (clone $baseTop)->where('customers.specialist', 2)->where('customers.active', 1)->get();
    $topHospitals      = (clone $baseTop)->where('customers.specialist', 3)->where('customers.active', 1)->get();

    // Top-5 Pharmacies by orders count (Query مستقل لتجنّب التعارض)
    $topPharmacies = DB::table('orders as o')
        ->join('customers as c', 'o.user_id', '=', 'c.id') // حسب بنية مشروعك
        ->when($request->customer_id, fn($q, $cust) => $q->where('o.user_id', $cust))
        ->when($request->specialist,   fn($q, $sp)   => $q->where('c.specialist', $sp))
        ->when($request->from_date,    fn($q, $d)    => $q->whereDate('o.created_at', '>=', $d))
        ->when($request->to_date,      fn($q, $d)    => $q->whereDate('o.created_at', '<=', $d))
        ->where('o.owner_id', $seller_id)
        ->where('o.type', 4)
        ->where('c.specialist', 1)
        ->where('c.active', 1)
        ->select('c.id', 'c.name', DB::raw('COUNT(o.id) AS orders_count'))
        ->groupBy('c.id', 'c.name')
        ->orderByDesc('orders_count')
        ->limit(5)
        ->get();

    // 9) Overall customer-type counts (unfiltered)
    $customerIds = DB::table('seller_customers')
        ->where('seller_id', $seller_id)
        ->pluck('customer_id');

    $type1Count = $this->customer->whereIn('id', $customerIds)->where('specialist', 1)->count();
    $type2Count = $this->customer->whereIn('id', $customerIds)->where('specialist', 2)->count();
    $type3Count = $this->customer->whereIn('id', $customerIds)->where('specialist', 3)->count();
    $type4Count = $this->customer->whereIn('id', $customerIds)->whereIn('specialist', [0, 4])->count();

    // 10) Data for filters
    $customers = $this->customer->whereIn('id', $customerIds)->get();
    $sellers   = $this->seller->where('role', 'seller')->get();

    // 11) Render view (نفس المتغيرات)
    return view('admin-views.visitors.indexresult', compact(
        'visitors', 'seller_id', 'customers', 'sellers',
        'type1Count', 'type2Count', 'type3Count', 'type4Count',
        'totalActualVisits', 'totalRequiredVisits',
        'collectedInvoicesCount', 'uncollectedInvoicesCount',
        'monthlyActualVisits', 'monthlyRequiredVisits',
        'topDoctors', 'topPharmacies', 'topMedicalCenters', 'topHospitals',
        'totalSales', 'totalCashSales', 'totalCreditSales', 'totalReturned', 'netSales',
        'totalinstallment', 'seller', 'regions'
    ));
}




    
    /**
     * The admin.vehicles route points here. The method had been commented out,
     * so the page answered 500 with "Method ...::vehicles does not exist";
     * the view it renders is still present.
     */
    public function vehicles(Request $request): Factory|View|Application
    {
        $date = $request['search'];
        $sellers = $this->seller->get();

        return view('admin-views.vehicle_stocks.vehicles', compact('sellers', 'date'));
    }
    
    /**
     * admin.vehicles.products routes here; the method had been commented out.
     * The stray dd() that used to sit before the return is dropped - it would
     * have dumped and halted the response.
     */
    public function vehicle_products($seller_id): Factory|View|Application
    {
        $stocks = \App\Models\ConfirmStock::where('seller_id', $seller_id)
            ->whereColumn('stock', '<=', 'main_stock')->where('stock', '!=', 0)->get();
        $remain_stocks = \App\Models\ConfirmStock::where('seller_id', $seller_id)->where('stock', 0)->get();
        $seller = Seller::findOrFail($seller_id);

        return view('admin-views.vehicle_stocks.products', compact('stocks', 'remain_stocks', 'seller'));
    }
    
    /** admin.vehicles.products' sibling; also previously commented out. */
    public function stock_products($seller_id): Factory|View|Application
    {
        $stocks = \App\Models\Stock::where('seller_id', $seller_id)
            ->whereColumn('stock', '<', 'main_stock')->where('stock', '!=', 0);
        $remain_stocks = \App\Models\Stock::where('seller_id', $seller_id)
            ->whereColumn('main_stock', '=', 'stock');
        $seller = Seller::find($seller_id);
        $orders = \App\Models\CurrentOrder::where('owner_id', $seller_id);

        return view('admin-views.vehicle_stocks.stocks', compact('stocks', 'remain_stocks', 'orders', 'seller'));
    }

   public function create(Request $request): Factory|View|Application|JsonResponse
    {
        // 1) get all sellers for this admin
        $adminId = Auth::guard('admin')->id();
        $sellerIds = AdminSeller::where('admin_id', $adminId)
                                ->pluck('seller_id');
        $sellers = Seller::whereIn('id', $sellerIds)
                         ->where('role', 'seller')
                         ->get(['id','email']);

        // 2) if AJAX, branch on params
        if ($request->ajax()) {
            // A) fetch regions for a seller
            if ($request->has('seller_id') && ! $request->has('region_id')) {
                $regionIds = SellerRegion::where('seller_id', $request->seller_id)
                                         ->pluck('region_id');
                $regions = Region::whereIn('id', $regionIds)
                                 ->get(['id','name']);
                return response()->json(['option' => $regions]);
            }

            // B) fetch customers for a region
            if ($request->has('region_id')) {
                $customers = Customer::where('region_id', $request->region_id)
                                     ->get(['id','name','mobile','address']);
                return response()->json(['option' => $customers]);
            }
        }

        // 3) normal page load
        $customers = collect(); 
        return view('admin-views.visitors.create', compact('sellers','customers'));
    }
public function export(Request $request)
{
    // Retrieve filters from request
    $sellerId = $request->seller_id;
    $regionId = $request->region_id;
    $fromDate = $request->from_date;
    $toDate = $request->to_date;

    // Initialize query
    $query = Visitor::query();

    // Apply filters
    if ($sellerId) {
        $query->where('seller_id', $sellerId);
    }

    if ($regionId) {
        $query->whereHas('customer', function ($q) use ($regionId) {
            $q->where('region_id', $regionId);
        });
    }

    if ($fromDate && $toDate) {
        $query->whereBetween('date', [$fromDate, $toDate]);
    } elseif ($fromDate) {
        $query->whereDate('date', '>=', $fromDate);
    } elseif ($toDate) {
        $query->whereDate('date', '<=', $toDate);
    }

    // Fetch the filtered visitors data
    $visitors = $query->with('seller', 'customer')->get();

    // Prepare the data for the Excel export
    $data = $visitors->map(function ($visitor) {
        return [
            'Visitor ID' => $visitor->id,
            'Seller Name' => $visitor->seller->email ?? 'N/A',
            'Customer Name' => $visitor->customer->name ?? 'N/A',
            'Region' => $visitor->customer->regions->name ?? 'N/A',
            'Date' => $visitor->date,
            'Note' => $visitor->note ?? 'N/A',
        ];
    });

    // Use FastExcel to download the data as an Excel file
    return (new FastExcel($data))->download('visitors.xlsx');
}




public function store(Request $request): Factory|RedirectResponse|Application
{
    // Validate the input fields
    $request->validate([
        'seller_id' => 'required|exists:admins,id',
        'customer_id' => 'required|array',   // customer_id is an array
        'date' => 'required|array',          // date is an array
        'note' => 'required|array',          // note is an array
    ]);

    $success = 0;
    $newVisitorsCount = 0; // Track the number of new visitors

    // Fetch the seller instance
    $seller = Seller::find($request->seller_id);
    if (!$seller) {
        Toastr::error(translate('Seller not found'));
        return back();
    }

    // Loop through the customer_id array, allowing multiple entries for the same customer
    foreach ($request->customer_id as $i => $customerId) {
        // Find the customer by ID
        $customer = $this->customer->find($customerId);
        if (!$customer) {
            continue; // Skip if customer not found
        }

        // Get the date and note for each visitor/customer from the arrays
        $date = $request->date[$i];
        $note = $request->note[$i];

        // Always create a new visitor record
        $visitor = new $this->visitor;
        $visitor->seller_id = $request->seller_id;
        $visitor->customer_id = $customerId;
        $visitor->date = $date;
        $visitor->note = $note;
        $visitor->save();

        // Increment the new visitors count
        $newVisitorsCount++;
        $success = 1;
    }

    // Increment the seller's visitors column by the number of new visitors
    if ($newVisitorsCount > 0) {
        $seller->increment('visitors', $newVisitorsCount); // Increment by the number of new visitors added
    }

    // Display a success message if visitors were added or updated
    if ($success == 1) {
        Toastr::success(translate('Visitors added successfully'));
    }

    return back();
}


    public function edit(Request $request, $id): Factory|View|Application|JsonResponse
    {
    $adminId = Auth::guard('admin')->id();

    // Retrieve the seller IDs associated with the authenticated admin from the 'admin_sellers' table
    $sellerIds = AdminSeller::where('admin_id', $adminId)->pluck('seller_id');

    // Retrieve sellers associated with the authenticated admin
    $sellers = Seller::whereIn('id', $sellerIds)->where('role', 'seller')->get();
    // findOrFail: an unknown id returned null and was dereferenced
    // straight away, answering 500 instead of a clean 404.
    $visitor = $this->visitor->findOrFail($id);
        $products = [];
        if($request->has('seller')) {
            $sel = $this->seller->find($request->seller);
            foreach($sel->cats as $c) {
                $id = $c->cat->id;
                $customers[] = $this->customer->where('category_id', $id)->get();
            }

            return response()->json([
                'option' => $customers
            ]);
        }
        else {
            foreach($visitor->seller->cats as $c) {
                $id = $c->cat->id;
                $customers[] = $this->customer->where('category_id', $id)->get();
            }
        }
        // شاشة تعديل الزيارة غير مكتملة: القالب المُشار إليه هو قالب مخزون
        // المركبات ويتوقّع $stock لا $visitor، وقالب visitors/edit نسخة منه
        // تحمل نفس المتغيّر وتُرسل إلى مسارات المخزون. الرابط في شاشة
        // الزيارات معطَّل بتعليق منذ الأصل. نُعيد المستخدم برسالة واضحة بدل
        // صفحة خطأ 500 إلى أن تُبنى الشاشة بالشكل الصحيح.
        Toastr::warning(\App\CPU\translate('شاشة تعديل الزيارة غير متاحة حاليًا'));

        return redirect()->route('admin.visitor.index');
    }

    public function update(Request $request, $id): Factory|RedirectResponse|Application
    {
        $customer = $this->customer->find($request->product_id);
        $request->validate([
            'seller_id' => 'required',
            'customer_id' => 'required',
            'date' => 'required',
            'note' => 'nullable',
        ]);

        // findOrFail: an unknown id returned null and was dereferenced
        // straight away, answering 500 instead of a clean 404.
        $visitor = $this->visitor->findOrFail($id);
        $visitor->seller_id = $request->seller_id;
        $visitor->customer_id = $request->customer_id;
        $visitor->date = $request->date;
        $visitor->note = $request->note;
        $visitor->update();


        Toastr::success(translate('Visitor updated successfully'));
        return redirect()->route('admin.visitor.index');
    }
public function delete(Request $request): RedirectResponse
{
    // Find the visitor by ID
    $visitor = $this->visitor->find($request->id);
    
    if (!$visitor) {
        Toastr::error(translate('Visitor not found'));
        return back();
    }

    // Find the seller associated with this visitor
    $seller = $this->seller->find($visitor->seller_id);

    if ($seller) {
        // Decrease the visitors count by 1
        $seller->decrement('visitors', 1);
    }

    // Delete the visitor record
    $visitor->delete();

    Toastr::success(translate('Visitor removed successfully'));
    return back();
}

public function indexresult(Request $request)
{
    // ====== تحضير صلاحيات البائعين للمشرف الحالي ======
$admin   = Auth::guard('admin')->user();
$adminId = $admin?->id;
$isSuper = $admin && in_array($admin->role, ['super_admin', 'admin'], true);

// IDs البائعين المسموحين
$allowedSellerIds = $isSuper
    ? Seller::where('role', 'seller')->pluck('id')->all()
    : AdminSeller::where('admin_id', $adminId)->pluck('seller_id')->all();

// ✅ إضافة البائع الخاص بالمستخدم الحالي (لو عنده seller_id)
if ($admin && $admin->id) {
    $allowedSellerIds[] = $admin->id;
}

// إزالة التكرار
$allowedSellerIds = array_unique($allowedSellerIds);

// قائمة البائعين النهائية للفلتر
$sellers = Seller::whereIn('id', $allowedSellerIds)
    ->where('role', 'seller')
    ->get();


    // فلتر العميل صار كتابة لا اختيارًا، فلم تعد هناك حاجة لتحميل ~2000
    // عميل في كل طلب. الفئات (نوع 0) هي تخصصات الأطباء، والمناطق للفلتر.
    $categories = Category::where('type', 0)->orderBy('name')->get();
    $regions    = Region::orderBy('name')->get();

    // ====== ضبط التواريخ ======
    $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : null;
    $dateTo   = $request->filled('date_to')   ? Carbon::parse($request->date_to)->endOfDay()   : null;

    // ====== الاستعلام الأساسي ======
    $base = ResultVisitor::query()
        ->select('id','customer_id','admin_id','note','lang','lat','created_at')
        // لو عندك علاقات: belongsTo(Customer) و belongsTo(Seller as admin/seller)
        ->with(['customer:id,name']);

    // تطبيق قيود الوصول للبائعين (إلا لو سوبر أدمن)
    if (!$isSuper) {
        $base->whereIn('admin_id', $allowedSellerIds);
    }

    // ====== فلاتر المدخلات ======
    // فلتر العميل كتابة: يطابق الاسم أو الموبايل بدل الاختيار من قائمة.
    if ($request->filled('customer')) {
        $term = $request->input('customer');
        $base->whereHas('customer', function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('mobile', 'like', "%{$term}%");
        });
    }

    // التخصص (أطفال / نسا وتوليد / ...) وهو category_id على العميل.
    if ($request->filled('category_id')) {
        $categoryId = $request->integer('category_id');
        $base->whereHas('customer', fn ($q) => $q->where('category_id', $categoryId));
    }

    // المنطقة، مع إمكانية تحديد أكثر من منطقة معًا.
    $regionIds = array_filter((array) $request->input('region_id'), fn ($v) => $v !== '' && $v !== null);
    if ($regionIds) {
        $base->whereHas('customer', fn ($q) => $q->whereIn('region_id', $regionIds));
    }

    if ($request->filled('seller_id')) {
        $requestedSeller = (int) $request->seller_id;
        // لا تسمح بفلتر لبائع غير مُخوّل
        if (in_array($requestedSeller, $allowedSellerIds, true)) {
            $base->where('admin_id', $requestedSeller);
        }
    }

    if ($dateFrom) {
        $base->where('created_at', '>=', $dateFrom);
    }
    if ($dateTo) {
        $base->where('created_at', '<=', $dateTo);
    }

    $visitors = $base->latest()->paginate(15)->withQueryString();

    // ====== الإجماليات (تعتمد على نفس الفلاتر العامة) ======
    $customerTotal = null;
    $sellerTotal   = null;

    // $base is already filtered; re-applying the same condition would be a
    // no-op, so just count what the filter actually selected.
    if ($request->filled('customer')) {
        $customerTotal = (clone $base)->count();
    }

    if ($request->filled('seller_id')) {
        $requestedSeller = (int) $request->seller_id;
        if (in_array($requestedSeller, $allowedSellerIds, true)) {
            $sellerTotal = (clone $base)->count();
        } else {
            $sellerTotal = 0; // غير مُخوّل
        }
    }

    return view('admin-views.visitors.indexresultnew', compact(
        'visitors', 'customerTotal', 'sellerTotal', 'sellers',
        'categories', 'regions', 'regionIds'
    ));
}

/**
 * الزيارات المنفَّذة كملف اكسيل، بنفس فلاتر الشاشة وعلى كامل النتيجة لا
 * على الصفحة المعروضة. لم يكن لهذه الشاشة تصدير من قبل؛ التصدير الموجود
 * (export) يخص الزيارات المخطَّطة وجدولًا آخر.
 */
public function exportResult(Request $request)
{
    $admin   = Auth::guard('admin')->user();
    $adminId = $admin?->id;
    $isSuper = $admin && in_array($admin->role, ['super_admin', 'admin'], true);

    $allowedSellerIds = $isSuper
        ? Seller::where('role', 'seller')->pluck('id')->all()
        : AdminSeller::where('admin_id', $adminId)->pluck('seller_id')->all();

    if ($admin && $admin->id) {
        $allowedSellerIds[] = $admin->id;
    }
    $allowedSellerIds = array_unique($allowedSellerIds);

    $base = ResultVisitor::query()->with(['customer.regions', 'seller']);

    if (!$isSuper) {
        $base->whereIn('admin_id', $allowedSellerIds);
    }

    if ($request->filled('customer')) {
        $term = $request->input('customer');
        $base->whereHas('customer', function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('mobile', 'like', "%{$term}%");
        });
    }

    if ($request->filled('category_id')) {
        $categoryId = $request->integer('category_id');
        $base->whereHas('customer', fn ($q) => $q->where('category_id', $categoryId));
    }

    $regionIds = array_filter((array) $request->input('region_id'), fn ($v) => $v !== '' && $v !== null);
    if ($regionIds) {
        $base->whereHas('customer', fn ($q) => $q->whereIn('region_id', $regionIds));
    }

    if ($request->filled('seller_id')) {
        $requestedSeller = (int) $request->seller_id;
        if (in_array($requestedSeller, $allowedSellerIds, true)) {
            $base->where('admin_id', $requestedSeller);
        }
    }

    if ($request->filled('date_from')) {
        $base->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay());
    }
    if ($request->filled('date_to')) {
        $base->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
    }

    $categories = Category::where('type', 0)->pluck('name', 'id');

    $rows = $base->latest()->get()->map(fn ($v) => [
        'رقم الزيارة' => $v->id,
        'التاريخ'     => optional($v->created_at)->format('Y-m-d H:i'),
        'العميل'      => optional($v->customer)->name ?? '',
        'الموبايل'    => optional($v->customer)->mobile ?? '',
        'التخصص'      => $categories[optional($v->customer)->category_id] ?? '',
        'المنطقة'     => optional(optional($v->customer)->regions)->name ?? '',
        'المندوب'     => trim((optional($v->seller)->f_name ?? '') . ' ' . (optional($v->seller)->l_name ?? '')),
        'الملاحظة'    => $v->note,
        'خط العرض'    => $v->lat,
        'خط الطول'    => $v->lang,
    ]);

    $filename = 'executed-visits-' . now()->format('Y-m-d') . '.csv';

    // maatwebsite/excel غير مثبّت، وExcel يفتح CSV مباشرة؛ الـ BOM يبقي
    // العربية مقروءة.
    return response()->streamDownload(function () use ($rows) {
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");

        if ($rows->isNotEmpty()) {
            fputcsv($out, array_keys($rows->first()));
            foreach ($rows as $row) {
                fputcsv($out, array_values($row));
            }
        } else {
            fputcsv($out, ['لا توجد بيانات']);
        }

        fclose($out);
    }, $filename, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ]);
}
    
    // public function history(Request $request)
    // {
    //     // $limit = $request['limit'] ?? 10;
    //     // $offset = $request['offset'] ?? 1;
    //     // $date = $request['date'] ?? null;
    //     $search = $request->input('search');
        
    //     $fromDate = $request->input('from_date');
    //     $toDate = $request->input('to_date');

    //     $stocks = $this->stock_order->latest()
    //                           ->with(['seller']);
    
    //     if (!empty($search)) {
    //         $stocks->where(function($query) use ($search) {
    //             $query->where('id', 'like', "%{$search}%")
    //                   ->orWhereHas('seller', function($query) use ($search) {
    //                       $query->where('f_name', 'like', "%{$search}%")
    //                             ->orWhere('l_name', 'like', "%{$search}%");
    //                   });
    //         });
    //     }
    
    //     if (!empty($fromDate) && !empty($toDate)) {
    //         $stocks->whereBetween('created_at', [$fromDate, $toDate]);
    //     }
    
    //     $stocks = $stocks->paginate(Helpers::pagination_limit())->appends([
    //         'search' => $search,
    //         'from_date' => $fromDate,
    //         'to_date' => $toDate,
    //     ]);
        
    //     // $items = $stocks->items();
    //     foreach($stocks as $key => $item) {
    //         $item['statistcs'] = json_decode($item->statistcs);
    //     }
    //     // dd($stocks);
    //     // return $stocks[0]->statistcs->products[0]->price . ' ' . \App\CPU\Helpers::currency_symbol();
    //     return view('admin-views.pos.stocks.list', ['orders' => $stocks, 'fromDate' => $fromDate, 'toDate' => $toDate, 'search' => $search]);
    //     // return response()->json($data, 200);
    // }
}
