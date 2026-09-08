<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\CPU\Helpers;
use App\Models\ConfirmStock;
use App\Models\CurrentOrder;
use App\Models\Installment;
use App\Models\StockOrder;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Stock;
use App\Models\Store;
use App\Models\Transection;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use function App\CPU\translate;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function __construct(
        private ConfirmStock $confirm_stock,
        private Stock $stock,
        private StockOrder $stock_order,
        private Product $product,
        private Seller $seller,
    ){}

    /**
     * @param Request $request
     * @return Application|Factory|View
     */
    public function index(Request $request): Factory|View|Application
    {
        // The page's search form posts `search`, plus the filters below. The
        // previous version ignored every parameter, so submitting the form
        // changed nothing.
        $stocks = $this->applyFilters($this->stock->newQuery(), $request)
            ->paginate(Helpers::pagination_limit())
            ->appends($request->query());

        $sellers = \App\Models\Seller::where('role', 'seller')
            ->orderBy('f_name')->get(['id', 'f_name', 'l_name', 'mandob_code']);

        return view('admin-views.vehicle_stocks.index', compact('stocks', 'sellers'));
    }

    /**
     * Filters shared by the listing and the export, so a downloaded report
     * always matches what was on screen.
     */
    private function applyFilters($query, Request $request)
    {
        return $query
            ->when($request->filled('seller_id'), fn ($q) =>
                $q->where('seller_id', $request->input('seller_id')))
            ->when($request->filled('product_id'), fn ($q) =>
                $q->where('product_id', $request->input('product_id')))
            // Only rows the seller still holds something of.
            ->when($request->input('remaining') === 'yes', fn ($q) => $q->where('stock', '>', 0))
            ->when($request->input('remaining') === 'no', fn ($q) => $q->where('stock', '<=', 0))
            ->when($request->filled('from'), fn ($q) =>
                $q->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) =>
                $q->whereDate('created_at', '<=', $request->input('to')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->input('search');

                // Product name/code, or the seller's name, code or vehicle.
                $q->where(function ($inner) use ($term) {
                    $inner->whereHas('product', fn ($p) =>
                            $p->where('name', 'LIKE', "%{$term}%")
                              ->orWhere('product_code', 'LIKE', "%{$term}%"))
                          ->orWhereHas('seller', fn ($s) =>
                            $s->where('f_name', 'LIKE', "%{$term}%")
                              ->orWhere('l_name', 'LIKE', "%{$term}%")
                              ->orWhere('mandob_code', 'LIKE', "%{$term}%")
                              ->orWhere('vehicle_code', 'LIKE', "%{$term}%"));
                });
            })
            ->latest('id');
    }

    /**
     * The current filter as an .xlsx, so a report can be handed on rather than
     * screenshotted.
     */
    public function export(Request $request)
    {
        $rows = $this->applyFilters($this->stock->newQuery(), $request)
            ->with(['product', 'seller'])
            ->get()
            ->map(function ($stock) {
                $issued    = (float) $stock->main_stock;
                $remaining = (float) $stock->stock;

                return [
                    'المندوب'        => trim(($stock->seller->f_name ?? '') . ' ' . ($stock->seller->l_name ?? '')),
                    'كود المندوب'    => $stock->seller->mandob_code ?? '',
                    'كود العربية'    => $stock->seller->vehicle_code ?? '',
                    'المنتج'         => $stock->product->name ?? '',
                    'كود المنتج'     => $stock->product->product_code ?? '',
                    'المصروف'        => $issued,
                    'المتبقي'        => $remaining,
                    'المُباع'        => $issued - $remaining,
                    'سعر البيع'      => (float) ($stock->product->selling_price ?? 0),
                    'قيمة المتبقي'   => round($remaining * (float) ($stock->product->selling_price ?? 0), 2),
                    'تاريخ الصرف'    => optional($stock->created_at)->format('Y-m-d'),
                ];
            });

        $filename = 'vehicle-stock-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM: without it Excel reads the Arabic headings as
            // mojibake.
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

    /**
     * Return part of what a seller is carrying to the warehouse.
     *
     * Both sides move together: the van's figures come down and the
     * warehouse quantity goes back up. A seller can only give back what they
     * still hold, so the cap is `stock`, not `main_stock`.
     */
    public function returnToWarehouse(Request $request, $id): RedirectResponse
    {
        $stock = $this->stock->findOrFail($id);

        $request->validate([
            'quantity' => ['required', 'numeric', 'gt:0'],
        ], [
            'quantity.gt' => translate('Quantity must be greater than zero'),
        ]);

        $quantity = (float) $request->input('quantity');

        if ($quantity > (float) $stock->stock) {
            Toastr::error(translate('Cannot return more than the seller is carrying') . ' (' . (float) $stock->stock . ')');
            return back();
        }

        DB::transaction(function () use ($stock, $quantity) {
            // Lock both rows: a concurrent sale by the seller would otherwise
            // read the same `stock` and the two could overdraw it.
            $locked  = $this->stock->lockForUpdate()->find($stock->id);
            $product = $this->product->lockForUpdate()->find($locked->product_id);

            $locked->stock      = (float) $locked->stock - $quantity;
            $locked->main_stock = max((float) $locked->main_stock - $quantity, 0);
            $locked->save();

            if ($product) {
                $product->quantity = (float) $product->quantity + $quantity;
                $product->save();
            }
        });

        Toastr::success(translate('Stock returned to warehouse'));

        return back();
    }
  public function vehicles(Request $request): Factory|View|Application
{
    // Get the authenticated admin's ID
    $adminId = Auth::guard('admin')->id();

    // Get the sellers associated with the authenticated admin
    $sellers = Seller::whereHas('adminSellers', function ($query) use ($adminId) {
        $query->where('admin_id', $adminId); // Filter by admin_id in admin_sellers table
    })->get();

    // Get the search date if provided
    $date = $request['search'];

    // Pass the filtered sellers and the search date to the view
    return view('admin-views.vehicle_stocks.vehicles', compact('sellers', 'date'));
}

    
/**
 * رد مخزون تم صرفه لمندوب.
 *
 * الصرف يفعل عكس هذا تمامًا: يزيد مخزون المندوب (main_stock و stock) وينقص
 * كمية المخزن العام. الرد يعكس الاتجاهين معًا داخل معاملة واحدة، وإلا بقيت
 * الكمية محسوبة مرتين أو ضاعت.
 *
 * القيد المهم: لا يُرد إلا ما لم يُبَع بعد. المندوب قد يكون باع جزءًا من
 * الكمية، وعمود stock هو المتبقي معه فعلًا، فهو سقف ما يمكن رده.
 */
public function return_dispatch(Request $request, $id): RedirectResponse
{
    $request->validate([
        'quantities'   => 'required|array|min:1',
        'quantities.*' => 'nullable|integer|min:0',
        'note'         => 'nullable|string|max:500',
    ]);

    $reserve = DB::table('reserve_products')->where('id', $id)->first();

    if (!$reserve) {
        Toastr::error(translate('أمر الصرف غير موجود'));
        return back();
    }

    // ملكية السجل: لا يرد أمر صرف إلا من يملك مندوبه.
    $adminId   = Auth::guard('admin')->id();
    $ownedIds  = \App\Models\AdminSeller::where('admin_id', $adminId)->pluck('seller_id')->all();
    $ownedIds[] = $adminId;

    if (!in_array((int) $reserve->seller_id, array_map('intval', $ownedIds), true)) {
        Toastr::error(translate('هذا الأمر لا يخص مناديبك'));
        return back();
    }

    $items = json_decode($reserve->data, true) ?: [];

    if (empty($items)) {
        Toastr::error(translate('لا توجد أصناف في أمر الصرف'));
        return back();
    }

    $returned = [];

    try {
        DB::transaction(function () use ($request, $reserve, $items, &$returned) {
            foreach ($items as $index => $item) {
                $productId = (int) ($item['product_id'] ?? 0);
                $asked     = (int) ($request->input('quantities')[$index] ?? 0);

                if ($productId <= 0 || $asked <= 0) {
                    continue;
                }

                $dispatched = (int) ($item['stock'] ?? 0);

                if ($asked > $dispatched) {
                    throw new \InvalidArgumentException(
                        'الكمية المطلوب ردها أكبر من المصروفة للصنف: ' . ($item['product_name'] ?? $productId)
                    );
                }

                $stock = $this->stock
                    ->where('product_id', $productId)
                    ->where('seller_id', $reserve->seller_id)
                    ->lockForUpdate()
                    ->first();

                if (!$stock) {
                    throw new \InvalidArgumentException(
                        'لا يوجد رصيد لهذا الصنف عند المندوب: ' . ($item['product_name'] ?? $productId)
                    );
                }

                // لا يُرد إلا المتبقي مع المندوب؛ المباع خرج من عهدته.
                if ($asked > (int) $stock->stock) {
                    throw new \InvalidArgumentException(
                        'المتبقي مع المندوب ' . (int) $stock->stock . ' فقط من الصنف: '
                        . ($item['product_name'] ?? $productId)
                    );
                }

                // عكس الصرف: ينقص من المندوب ويعود إلى المخزن.
                $stock->decrement('stock', $asked);
                $stock->decrement('main_stock', $asked);

                if ($product = $this->product->find($productId)) {
                    $product->increment('quantity', $asked);
                }

                $returned[] = [
                    'product_id'   => $productId,
                    'product_name' => $item['product_name'] ?? '',
                    'quantity'     => $asked,
                ];
            }

            if (empty($returned)) {
                throw new \InvalidArgumentException('لم تُحدَّد أي كمية للرد');
            }

            // سجل الرد: أمر من نوع 5 يحمل ما رُدّ فعلًا، حتى يبقى للعملية أثر
            // يمكن مراجعته لاحقًا.
            // المعرّف يُسنَد يدويًا في هذا الجدول (نفس ما يفعله الصرف والحجز)،
            // فالعمود ليس تلقائي الترقيم.
            $newId = (int) (DB::table('reserve_products')->max('id') ?? 20000000) + 1;

            DB::table('reserve_products')->insert([
                'id'         => $newId,
                'seller_id'  => $reserve->seller_id,
                'data'       => json_encode($returned, JSON_UNESCAPED_UNICODE),
                'type'       => 5,
                'active'     => 2,
                // NOT NULL بلا قيمة افتراضية في هذا الجدول.
                'update_flag' => 0,
                'insert_flag' => 1,
                'notification' => 0,
                'note'       => $request->input('note'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    } catch (\Throwable $e) {
        Toastr::error(translate($e->getMessage()));
        return back();
    }

    Toastr::success(translate('تم رد المخزون بنجاح'));
    return back();
}

    public function vehicle_products($seller_id): Factory|View|Application
    {
        $stocks = $this->confirm_stock->where('seller_id', $seller_id)->whereRaw('stock <= main_stock AND stock != 0')->get();
        $remain_stocks = $this->confirm_stock->where('seller_id', $seller_id)->whereRaw('stock = 0')->get();

        return view('admin-views.vehicle_stocks.products', compact('stocks', 'remain_stocks'));
    }
    
    public function stock_products($seller_id): Factory|View|Application
    {
        $seller = Seller::findOrFail($seller_id);

        $stocks = $this->stock->with('product')
            ->where('seller_id', $seller_id)
            ->whereColumn('stock', '<', 'main_stock')
            ->orderBy('product_id')
            ->get();

        $remain_stocks = $this->stock->with('product')
            ->where('seller_id', $seller_id)
            ->whereColumn('main_stock', 'stock')
            ->where('main_stock', '>', 0)
            ->orderBy('product_id')
            ->get();

        $allStockRows = $stocks->concat($remain_stocks);
        $orders = CurrentOrder::where('owner_id', $seller_id)->get();
        $store = Store::where('store_id', $seller->vehicle_code)->first();

        $totalIssued = (float) $allStockRows->sum('main_stock');
        $totalSold = (float) $stocks->sum(fn ($stock) => max((float) $stock->main_stock - (float) $stock->stock, 0));
        $totalRemaining = (float) $allStockRows->sum('stock');
        $soldOutCount = $stocks->filter(fn ($stock) => (float) $stock->stock <= 0)->count();

        $summary = [
            'seller_name' => trim(($seller->f_name ?? '') . ' ' . ($seller->l_name ?? '')),
            'seller_code' => $seller->mandob_code,
            'vehicle_code' => $store->store_code ?? $seller->vehicle_code,
            'vehicle_name' => $store->store_name1 ?? '',
            'cash_sales' => (float) Transection::where('seller_id', $seller_id)->where('tran_type', 4)->where('cash', 1)->where('active', 1)->sum('amount'),
            'credit_sales' => (float) Transection::where('seller_id', $seller_id)->where('tran_type', 4)->where('cash', 2)->where('active', 1)->sum('amount'),
            'refund_sales' => (float) Transection::where('seller_id', $seller_id)->where('tran_type', 7)->where('active', 1)->sum('amount'),
            'installments' => (float) Installment::where('seller_id', $seller_id)->sum('total_price'),
            'orders_count' => $orders->count(),
            'sold_products_count' => $stocks->count(),
            'untouched_products_count' => $remain_stocks->count(),
            'sold_out_count' => $soldOutCount,
            'issued_qty' => $totalIssued,
            'sold_qty' => $totalSold,
            'remaining_qty' => $totalRemaining,
            'sold_value' => (float) $stocks->sum(fn ($stock) => max((float) $stock->main_stock - (float) $stock->stock, 0) * (float) optional($stock->product)->selling_price),
            'remaining_value' => (float) $allStockRows->sum(fn ($stock) => (float) $stock->stock * (float) optional($stock->product)->selling_price),
            'sell_through_percent' => $totalIssued > 0 ? round(($totalSold / $totalIssued) * 100, 1) : 0,
        ];

        return view('admin-views.vehicle_stocks.stocks', compact('stocks', 'remain_stocks', 'orders', 'seller', 'store', 'summary'));
    }

    public function create(Request $request): Factory|View|Application|JsonResponse
    {
            $adminId = Auth::guard('admin')->id();

   $sellers = Seller::whereHas('adminSellers', function ($query) use ($adminId) {
        $query->where('admin_id', $adminId); // Filter by admin_id in admin_sellers table
    })->get();
        $products = [];

        // dd();
        if($request->has('seller')) {
            $sel = $this->seller->find($request->seller);
            foreach($sel->cats as $c) {
                $id = $c->cat->id;
                $products[] = $this->product->where('category_id', $id)->get();
                // dd($products->get());
            }
            // dd($products);
            return response()->json([
                'option' => $products
            ]);
            // $products = $products->get();
            
        }
        return view('admin-views.vehicle_stocks.create', compact('sellers', 'products'));
    }

public function store(Request $request): RedirectResponse
{
    // 1) التحقق من صحة البيانات
    $request->validate([
        'seller_id'    => 'required|exists:admins,id',
        'product_id'   => 'required|array|min:1',
        'product_id.*' => 'required|integer|exists:products,id',
        'stock'        => 'required|array|min:1',
        'stock.*'      => 'required|integer|min:1',
    ]);

    $reserveProducts = [];
    $success         = false;

    // 2) المعالجة لكل منتج
    foreach ($request->input('product_id') as $i => $productId) {
        $product = $this->product->find($productId);
        $qt      = (int) ($request->input('stock')[$i] ?? 0);

        // إذا لم يُوجد المنتج أو الكمية المطلوبة أعلى من المتوفر
        if (! $product) {
            continue;
        }
        if ($product->quantity < $qt) {
            Toastr::error(translate("({$product->name}) Stock out"));
            continue;
        }

        // 3) تحديث أو إنشاء سجل المخزون للمندوب
        $stockModel = $this->stock
            ->where('product_id', $productId)
            ->where('seller_id', $request->seller_id)
            ->first();

        if ($stockModel) {
            $stockModel->increment('main_stock', $qt);
            $stockModel->increment('stock', $qt);
        } else {
            // استبدل create بـ save:
            $stockModel = new $this->stock;
            $stockModel->seller_id  = $request->seller_id;
            $stockModel->product_id = $productId;
            $stockModel->main_stock = $qt;
            $stockModel->stock      = $qt;
            $stockModel->save();
        }

        // 4) تخفيض المخزون العام للمنتج
        $product->decrement('quantity', $qt);

        // 5) تحضير بيانات الاحتياطي
        $reserveProducts[] = [
            'product_name' => $product->name,
            'product_id'   => $product->id,
            'stock'        => $qt,
            'balance'      => $product->quantity,
            'price'        => $product->selling_price,
        ];

        $success = true;
    }

    // 6) إدخال سجل الاحتياطي دفعة واحدة
    if (! empty($reserveProducts)) {
        DB::table('reserve_products')->insert([
            'seller_id'  => $request->seller_id,
            'data'       => json_encode($reserveProducts, JSON_UNESCAPED_UNICODE),
            'type'       => 3,
            'active'     => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // 7) إشعار النجاح
    if ($success) {
        Toastr::success(translate('Vehicle Stock added successfully'));
    }

    return redirect()->back();
}


    public function edit(Request $request, $id): Factory|View|Application|JsonResponse
    {
 $adminId = Auth::guard('admin')->id();

   $sellers = Seller::whereHas('adminSellers', function ($query) use ($adminId) {
        $query->where('admin_id', $adminId); // Filter by admin_id in admin_sellers table
    })->get();        $stock = $this->stock->find($id);
        $products = [];
        if($request->has('seller')) {
            $sel = $this->seller->find($request->seller);
            foreach($sel->cats as $c) {
                $id = $c->cat->id;
                $products[] = $this->product->where('category_id', $id)->get();
            }

            return response()->json([
                'option' => $products
            ]);
        }
        else {
            foreach($stock->seller->cats as $c) {
                $id = $c->cat->id;
                $products[] = $this->product->where('category_id', $id)->get();
            }
        }
        return view('admin-views.vehicle_stocks.edit', compact('sellers', 'products', 'stock'));
    }

    public function update(Request $request, $id): Factory|RedirectResponse|Application
    {
        $product = $this->product->find($request->product_id);
        $max = $product->quantity;
        $request->validate([
            'seller_id' => 'required',
            'product_id' => 'required',
            'stock' => "required|numeric|max:$max",
        ]);

        $stock = $this->stock->find($id);
        $stock->seller_id = $request->seller_id;
        $stock->product_id = $request->product_id;
        $stock->main_stock = $request->stock;
        $stock->stock = $request->stock;
        $stock->update();

        $new_stock = $product->quantity - $request->stock;
        $product->quantity = $new_stock;
        $product->update();

        Toastr::success(translate('Vehicle Stock updated successfully'));
        return redirect()->route('admin.stock.index');
    }

    public function delete(Request $request): RedirectResponse
    {
        $stock = $this->stock->find($request->id);
        $product = $this->product->find($stock->product_id);
        $new_stock = $product->quantity + $stock->stock;
        $product->quantity = $new_stock;
        $product->update();
        $stock->delete();

        Toastr::success(translate('Vehicle Stock removed successfully'));
        return back();
    }
    
    public function history(Request $request)
    {
        // $limit = $request['limit'] ?? 10;
        // $offset = $request['offset'] ?? 1;
        // $date = $request['date'] ?? null;
        $search = $request->input('search');
        
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $stocks = $this->stock_order->latest()
                              ->with(['seller']);
    
        if (!empty($search)) {
            $stocks->where(function($query) use ($search) {
                $query->where('id', 'like', "%{$search}%")
                      ->orWhereHas('seller', function($query) use ($search) {
                          $query->where('f_name', 'like', "%{$search}%")
                                ->orWhere('l_name', 'like', "%{$search}%");
                      });
            });
        }
    
        if (!empty($fromDate) && !empty($toDate)) {
            $stocks->whereBetween('created_at', [$fromDate, $toDate]);
        }
    
        $stocks = $stocks->paginate(Helpers::pagination_limit())->appends([
            'search' => $search,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);
        
        // $items = $stocks->items();
        foreach($stocks as $key => $item) {
            $item['statistcs'] = json_decode($item->statistcs);
        }
        // dd($stocks);
        // return $stocks[0]->statistcs->products[0]->price . ' ' . \App\CPU\Helpers::currency_symbol();
        return view('admin-views.pos.stocks.list', ['orders' => $stocks, 'fromDate' => $fromDate, 'toDate' => $toDate, 'search' => $search]);
        // return response()->json($data, 200);
    }
    
}
