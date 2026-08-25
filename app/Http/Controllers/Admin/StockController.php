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
use App\Models\StockOrder;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Stock;
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
        $stocks = $this->stock;
        $stocks = $stocks->paginate(Helpers::pagination_limit());
        return view('admin-views.vehicle_stocks.index', compact('stocks'));
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

    
    public function vehicle_products($seller_id): Factory|View|Application
    {
        $stocks = $this->confirm_stock->where('seller_id', $seller_id)->whereRaw('stock <= main_stock AND stock != 0')->get();
        $remain_stocks = $this->confirm_stock->where('seller_id', $seller_id)->whereRaw('stock = 0')->get();
        dd($stocks);
        return view('admin-views.vehicle_stocks.products', compact('stocks', 'remain_stocks'));
    }
    
    public function stock_products($seller_id): Factory|View|Application
    {
        $stocks = $this->stock->where('seller_id', $seller_id)->whereRaw('stock < main_stock AND stock != 0');
        $remain_stocks = $this->stock->where('seller_id', $seller_id)->whereRaw('main_stock = stock');
        $seller = Seller::find($seller_id);
        $orders = \App\Models\CurrentOrder::where('owner_id', $seller_id);
        return view('admin-views.vehicle_stocks.stocks', compact('stocks', 'remain_stocks', 'orders', 'seller'));
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
