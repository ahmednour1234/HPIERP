<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Product;
use App\Models\ReserveProduct;
use App\Models\CurrentReserveProduct;
use App\Models\CurrentOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Transection;
use App\Http\Controllers\Controller;
use App\Http\Resources\StocksResource;
use App\Models\ConfirmStock;
use App\Models\Installment;
use App\Models\StockOrder;
use App\Models\StockHistory;
use App\Models\Order;
use App\Http\Resources\ProductsResource;
use App\Models\Stock;
use Illuminate\Support\Facades\Auth;
use function App\CPU\translate;

class StockController extends Controller
{
    public function __construct(
        private Product $product,
        private ReserveProduct $reserve_product,
        private CurrentReserveProduct $current_reserve_products,
        private Stock $stock,
        private ConfirmStock $confirm_stock,
        private Installment $installment,
        private StockHistory $stock_history,
        private StockOrder $stock_order,
        private CurrentOrder $current_order,
        private Transection $transection
    ){}
    
public function index(Request $request): JsonResponse
{
    // استخلاص المدخلات
    $limit = $request->input('limit', 10);
    $offset = $request->input('offset', 1);
    $cat_id = $request->input('category_id', 0);
    $type = $request->input('type');
    $search = $request->input('search');

    $userId = auth()->user()->id;

    if ($type == 4) {
        // طلب مخزون البائع
        $query = $this->stock->where('seller_id', $userId)
            ->with(['product' => function ($q) {
                $q->orderBy('name', 'asc');
            }]);

        // تطبيق فلتر category_id إن وُجد
        if ($cat_id != 0) {
            $productIds = $this->product->where('category_id', $cat_id)->pluck('id');
            $query->whereIn('product_id', $productIds);
        }

        // جلب النتائج مع paginate
        $stocks = $query->paginate($limit, ['*'], 'page', $offset);

        // تعديل أسعار المنتجات حسب سعر البائع إن وجد
        foreach ($stocks as $stock) {
            $seller_price = \App\Models\SellerPrice::where([
                'seller_id' => $userId,
                'product_id' => $stock->product->id,
            ])->first();

            $stock->product->selling_price = $seller_price ? $seller_price->price : $stock->product->selling_price;
        }

        // تحويل إلى Resource
        $stocksResource = StocksResource::collection($stocks);

        return response()->json([
            'limit' => $limit,
            'offset' => $offset,
            'stocks' => $stocksResource,
            'current_page' => $stocks->currentPage(),
            'last_page' => $stocks->lastPage()
        ]);
    } else {
        // جلب المنتجات المرتبطة بالبائع
        $catIds = \App\Models\SellerCategory::where('seller_id', $userId)->pluck('cat_id');

        // فلتر category_id
        $filteredCatIds = $cat_id != 0 ? [$cat_id] : $catIds;

        $query = $this->product->whereIn('category_id', $filteredCatIds);

        // فلتر بالبحث إن وجد
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('product_code', 'LIKE', "%$search%")
                  ->orWhere('name', 'LIKE', "%$search%");
            });
        }

        // تنفيذ paginate
        $products = $query->orderBy('name', 'asc')
                          ->paginate($limit, ['*'], 'page', $offset);

        // تعديل السعر للبائع إن وجد
        foreach ($products as $product) {
            $seller_price = \App\Models\SellerPrice::where([
                'seller_id' => $userId,
                'product_id' => $product->id
            ])->first();

            $product->selling_price = $seller_price ? $seller_price->price : $product->selling_price;
        }

        // تحويل إلى Resource
        $productsResource = ProductsResource::collection($products);

        return response()->json([
            'limit' => $limit,
            'offset' => $offset,
            'products' => $productsResource,
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage()
        ]);
    }
}



    public function confirm(): JsonResponse
    {
        $seller = Auth::user();
        $stocks = $this->stock->where('seller_id', $seller->id)->whereRaw('main_stock != stock');
        $remain_stocks = $this->stock->where('seller_id', $seller->id)->whereRaw('main_stock = stock');
        
        $order_count = 0;
        $total_stock = 0;
        $total_remains_stock = 0;
        $total_cash = 0;
        $total_credit = 0;
        $product_count = 0;
        $products = [];
        $remain_products = [];
        
        $stock_order = $this->stock_order;
        
        $order_id = 30000000 + $stock_order->count() + 1;
        if ($stock_order->find($order_id)) {
            $order_id = $stock_order->orderBy('id', 'DESC')->first()->id + 1;
        }
        
        $stock_order->id = $order_id;
        $stock_order->seller_id = $seller->id;
        $stock_order->save();
        
        if($stocks->count() > 0 || $remain_stocks->count() > 0)
        {

            $this->confirm_stock->where('seller_id', $seller->id)->delete();
            foreach ($stocks->get() as $key => $item)
            {
                $c_stock = new $this->confirm_stock;
                $c_stock->seller_id = $seller->id;
                $c_stock->product_id = $item->product_id;
                $c_stock->main_stock = $item->main_stock;
                $c_stock->stock = $item->main_stock - $item->stock;
                $c_stock->save();
                
                $total_stock += $item->main_stock - $item->stock;
                // $total_stock += $c_stock->stock;
                // $total_remains_stock += $c_stock->main_stock - $c_stock->stock;
                $total_remains_stock += $item->stock;
                
                $h_stock = new $this->stock_history;
                $h_stock->seller_id = $seller->id;
                $h_stock->order_id = $order_id;
                $h_stock->product_id = $item->product_id;
                $h_stock->main_stock = $item->main_stock;
                $h_stock->stock = $item->main_stock - $item->stock;
                $h_stock->save();
                
                $product = $this->product->find($item->product_id);
                $product->quantity += $item->stock;
                $product->update();

                if($item->stock != 0)
                {
                    $remain_products[] = [
                        'name' => $item->product->name,
                        'name_en' => $item->product->name_en,
                        'quantity' => $item->stock,
                        'product_code' => $item->product->product_code,
                        'price' => $item->product->selling_price * $item->stock,
                    ];
                }
            }
            
            
            foreach ($remain_stocks->get() as $item) {
                $total_remains_stock += $item->stock;
                
                $c_stock = new $this->confirm_stock;
                $c_stock->seller_id = $seller->id;
                $c_stock->product_id = $item->product_id;
                $c_stock->main_stock = $item->main_stock;
                $c_stock->stock = $item->main_stock - $item->stock;
                $c_stock->save();
                
                $total_stock += $item->main_stock - $item->stock;
                $total_remains_stock += $item->stock;
                
                $h_stock = new $this->stock_history;
                $h_stock->seller_id = $seller->id;
                $h_stock->order_id = $order_id;
                $h_stock->product_id = $item->product_id;
                $h_stock->main_stock = $item->main_stock;
                $h_stock->stock = $item->main_stock - $item->stock;
                $h_stock->save();
                
                $product = $this->product->find($item->product_id);
                $product->quantity += $item->stock;
                $product->update();

                $remain_products[] = [
                    'name' => $item->product->name,
                    'name_en' => $item->product->name_en,
                    'quantity' => $item->stock,
                    'product_code' => $item->product->product_code,
                    'price' => $item->product->selling_price * $item->stock,
                ];
            }
            
            $orders = $this->current_order->where('owner_id', $seller->id);
            
            $product_count = $this->confirm_stock->where('seller_id', $seller->id)->whereRaw('created_at LIKE "' . date('Y-m-d') . '%"')->count();
            
            foreach($this->confirm_stock->where('seller_id', $seller->id)->where('stock', '!=', 0)->get() as $i => $item)
            {
                $products[$i]['name'] = $item->product->name;
                $products[$i]['name_en'] = $item->product->name_en;
                $products[$i]['product_code'] = $item->product->product_code;
                $products[$i]['quantity'] = $item->stock;
                foreach($orders->get() as $or) {
                    $value = \App\Models\OrderDetail::where(['product_id' => $item->product_id, 'order_id' => $or->id])->first();
                    if ($value)
                    {
                        $products[$i]['price'] = $value->price;
                    }
                }
            }
            
            $order_count = $orders->count();
        }
        
        $total_cash = $this->transection->where('tran_type', 4)->where('cash',1)->where('seller_id', $seller->id)->sum('amount');
        $total_credit = $this->transection->where('tran_type', 4)->where('cash',2)->where('seller_id', $seller->id)->sum('amount');
        $refund_total = $this->transection->where('tran_type', 7)->where('seller_id', $seller->id)->sum('amount');
        $installment = $this->installment->where('seller_id', $seller->id)->sum('total_price');
        
        $data = [
            'vehicle_code' => $this->vehicleCode($seller->vehicle_code),
            'vehicle_name' => \App\Models\Store::where('store_id', $seller->vehicle_code)->first()->store_name1,
            'product_count' => $stocks->count(),
            'total_stock' => $total_stock,
            'order_count' => $order_count,
            'remain_stock' => $total_remains_stock,
            'total_cash' => $total_cash,
            'total_credit' => $total_credit,
            'installment_total' => $installment,
            'refund_total' => $refund_total,
            'products' => $products,
            'remain_products' => $remain_products,
        ];
        
        
        $stock_order->statistcs = json_encode($data);
        $stock_order->update();

        $stocks->delete();
        $remain_stocks->delete();
        $this->current_order->where('owner_id', $seller->id)->delete();
        $this->current_reserve_products->where('seller_id', $seller->id)->delete();
$transactions = $this->transection->where('seller_id', $seller->id)->get();
foreach ($transactions as $transaction) {
    $transaction->active = 0;
    $transaction->save();
}
        // $this->installment->where('seller_id', $seller->id)->delete();

        return response()->json(['message' => 'Stocks confirmed successfully', 'data' => $data], 200);
    }
    
    public function history(Request $request)
    {
        $limit = $request['limit'] ?? 10;
        $offset = $request['offset'] ?? 1;
        $date = $request['date'] ?? null;
        
        $this->stock_order->whereNull('statistcs')->delete();
        $stocks = $this->stock_order->where('seller_id', Auth::user()->id)->latest()->get();
        
        $items = [];
        // dd(json_decode($stocks[0]['statistcs']));
        foreach($stocks as $key => $item) {
            $item['statistcs'] = json_decode($item->statistcs);
            
            $item['seller']->vehicle_code = $this->vehicleCode($item['seller']->vehicle_code);
        }
        
        $data = [
            'limit' => $limit,
            'offset' => $offset,
            'stocks' => $stocks
        ];
        return response()->json($data, 200);
    }


}
