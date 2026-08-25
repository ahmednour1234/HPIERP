<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Visitor;
use App\Models\ResultVisitor;
use App\Models\Seller;
use App\Models\SellerRegion;
use App\Models\Region;
use App\Models\Customer;
use App\Models\AdminSeller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator; // Import the Validator facade
use Illuminate\Support\Facades\DB;
use App\Models\DevelopSeller;
use Carbon\Carbon;
use App\Models\Document;
use App\Models\DocumentAttachment;


class VisitorController extends Controller
{
  
    private Visitor $visitor;
    private Customer $customer;
          private Region $regions;
        private Seller $seller;
        private ResultVisitor $resultVisitor;
    public function __construct(Visitor $visitor, Seller $seller, Customer $customer,ResultVisitor $resultVisitor)
    {
        $this->visitor = $visitor;
        $this->seller = $seller;
        $this->customer = $customer;
              $this->resultVisitor=$resultVisitor;
              
    }

    /**
     * Fetch visitors based on seller_id and date filters.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Get the authenticated user (assuming the user is a seller)
        $user = Auth::user();
        
        // Check if the authenticated user is a seller
        if (!$user || $user->role !== 'seller') {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }

        // Initialize query for Visitor model
$query = Visitor::query()
    ->orderBy('created_at', 'desc'); // Order by created_at in descending order

        // Automatically filter by the authenticated seller's ID
        $query->where('seller_id', $user->id);

        // Filter by additional seller_id if provided (optional)
        if ($request->has('seller_id') && $request->seller_id) {
            $query->where('seller_id', $request->seller_id);
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
        $visitors = $query->with('seller', 'customer.regions')->get();

        // Return the visitors as a JSON response
        return response()->json([
            'status' => 'success',
            
            'visitors' => $visitors
        ], 200);
    }
     public function indexresultvisitor(Request $request): JsonResponse
    {
        // Get the authenticated user (assuming the user is a seller)
        $user = Auth::user();
        
        // Check if the authenticated user is a seller
        if (!$user || $user->role !== 'seller') {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }

        // Initialize query for Visitor model
$query = ResultVisitor::query()
    ->orderBy('created_at', 'desc'); // Order by created_at in descending order

        // Automatically filter by the authenticated seller's ID
        $query->where('admin_id', $user->id);

        // Filter by additional seller_id if provided (optional)
        if ($request->has('admin_id') && $request->admin_id) {
            $query->where('admin_id', $request->admin_id);
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
        $visitors = $query->with('seller', 'customer.regions')->get();

        // Return the visitors as a JSON response
        return response()->json([
            'status' => 'success',
            'visitors' => $visitors
        ], 200);
    }
public function store(Request $request): JsonResponse
{
    // Get the authenticated seller
    $user = Auth::user();
    
    // Ensure the user is authorized to perform this action
    if (!$user || $user->role !== 'seller') {
        return response()->json(['error' => 'Unauthorized access'], 403);
    }

    // Validate the incoming request data
    $validator = Validator::make($request->all(), [
        'customer_id' => 'required|exists:customers,id',
        'note' => 'required|string',
        'lang' => 'nullable', // Optional
        'lat' => 'nullable',  // Optional
    ]);

    // If validation fails, return a 422 response with errors
    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'errors' => $validator->errors(),
        ], 422);
    }

    // Get the current date and time
    $currentDateTime = now();
    $currentDate = $currentDateTime->toDateString();

    // Retrieve the last visit for the authenticated seller
    $lastVisit = ResultVisitor::where('admin_id', $user->id)
        ->whereDate('created_at', $currentDate) // Same date
        ->latest('created_at') // Get the most recent visit
        ->first();

    // Check if the last visit exists and whether the time difference is less than 10 minutes
    if ($lastVisit) {
        $timeDifference = $currentDateTime->diffInMinutes($lastVisit->created_at);

        if ($timeDifference < 5) {
            return response()->json([
                'status' => 'error',
                'message' => 'لازم يكون فارق التوقيت بين كل زياراة والتانية 5دقائق.'
            ], 403);
        }
    }

    // If the first visit of the day is before 11 AM, increment the `number_of_days`
    if ($currentDateTime->format('H:i') < '11:00' && !$lastVisit) {
        $user->increment('number_of_days');
    }

    // Store the new visitor result
    $resultVisitor = ResultVisitor::create([
        'customer_id' => $request->customer_id,
        'admin_id' => $user->id, // Admin ID is the authenticated seller's ID
        'note' => $request->note,
        'lang' => $request->lang,
        'lat' => $request->lat,
    ]);

    // Increment the `result_visitors` column in the `admins` table
    $user->increment('result_visitors'); // This will increment the column by 1

    // Return a success response with the created visitor data
    return response()->json([
        'status' => 'success',
        'visitor' => $resultVisitor
    ], 200);
}


public function storeseller(Request $request)
{
    try {
        // Fetch the authenticated seller
        $sellerId = auth()->id();

        // Fetch the associated admin_id
        $adminId = AdminSeller::where('seller_id', $sellerId)->first();
        if (!$adminId) {
            return response()->json(['error' => 'Admin not found for the seller'], 404);
        }

        // Use Validator to validate the input fields
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id', // Ensure customer_id exists in customers table
            'date' => 'required|date',                      // Validate as a valid date
            'note' => 'required|string',                    // Validate note as a string
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Create a new visitor record
        $visitor = new Visitor;
        $visitor->seller_id = $sellerId;
        $visitor->customer_id = $request->customer_id; // Single value, not an array
        $visitor->date = $request->date;              // Single date value
        $visitor->note = $request->note;              // Single note value
        $visitor->save();

        // Increment the seller's visitors count by 1

        // Return a JSON response with the results
        return response()->json([
            'status' => 'success',
            'visitor' => $visitor,
        ], 200);

    } catch (\Exception $e) {
        // Log the error for debugging purposes

        // Return an error response
        return response()->json([
            'status' => 'error',
            'message' => 'An error occurred while processing the request. Please try again later.'.$e,
        ], 500);
    }
}
    public function storeRating(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id' => 'required|exists:admins,id',
            'score'     => 'required|numeric',
            'note'      => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $seller = Seller::find($request->seller_id);

        if (! $seller) {
            return response()->json([
                'success' => false,
                'message' => 'Seller not found.',
            ], 404);
        }

        $seller->score = $request->score;
        $seller->note  = $request->note;
        $seller->save();

        return response()->json([
            'success' => true,
            'message' => 'Score updated for the seller.',
            'seller'  => $seller,
        ], 200);
    }

    /**
     * Get all sellers assigned to the current admin.
     * Looks up admin_seller pivot table.
     */
  public function getAssignedSellers(Request $request)
{
    $adminId = (string) Auth::id();

    $sellerIds = DB::table('admin_sellers')
        ->where('admin_id', $adminId)
        ->pluck('seller_id');

    if ($sellerIds->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'لا يوجد مناديب مرتبطة بهذا المسؤول',
        ], 404);
    }

    $sellers = Seller::whereIn('id', $sellerIds)->get();

    return response()->json([
        'success' => true,
        'sellers' => $sellers,
    ], 200);
}

    public function approve(Request $request, $id)
    {
        // تجد السجل أو ترجع 404
        $developSeller = DevelopSeller::findOrFail($id);

        // إذا كانت الإجازة مُعتمدة مسبقاً
        if ($developSeller->active) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الطلب معتمد بالفعل.',
            ], 400);
        }

        // نفّذ التحديث داخل Transaction
        DB::transaction(function () use ($developSeller) {
            // اعتمد الطلب
            $developSeller->active = 1;
            $developSeller->save();

            // اطرّ علاقة الـ seller (تأكد من اسم العلاقة في الموديل)
            $seller = $developSeller->sellers;
            if ($seller && $seller->holidays > 0) {
                // أقلّ يوم واحد من رصيد الإجازات
                $seller->decrement('holidays');
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'تمت الموافقة على الإجازة بنجاح.',
            'data'    => $developSeller->fresh(),
        ], 200);
    }

   public function getType2Pending()
    {
        // 1. ID of the currently authenticated admin
        $adminId = auth()->id();

        // 2. Pull the seller IDs assigned to this admin
        $sellerIds = DB::table('admin_sellers')
            ->where('admin_id', $adminId)
            ->pluck('seller_id');

        // 3. Fetch DevelopSeller records of type=2, active=0
        //    and whose seller_id is in $sellerIds
        $pending = DevelopSeller::with('sellers')
            ->where('type', 2)
            ->whereIn('seller_id', $sellerIds)
            ->get();

        // 4. Return as JSON
        return response()->json([
            'success' => true,
            'data'    => $pending,
        ], 200);
    }
    public function showResultVisitors(Request $request, $seller_id)
{
    // 1) Fetch seller
    $seller = $this->seller->find($seller_id);
    if (! $seller) {
        return response()->json(['error' => 'Seller not found'], 404);
    }

    // 2) Base queries
    $visitQuery    = $this->resultVisitor->where('admin_id', $seller_id);
    $requiredQuery = DB::table('visitors')->where('seller_id', $seller_id);
    $ordersQuery   = DB::table('orders')->where('owner_id', $seller_id);

    // 3) Shared filters
    if ($cust = $request->customer_id) {
        $visitQuery    = $visitQuery->where('customer_id', $cust);
        $requiredQuery = $requiredQuery->where('customer_id', $cust);
        $ordersQuery   = $ordersQuery->where('customer_id', $cust);
    }
    if ($spec = $request->specialist) {
        $visitQuery    = $visitQuery->whereHas('customer', fn($q) => $q->where('specialist', $spec));
        $requiredQuery = $requiredQuery
                            ->join('customers','visitors.customer_id','=','customers.id')
                            ->where('customers.specialist', $spec);
        $ordersQuery   = $ordersQuery
                            ->join('customers','orders.user_id','=','customers.id')
                            ->where('customers.specialist', $spec);
    }
    if ($from = $request->from_date) {
        $visitQuery    = $visitQuery->whereDate('result_visitors.created_at','>=',$from);
        $requiredQuery = $requiredQuery->whereDate('visitors.created_at','>=',$from);
        $ordersQuery   = $ordersQuery->whereDate('orders.created_at','>=',$from);
    }
    if ($to = $request->to_date) {
        $visitQuery    = $visitQuery->whereDate('result_visitors.created_at','<=',$to);
        $requiredQuery = $requiredQuery->whereDate('visitors.created_at','<=',$to);
        $ordersQuery   = $ordersQuery->whereDate('orders.created_at','<=',$to);
    }

    // 4) Paginated actual visits (10 per page)
    $visitors = $visitQuery->paginate(10);

    // 5) Totals under filters
    $totalActualVisits   = $visitQuery->count();
    $totalRequiredVisits = $requiredQuery->count();

    // 6) Invoice collection totals
    $collectedInvoicesCount   = (clone $ordersQuery)
        ->whereColumn('order_amount','<=','transaction_reference')
        ->count();
    $uncollectedInvoicesCount = (clone $ordersQuery)
        ->whereColumn('order_amount','>','transaction_reference')
        ->count();

    // 7) Monthly breakdown (current year)
    $year = Carbon::now()->year;
    $actualBuckets = $visitQuery
        ->whereYear('result_visitors.created_at', $year)
        ->select(
            DB::raw('MONTH(result_visitors.created_at) AS month'),
            DB::raw('COUNT(*) AS total')
        )
        ->pluck('total','month')
        ->toArray();

    $requiredBuckets = $requiredQuery
        ->whereYear('visitors.created_at', $year)
        ->select(
            DB::raw('MONTH(visitors.created_at) AS month'),
            DB::raw('COUNT(*) AS total')
        )
        ->pluck('total','month')
        ->toArray();

    $monthlyActualVisits   = array_map(fn($m) => $actualBuckets[$m] ?? 0, range(1,12));
    $monthlyRequiredVisits = array_map(fn($m) => $requiredBuckets[$m] ?? 0, range(1,12));

    // 8) Top-5 most-visited per specialist
    $baseTop = $visitQuery
        ->join('customers','result_visitors.customer_id','=','customers.id')
        ->select('customers.id','customers.name', DB::raw('COUNT(*) AS visits'))
        ->groupBy('customers.id','customers.name')
        ->orderBy('visits','desc')
        ->limit(5);

    $topDoctors        = (clone $baseTop)->where('customers.specialist',4)->where('customers.active',1)->get();
    $topPharmacies     = (clone $baseTop)->where('customers.specialist',1)->where('customers.active',1)->get();
    $topMedicalCenters = (clone $baseTop)->where('customers.specialist',2)->where('customers.active',1)->get();
    $topHospitals      = (clone $baseTop)->where('customers.specialist',3)->where('customers.active',1)->get();

    // 9) Overall customer-type counts (unfiltered)
    $customerIds = DB::table('seller_customers')
        ->where('seller_id',$seller_id)
        ->pluck('customer_id');

    $typeCounts = [
        1 => $this->customer->whereIn('id',$customerIds)->where('type',1)->count(),
        2 => $this->customer->whereIn('id',$customerIds)->where('type',2)->count(),
        3 => $this->customer->whereIn('id',$customerIds)->where('type',3)->count(),
        4 => $this->customer->whereIn('id',$customerIds)->where('type',4)->count(),
    ];

    // 10) Data for filters
    $customers = $this->customer->whereIn('id',$customerIds)->get();
    $sellers   = $this->seller->where('role','seller')->get();

    // 11) Return JSON response
    return response()->json([
        'pagination' => [
            'current_page' => $visitors->currentPage(),
            'per_page'     => $visitors->perPage(),
            'total'        => $visitors->total(),
            'last_page'    => $visitors->lastPage(),
        ],
        'total_actual_visits'       => $totalActualVisits,
        'total_required_visits'     => $totalRequiredVisits,
        'invoices' => [
            'collected'   => $collectedInvoicesCount,
            'uncollected' => $uncollectedInvoicesCount,
        ],
        'top' => [
            'doctors'        => $topDoctors,
            'pharmacies'     => $topPharmacies,
            'medical_centers'=> $topMedicalCenters,
            'hospitals'      => $topHospitals,
        ],
        'customer_type_counts'      => $typeCounts,
    
    ], 200);
}
public function indexdocument(Request $request): JsonResponse
{
    $documents = Document::with('attachments')
        ->latest()
        ->get();

    return response()->json([
        'success'   => true,
        'documents' => $documents,
    ]);
}
public function indexRegions(Request $request)
{
    // 1) جلب المستخدم الحالي
    /** @var \App\Models\Admin $auth */
    $auth     = Auth::user();
    $sellerId = (int) $auth->id;
    $type     = (string) $auth->type;

    // هنحدد IDs المناطق حسب النوع
    $regionIds = collect();

    // لو مدير: هات مرؤوسي المدير من admin_sellers، ثم هات seller_regions لهم
    if (in_array($type, ['manager', 'bigmanager'], true)) {
        $subordinateSellerIds = \App\Models\AdminSeller::where('admin_id', $sellerId)
            ->pluck('seller_id')
            ->map(fn($x) => (int) $x)
            ->unique()
            ->values();

        if ($subordinateSellerIds->isNotEmpty()) {
            $regionIds = \App\Models\SellerRegion::whereIn('seller_id', $subordinateSellerIds)
                ->pluck('region_id')
                ->map(fn($x) => (int) $x)
                ->unique()
                ->values();
        } else {
            // لا يوجد مرؤوسون -> ترجع فاضي
            return response()->json(['regions' => []]);
        }

    } else {
        // غير مدير (مندوب/بائع): السلوك القديم
        $regionIds = \App\Models\SellerRegion::where('seller_id', $sellerId)
            ->pluck('region_id')
            ->map(fn($x) => (int) $x)
            ->unique()
            ->values();
    }

    if ($regionIds->isEmpty()) {
        return response()->json(['regions' => []]);
    }

    // 3) جلب كائنات المناطق
    $regions = \App\Models\Region::whereIn('id', $regionIds)->get(['id', 'name']);

    // 4) جلب العملاء المرتبطين بهذه المناطق
    //    - لو مدير: العملاء المرتبطين بأي مرؤوس (seller_customers.seller_id IN subordinate ids)
    //    - غير مدير: العملاء المرتبطين بالمندوب نفسه (seller_customers.seller_id = $sellerId)
    if (in_array($type, ['manager', 'bigmanager'], true)) {
        $subordinateSellerIds = \App\Models\AdminSeller::where('admin_id', $sellerId)
            ->pluck('seller_id')
            ->map(fn($x) => (int) $x)
            ->unique()
            ->values();

        $customers = \App\Models\Customer::whereIn('region_id', $regionIds)
            ->whereExists(function($q) use ($subordinateSellerIds) {
                $q->select(DB::raw(1))
                  ->from('seller_customers')
                  ->whereColumn('seller_customers.customer_id', 'customers.id')
                  ->whereIn('seller_customers.seller_id', $subordinateSellerIds->all());
            })
            ->get(['id','name','mobile','address','region_id']);

    } else {
        $customers = \App\Models\Customer::whereIn('region_id', $regionIds)
            ->whereExists(function($q) use ($sellerId) {
                $q->select(DB::raw(1))
                  ->from('seller_customers')
                  ->whereColumn('seller_customers.customer_id', 'customers.id')
                  ->where('seller_customers.seller_id', $sellerId);
            })
            ->get(['id','name','mobile','address','region_id']);
    }

    // 5) تجميع كل منطقة مع عملائها
    $grouped = $regions->map(function($region) use ($customers) {
        return [
            'id'        => $region->id,
            'name'      => $region->name,
            'customers' => $customers
                ->where('region_id', $region->id)
                ->values()
                ->map(fn($c) => [
                    'id'      => $c->id,
                    'name'    => $c->name,
                    'mobile'  => $c->mobile,
                    'address' => $c->address,
                ]),
        ];
    });

    // 6) الإرجاع
    return response()->json([
        'regions' => $grouped,
    ]);
}




}
