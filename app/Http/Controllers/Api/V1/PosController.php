<?php

namespace App\Http\Controllers\Api\V1;

use App\CPU\Helpers;
use App\Models\CurrentOrder;
use App\Models\Order;
use App\Models\OrderNotification;
use App\Models\Account;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Customer;
use App\Models\OrderDetail;
use App\Models\OrderDetailNotification;
use App\Models\Installment;
use App\Models\HistoryInstallment;
use App\Models\HistoryTransection;
use App\Models\Transection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\BusinessSetting;
use function App\CPU\translate;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductsResource;
use App\Http\Resources\StocksResource;
use App\Models\ReserveProduct;
use App\Models\CurrentReserveProduct;
use App\Models\Stock;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;


class PosController extends Controller
{
    public function __construct(
        private CurrentOrder $current_order,
        private Order $order,
       private OrderDetail $order_details,
       private OrderNotification $order_notification,
     private OrderDetailNotification $order_details_notification,
        private Installment $installment,
        private HistoryInstallment $history_installment,
        private Account $account,
        private Product $product,
        private Stock $stock,
        private Customer $customer,
        private OrderDetail $order_detail,
        private ReserveProduct $reserveProduct,
        private CurrentReserveProduct $current_reserve_products,
        private HistoryTransection $history_transection,
        private Transection $transection,
        private BusinessSetting $business_setting,
    ){}
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getProductIndex(Request $request): JsonResponse
    {
        $limit = $request['limit'] ?? 1000;
        $offset = $request['offset'] ?? 1;
        $product = $this->product->latest()->paginate($limit, ['*'], 'page', $offset);
        $products = ProductsResource::collection($product);
        $data = [
            'total' => $products->total(),
            'limit' => $limit,
            'offset' => $offset,
            'products' => $products->items(),
        ];
        return response()->json($data, 200);
    }
    public function getProductCode(Request $request): JsonResponse
{
    $limit = $request->input('limit', 10);
    $offset = $request->input('offset', 1);
    $productCode = $request->input('product_code');

    // Check if product_code is provided to fetch a specific product
    if ($productCode) {
        $product = Product::where('product_code', $productCode)->first();
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }
        return response()->json(['product' => new ProductsResource($product)], 200);
    }

    // Fetch paginated products if product_code is not provided
    $products = Product::latest()->paginate($limit, ['*'], 'page', $offset);
    $productsCollection = ProductsResource::collection($products);

    $data = [
        'total' => $productsCollection->total(),
        'limit' => $limit,
        'offset' => $offset,
        'products' => $productsCollection->items(),
    ];

    return response()->json($data, 200);
}
public function getSellerProducts(Request $request)
{
    $seller = \App\Models\Seller::find(Auth::user()->id);

    // جمع التصنيفات المرتبطة بالبائع
    $categoryIds = [];
    foreach ($seller->cats as $category) {
        $categoryIds[] = $category->cat->id;
    }

    // فلتر category_id إن وجد، وتأكد أنه ضمن المسموح للبائع
    if ($request->has('category_id') && in_array($request->category_id, $categoryIds)) {
        $filteredCategoryIds = [$request->category_id];
    } else {
        $filteredCategoryIds = $categoryIds;
    }

    // جلب المنتجات مع التصنيف المطلوب
    $products = $this->product->whereIn('category_id', $filteredCategoryIds)
                              ->orderBy('name', 'asc')
                              ->get();

    // معالجة السعر حسب البائع
    foreach ($products as $product) {
        $seller_price = \App\Models\SellerPrice::where([
            'seller_id' => Auth::user()->id,
            'product_id' => $product->id
        ])->first();

        if ($seller_price) {
            $product->selling_price = $seller_price->price;
        } else {
            $product->price = $product->selling_price;
        }
    }

    // تحويل النتائج باستخدام Resource
    $productsResource = ProductsResource::collection($products);

    return response()->json($productsResource, 200);
}


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function orderList(Request $request): JsonResponse
    {
        $limit = $request['limit'] ?? 1000000;
        $offset = $request['offset'] ?? 1;
        $orders = $this->current_order->where('type', $request->type)->where('owner_id', auth()->user()->id)->with('account','customer','details')->latest()->paginate($limit, ['*'], 'page', $offset);
        $type = $request->type == 4 ? "orders" : "refnd";
        $data = [
            'total' => $orders->total(),
            'limit' => $limit,
            'offset' => $offset,
            $type => $orders->items(),
        ];
        return response()->json($data, 200);
    }
public function orderListnotinstall(Request $request): JsonResponse
{
    $limit   = (int) $request->input('limit', 1000000);
    $page    = (int) $request->input('offset', 1);
    $ownerId = auth()->id();
    $tolerance = 1.00; // جنيه سماح لكسر الكسور، يُستخدم في المقارنة فقط

    // Subquery: إجمالي الكمية للفاتورة الأصلية
    $originalQtySub = DB::table('order_details')
        ->selectRaw('order_id, SUM(ABS(quantity)) AS qty')
        ->groupBy('order_id');

    // Subquery: إجمالي كمية المرتجع مجمّعة على parent_id (أي الفاتورة الأصلية)
    $returnsQtySub = DB::table('order_details as od')
        ->join('orders as oc', 'oc.id', '=', 'od.order_id') // oc = returned order (child)
        ->whereNotNull('oc.parent_id')
        ->selectRaw('oc.parent_id AS parent_id, SUM(ABS(od.quantity)) AS qty')
        ->groupBy('oc.parent_id');

    // الاستعلام الرئيسي على الفواتير الأصلية فقط (ليست مرتجعات)
    $q = \App\Models\Order::from('orders as o')
        ->leftJoinSub($originalQtySub, 'oq', 'oq.order_id', '=', 'o.id')
        ->leftJoinSub($returnsQtySub, 'rq', 'rq.parent_id', '=', 'o.id')
        ->where('o.type', 4)
        ->where('o.owner_id', $ownerId)
        ->whereNull('o.parent_id') // مهم: نتعامل مع الفاتورة الأصلية فقط

        // الفاتورة "غير مُحصّلة" لو (المحصّل + جنيه) أقل من القيمة الفعلية بعد المرتجع
        ->whereRaw("
            (o.transaction_reference + ?) < GREATEST(
                0,
                o.order_amount
                - (
                    (CASE WHEN COALESCE(oq.qty,0) > 0
                          THEN (o.order_amount / COALESCE(oq.qty,0))
                          ELSE 0 END)
                    * COALESCE(rq.qty,0)
                  )
            )
        ", [$tolerance])

        // معلومات مشتقة مفيدة للواجهة/التتبع
        ->selectRaw("
            o.*,
            COALESCE(oq.qty,0) AS original_qty,
            COALESCE(rq.qty,0) AS returned_qty,
            (CASE WHEN COALESCE(oq.qty,0) > 0
                  THEN (o.order_amount / COALESCE(oq.qty,0))
                  ELSE 0 END) AS unit_price,
            ((CASE WHEN COALESCE(oq.qty,0) > 0
                   THEN (o.order_amount / COALESCE(oq.qty,0))
                   ELSE 0 END) * COALESCE(rq.qty,0)) AS return_amount,
            GREATEST(
              0,
              o.order_amount
              - ((CASE WHEN COALESCE(oq.qty,0) > 0
                       THEN (o.order_amount / COALESCE(oq.qty,0))
                       ELSE 0 END) * COALESCE(rq.qty,0))
            ) AS effective_amount
        ")
        ->with(['account','customer'])
        ->orderByDesc('o.id');

    $orders = $q->paginate($limit, ['*'], 'page', $page);

    $type = ((int)$request->input('type') === 4) ? 'orders' : 'refnd';

    return response()->json([
        'total'  => $orders->total(),
        'limit'  => $limit,
        'offset' => $page,
        $type    => $orders->items(),
    ], 200);
}
      public function orderListinstall(Request $request): JsonResponse
{
    $limit    = (int) ($request->input('limit', 1000000));
    $page     = (int) ($request->input('offset', 1));
    $ownerId  = auth()->id();
    $tolerance = 1.00; // جنيه سماح لكسر الكسور

    // إجمالي كميات الفاتورة الأصلية
    $originalQtySub = DB::table('order_details')
        ->selectRaw('order_id, SUM(ABS(quantity)) AS qty')
        ->groupBy('order_id');

    // إجمالي كميات المرتجعات مجمعة على parent_id (أي ترجع للأصل)
    $returnsQtySub = DB::table('order_details as od')
        ->join('orders as oc', 'oc.id', '=', 'od.order_id') // oc = returned order (child)
        ->whereNotNull('oc.parent_id')
        ->selectRaw('oc.parent_id AS parent_id, SUM(ABS(od.quantity)) AS qty')
        ->groupBy('oc.parent_id');

    $q = \App\Models\Order::from('orders as o')
        ->leftJoinSub($originalQtySub, 'oq', 'oq.order_id', '=', 'o.id')
        ->leftJoinSub($returnsQtySub, 'rq', 'rq.parent_id', '=', 'o.id')
        ->where('o.type', 4)
        ->where('o.owner_id', $ownerId)
        ->whereNull('o.parent_id') // مهم: فواتير أصلية فقط

        // الفاتورة "مُحصّلة" لو (المحصّل + جنيه) >= القيمة الفعلية بعد المرتجع
        ->whereRaw("
            (o.transaction_reference + ?) >= GREATEST(
                0,
                o.order_amount
                - (
                    (CASE WHEN COALESCE(oq.qty,0) > 0
                          THEN (o.order_amount / COALESCE(oq.qty,0))
                          ELSE 0 END)
                    * COALESCE(rq.qty,0)
                  )
            )
        ", [$tolerance])

        // اختيار أعمدة مشتقة (مفيدة لو حبيت تعرضها في الـ API)
        ->selectRaw("
            o.*,
            COALESCE(oq.qty,0) AS original_qty,
            COALESCE(rq.qty,0) AS returned_qty,
            (CASE WHEN COALESCE(oq.qty,0) > 0
                  THEN (o.order_amount / COALESCE(oq.qty,0))
                  ELSE 0 END) AS unit_price,
            ((CASE WHEN COALESCE(oq.qty,0) > 0
                   THEN (o.order_amount / COALESCE(oq.qty,0))
                   ELSE 0 END) * COALESCE(rq.qty,0)) AS return_amount,
            GREATEST(
              0,
              o.order_amount
              - ((CASE WHEN COALESCE(oq.qty,0) > 0
                       THEN (o.order_amount / COALESCE(oq.qty,0))
                       ELSE 0 END) * COALESCE(rq.qty,0))
            ) AS effective_amount
        ")
        ->with(['account','customer'])
        ->orderByDesc('o.id');

    $orders = $q->paginate($limit, ['*'], 'page', $page);

    $type = ((int) $request->input('type') === 4) ? 'orders' : 'refnd';

    return response()->json([
        'total'  => $orders->total(),
        'limit'  => $limit,
        'offset' => $page,
        $type    => $orders->items(),
    ], 200);
}
    public function installmentList(Request $request): JsonResponse
    {
        $limit = $request['limit'] ?? 100000;
        $offset = $request['offset'] ?? 1;

        if (Auth::user()->role == 'admin') {
            $installments = $this->installment->with('seller', 'customer')->latest()->paginate($limit, ['*'], 'page', $offset);
        }
        else {
            $installments = $this->installment->where('seller_id', Auth::user()->id)->with('seller', 'customer')->latest()->paginate($limit, ['*'], 'page', $offset);
        }
        
        $data = [];
        foreach($installments->items() as $i => $install)
        {
            $data[$i]['id'] = $install->id;
            $data[$i]['seller']['name'] = $install->seller->f_name . ' ' . $install->seller->l_name;
                        $data[$i]['customer']['id'] = $install->customer->id;
            $data[$i]['customer']['name'] = $install->customer->name;
            $data[$i]['price'] = $install->total_price;
            $data[$i]['date'] = $install->created_at;
        }
        
        $data = [
            'total' => $installments->total(),
            'limit' => $limit,
            'offset' => $offset,
            'installments' => $data,
        ];
        return response()->json($data, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
public function invoiceGenerate(Request $request): JsonResponse
{
    // التحقق من وجود معامل order_id وإرجاع خطأ إذا لم يكن موجودًا
    if (!$request->has('order_id') || empty($request->input('order_id'))) {
        return response()->json(['errors' => ['order_id' => ['Order id is required.']]], 403);
    }

    // استخراج قيمة order_id من الطلب
    $order_id = $request->input('order_id');

    // البحث عن الفاتورة في جدول الطلبات مع العلاقات المطلوبة
    $invoice = $this->order->with(['details', 'details.product', 'account', 'seller', 'customer'])
                           ->where('id', $order_id)
                           ->first();

    // طباعة الفاتورة للتأكد (يمكن إزالة print_r بعد مرحلة التصحيح)

    // في حال عدم وجود فاتورة في جدول الطلبات، يتم البحث في جدول order_notifications (أو جدول آخر حسب الحاجة)
    if (!$invoice) {
        $invoice = $this->order->with(['details', 'details.product', 'account', 'seller', 'user'])
                               ->where('id', $order_id)
                               ->first();

        if (!$invoice) {
            return response()->json(['message' => 'Order not found'], 404);
        }
    }

    // تقريب قيمة order_amount (يمكنك تعديل إذا كنت تريد عملية تقريب محددة)
    $invoice->order_amount = $invoice->order_amount;
    
    // تعديل vehicle_code للبائع باستخدام دالة vehicleCode

    return response()->json([
        'success' => true,
        'invoice' => $invoice,
    ], 200);
}


    
    public function installmentInvoiceGenerate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'installment_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        
        $invoice = $this->installment->with(['customer'])->where(['id' => $request['installment_id']])->first();
        $invoice['seller']->vehicle_code = $this->vehicleCode($invoice->seller->vehicle_code);
        return response()->json([
            'success' => true,
            'invoice' => $invoice,
        ], 200);
    }
    
public function reserveProduct(Request $request)
{
    // Validate request data
    if ($request['data']) {
        if (count($request['data']) < 1) {
            return response()->json(['message' => 'Data empty'], 403);
        }
    } else {
        return response()->json(['message' => 'Data empty'], 403);
    }

    $reserveProduct = new \App\Models\ReserveProduct();
            $current_reserve_products = $this->current_reserve_products;

    $data = [];
    
    foreach ($request->data as $i => $item) {
        $product_id = $item['product_id'];

        // Retrieve the product
        $product = \App\Models\Product::find($product_id);
        if (!$product) {
            return response()->json(['message' => "Product not found for ID: $product_id"], 404);
        }

        // First check for customer price
        $customerPrice = \App\Models\CustomerPrice::where('product_id', $product_id)
            ->where('customer_id', $request->customer_id)
            ->first();

        // If customer price is not found, check for seller price
        $seller_price = null;
        if (!$customerPrice) {
            $seller_price = \App\Models\SellerPrice::where(['seller_id' => Auth::user()->id, 'product_id' => $product_id])->first();
        }

        // Determine the final price
        $price = $customerPrice ? $customerPrice->price : ($seller_price ? $seller_price->price : $product->selling_price);
         if ($request->type == '7') {

                if (  $item['balance'] < $item['stock']) {
                    return response()->json([
                    'message' => 'هذا المنتج لا توجد منه كمية كافية في المخزون للاسترجاع: ' . $product->name
                    ], 403);
                }
            }

        // Prepare data for the reservation
        $data[$i] = [
            'product_name' => $item['product_name'],
            'product_id' => $product_id,
            'stock' => $item['stock'],
            'balance' => $item['balance'],
            'price' => $price, // Use the determined price
        ];
    }

    // Generate order ID
    $order_id = 20000000 + $reserveProduct->count() + 1;
    if ($reserveProduct->find($order_id)) {
        $order_id = $reserveProduct->orderBy('id', 'DESC')->first()->id + 1;
    }

    // Save the reservation
    $reserveProduct->id = $order_id;
    $reserveProduct->data = json_encode($data);
    $reserveProduct->seller_id = Auth::user()->id;
    $reserveProduct->date = now()->format('Y-m-d'); // Use now() for current date
    $reserveProduct->customer_id = $request->customer_id;
    $reserveProduct->type = $request->type;
    $reserveProduct->active = 1; // Set the active column to 1
    $reserveProduct->save();
     $current_reserve_products->id = $order_id;
        $current_reserve_products->data = json_encode($data);
        $current_reserve_products->seller_id = Auth::user()->id;
        $current_reserve_products->date = date('Y-m-d');
        $current_reserve_products->customer_id = $request->customer_id;
        $current_reserve_products->type = $request->type;
        $current_reserve_products->save();
    return response()->json([
        'message' => 'Reservation successful',
        'id' => $reserveProduct->id
    ], 200);
}




public function reservations(Request $request, $type)
{
    $limit = $request->input('limit', 100000);
    $offset = $request->input('offset', 1);

    $reserveProduct = $this->reserveProduct
        ->where('seller_id', Auth::user()->id)
        ->where('type', $type)
        ->with('customer')
        ->latest()
        ->paginate($limit, ['*'], 'page', $offset);

    foreach ($reserveProduct as $item) {
        $item['data'] = json_decode($item['data']);
        
        $item['seller']->vehicle_code = $this->vehicleCode($item->seller->vehicle_code);
        
        foreach ($item['data'] as $value) {
            // Find the product to get its product_code
            $product = Product::find($value->product_id);
            if ($product) {
                $value->product_code = $product->product_code;
            }
        }
    }

    $data = [
        'total' => $reserveProduct->total(),
        'limit' => $limit,
        'offset' => $offset,
        'reservations' => $reserveProduct->items(),
    ];

    return response()->json($data, 200);
}
public function placeOrder(Request $request): JsonResponse
{
    // Check if the cart is empty
    if (empty($request->cart)) {
        return response()->json(['message' => 'Cart is empty'], 403);
    }

    // Preprocess the cart string to make it valid JSON
    $cart = preg_replace('/([{,])(\s*)([a-zA-Z0-9_]+)(\s*:\s*)/', '$1$2"$3"$4', $request->cart);
    $cart = json_decode($cart, true); // Decode the modified JSON string

    // Validate that the cart is decoded and is an array
    if (!$cart || !is_array($cart) || count($cart) < 1) {
        return response()->json(['message' => 'Cart is empty or invalid'], 403);
    }

    // Retrieve customer details
    $customer = \App\Models\Customer::find($request->user_id);
    if (!$customer) {
        return response()->json(['message' => 'Customer not found'], 404);
    }

    // Set up order details
    $user_id = $customer->id;
    $order_id = $this->order->max('id') + 1;
    $coupon_discount = $request->coupon_discount ?? 0;

    // Handle image upload if exists
    $imgPath = null;
    if ($request->hasFile('img')) {
        $imgPath = $request->file('img')->store('shop', 'public'); // Store the image
        $fileName = $request->file('img')->getClientOriginalName(); // Get original filename (optional)
    }


    // Prepare order data
    $orderData = [
        'id' => $order_id,
        'owner_id' => Auth::id(),
        'user_id' => $user_id,
        'coupon_code' => $request->coupon_code,
        'coupon_discount_title' => $request->coupon_title,
        'payment_id' => $request->type,
        'cash' => $request->cash,
        'img' => $imgPath, // Store the full path
        'total_tax' => $request->total_tax,
        'order_amount' => $request->order_amount,
        'extra_discount' => $request->extra_discount,
        'coupon_discount_amount' => $coupon_discount,
        'collected_cash' => $request->collected_cash,
        'transaction_reference' => $request->collected_cash,
        'type' => $request->order_type,
        'created_at' => now(),
        'updated_at' => now(),
    ];

    $order = $this->order->newInstance($orderData);
    $c_order = $this->current_order->newInstance($orderData);

    // Initialize totals and order details
    $product_price = 0;
    $product_discount = 0;
    $product_tax = 0;
    $order_details = [];

    // Process each item in the cart
    foreach ($cart as $cartItem) {
        if (is_array($cartItem)) {
            $product = $this->product->find($cartItem['id']);
            if (!$product) continue;

            // Retrieve customer or seller price
            $customerPrice = \App\Models\CustomerPrice::where(['product_id' => $cartItem['id'], 'customer_id' => $user_id])->first();
            $seller_price = \App\Models\SellerPrice::where(['seller_id' => Auth::id(), 'product_id' => $cartItem['id']])->first();
            $price = $customerPrice ? $customerPrice->price : ($seller_price ? $seller_price->price : $cartItem['price']);
// Check stock availability
$stock = Stock::where('seller_id', Auth::id())
              ->where('product_id', $cartItem['id'])
              ->first();

// If stock doesn't exist and order_type is 7, create a new stock entry
if (!$stock && $request->order_type == 7) {
    // Retrieve the store_id associated with the seller_id
    $store = Store::where('seller_id', Auth::id())->first();

    if ($store) {
        // Create the stock with the retrieved store_id
        $stock = Stock::create([
            'seller_id' => Auth::id(),
            'store_id' => $store->id, // Use the store_id from the stores table
            'product_id' => $cartItem['id'],
            'main_stock' => $cartItem['quantity'],
            'stock' => $cartItem['quantity'],
        ]);
    } else {
        // Handle the case where no store is found for the seller
        return response()->json(['error' => 'No store found for the seller'], 404);
    }
}

if (($request->order_type == 4 || $request->order_type == 12 || $request->order_type == 24) && $stock && $stock->stock < $cartItem['quantity']) {
                return response()->json(['message' => 'Insufficient stock for product: ' . $product->name], 422);
            }

            if ($stock) {
                $stock->stock = $request->order_type == 7 ? $stock->stock + $cartItem['quantity']: $stock->stock - $cartItem['quantity'] ;
                $stock->save(); // Save the stock update
            }

            // Prepare product details for the order
            $product_details = [
                'product_id' => $cartItem['id'],
                'product_details' => $product,
                'quantity' => $cartItem['quantity'],
                'price' => $cartItem['price'],
                'tax_amount' => Helpers::tax_calculate($product, $cartItem['price']),
                'discount_on_product' => Helpers::discount_calculate($product, $cartItem['price']),
                'discount_type' => 'discount_on_product',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Accumulate totals
            $product_price += $cartItem['price'] * $cartItem['quantity'];
            $product_discount += $cartItem['discount'] * $cartItem['quantity'];
            $product_tax += $cartItem['tax'] * $cartItem['quantity'];
            $order_details[] = $product_details;

            // Increment order count for the product
            $product->increment('order_count');
        }
    }

    // Calculate totals and grand total
    $total_price = $product_price - $product_discount;
    $extra_discount = $request->extra_discount_type == 'percent' ? ($total_price * $request->extra_discount) / 100 : $request->extra_discount;
    $grand_total = $total_price + $request->total_tax - $extra_discount - $coupon_discount;

    $order->fill([
        'collected_cash' => $request->collected_cash ?? $grand_total,
        'order_amount' =>  $request->order_amount,
    ]);

    $c_order->fill($order->getAttributes());

try {
    // Save the order and current order
    $order->save();
    $c_order->save();
    $this->order_detail->insert(array_map(fn($item) => array_merge($item, ['order_id' => $order_id]), $order_details));

    // Check if the order type is NOT 12 or 24 before processing transaction
    if (!in_array($request->order_type, [12, 24])) {
        // Determine description based on order type
        $description = $request->order_type == 4 ? 'مبيعات' : 'مرتجع مبيعات';

        // Handle transaction data
        $account = $this->account->find($request->type);
        $transaction_data = [
            'tran_type' => $request->order_type,
            'seller_id' => Auth::id(),
            'account_id' => $request->type,
            'amount' => $request->collected_cash,
            'cash' => $request->cash,
            'description' => $description,
            'date' => now(),
            'balance' =>  $request->collected_cash,
            'customer_id' => $user_id,
            'order_id' => $order_id,
            'img' => $imgPath,
        ];

        // If order_type is 7, don't process account or transaction, update customer credit instead
        if ($request->order_type == 7) {
            // Assuming $customer is retrieved from the database using $user_id
            $customer = $this->customer->find($user_id);
                        $this->transection->create($transaction_data);
            // Increment the customer's credit
            $customer->increment('balance', $grand_total);
        } else {
            $seller = Seller::where('id', Auth::id())->first();
            // Create transaction
            $seller->commission+= $request->collected_cash;
$seller->credit+= $request->collected_cash;
$seller->save();
            $this->transection->create($transaction_data);
            //   $seller->increment('commission',$grand_total);
            // // Update account balances (if order_type is not 7)
            // $account->increment('balance', $grand_total);
            // $account->increment('total_in', $grand_total);
            if ($request->cash == 2) {
    $customer = $this->customer->find($user_id); // Retrieve customer
    $final=$request->order_amount-$request->collected_cash;
    $customer->increment('credit',$final); // Increment customer's credit balance
}
        }
    }

    return response()->json(['message' => 'Order placed successfully', 'order_id' => $order_id], 200);
} 
 catch (\Exception $e) {
        return response()->json(['message' => 'Failed to place order'.$e->getMessage(), 'error' => $e->getMessage()], 400);
    }
}




public function placeInstallment(Request $request): JsonResponse
{
    // ========= 0) Validate with logging =========
    try {
        $validator = Validator::make($request->all(), [
            'user_id'    => 'required|exists:customers,id',
            'payment_id' => 'required|exists:accounts,id',
            'order_id'   => 'required|exists:orders,id',
            'price'      => 'required|numeric|min:1',
            'note'       => 'required|string',
            'img'        => 'nullable|image',
        ]);

        if ($validator->fails()) {
            Log::warning('Installment validation failed', [
                'errors' => $validator->errors()->toArray(),
                'input'  => $request->except('img'),
                'seller' => Auth::id(),
            ]);
            return response()->json([
                'status'  => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }
    } catch (ValidationException $ve) {
        Log::error('ValidationException during installment', [
            'exception' => $ve,
            'input'     => $request->except('img'),
            'seller'    => Auth::id(),
        ]);
        return response()->json([
            'status'  => false,
            'message' => 'Validation exception',
            'errors'  => $ve->errors(),
        ], 422);
    }

    // ========= 1) Pre-store image (will delete on rollback) =========
    $imgPath = null;
    try {
        if ($request->hasFile('img')) {
            $img = $request->file('img');
            $imgPath = $img->store('shop', 'public');
            Log::info('Installment image stored', [
                'path'      => $imgPath,
                'mime'      => $img->getClientMimeType(),
                'size'      => $img->getSize(),
                'seller_id' => Auth::id(),
            ]);
        }
    } catch (\Throwable $e) {
        Log::error('Failed to store installment image', [
            'exception' => $e,
            'seller_id' => Auth::id(),
        ]);
        return response()->json([
            'status'  => false,
            'message' => 'Failed to store image',
            'error'   => $e->getMessage(),
        ], 400);
    }

    // helper: delete image on any failure
    $deleteImageIfAny = function () use (&$imgPath) {
        if ($imgPath) {
            try {
                Storage::disk('public')->delete($imgPath);
                Log::info('Installment image deleted after failure/rollback', ['path' => $imgPath]);
            } catch (\Throwable $t) {
                Log::error('Failed to delete image on rollback', [
                    'exception' => $t,
                    'path'      => $imgPath,
                ]);
            } finally {
                $imgPath = null;
            }
        }
    };

    Log::info('DB transaction starting for installment', [
        'seller_id'  => Auth::id(),
        'user_id'    => $request->user_id,
        'order_id'   => $request->order_id,
        'payment_id' => $request->payment_id,
        'price'      => $request->price,
    ]);

    try {
        // هنجمع القيم اللي محتاجينها للـ success response بدون ما نغير شكله
        $installmentId = null;
        $effectiveAmount = 0.0;
        $returnAmount = 0.0;
        $unitPrice = 0.0;
        $originalQty = 0.0;
        $returnedQty = 0.0;
        $verifiedRef = 0.0;

        DB::transaction(function () use ($request, &$imgPath, &$installmentId, &$effectiveAmount, &$returnAmount, &$unitPrice, &$originalQty, &$returnedQty, &$verifiedRef) {

            $sellerId = Auth::id();

            // ========= 2) Lock rows =========
            /** @var Order|null $order */
            $order = Order::where('id', $request->order_id)
                ->where('user_id', $request->user_id)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                throw new \RuntimeException('هذا العميل لا يملك هذه الفاتورة.', 400);
            }

            /** @var Customer $customer */
            $customer = Customer::lockForUpdate()->findOrFail($request->user_id);

            /** @var Seller $seller */
            $seller   = Seller::lockForUpdate()->findOrFail($sellerId);

            // (اختياري) قفل الحساب المالي
            $accountExists = DB::table('accounts')->where('id', $request->payment_id)->lockForUpdate()->exists();
            if (!$accountExists) {
                throw new \RuntimeException('الحساب المالي غير موجود.', 400);
            }

            // ========= 3) Compute effective amount with returns =========
            $price       = (float) $request->price;
            $oldRef      = (float) $order->transaction_reference; // بدّل الاسم لو مختلف
            $orderAmt    = (float) $order->order_amount;

            $originalQty = (float) DB::table('order_details')
                ->where('order_id', $order->id)
                ->selectRaw('COALESCE(SUM(ABS(quantity)), 0) AS qty')
                ->value('qty');

            $unitPrice = $originalQty > 0 ? $orderAmt / $originalQty : 0.0;

            $returnOrderIds = Order::where('parent_id', $order->id)
                ->lockForUpdate()
                ->pluck('id');

            $returnedQty = 0.0;
            if ($returnOrderIds->isNotEmpty()) {
                $returnedQty = (float) DB::table('order_details')
                    ->whereIn('order_id', $returnOrderIds)
                    ->selectRaw('COALESCE(SUM(ABS(quantity)), 0) AS qty')
                    ->value('qty');
            }

            $returnAmount    = round($unitPrice * $returnedQty, 2);
            $effectiveAmount = max(0.0, $orderAmt - $returnAmount);

            Log::info('Unit price & returns calculation for installment', [
                'order_id'         => $order->id,
                'order_amount'     => $orderAmt,
                'original_qty'     => $originalQty,
                'unit_price'       => $unitPrice,
                'returned_qty'     => $returnedQty,
                'return_amount'    => $returnAmount,
                'effective_amount' => $effectiveAmount,
                'old_ref'          => $oldRef,
                'add_price'        => $price,
            ]);

            if ($oldRef >= $effectiveAmount) {
                throw new \RuntimeException('هذه الفاتورة محصّلة بالكامل بعد احتساب المرتجعات.', 400);
            }

            $newRef = $oldRef + $price;

            $epsilon = 1; // هامش بسيط
            if ($newRef - $effectiveAmount > $epsilon) {
                throw new \RuntimeException('المبلغ المدفوع يتجاوز القيمة المسموح بها بعد احتساب المرتجعات.', 400);
            }

            // ========= 4) Create Installment =========
            $installment = new Installment();
            $installment->seller_id   = $sellerId;
            $installment->customer_id = $customer->id;
            $installment->order_id    = $order->id;
            $installment->total_price = $price;
            $installment->note        = $request->note;
            $installment->img         = $imgPath;
            $installment->save();
            $installmentId = $installment->id;

            Log::info('Installment created', [
                'installment_id' => $installmentId,
                'order_id'       => $order->id,
                'customer_id'    => $customer->id,
                'seller_id'      => $sellerId,
                'price'          => $price,
            ]);

            // ========= 5) HistoryInstallment =========
            $history = new HistoryInstallment();
            $history->seller_id   = $sellerId;
            $history->customer_id = $customer->id;
            $history->order_id    = $order->id;
            $history->total_price = $price;
            $history->note        = $request->note;
            $history->img         = $imgPath;
            $history->save();

            // ========= 6) Transection =========
            $trx = new Transection();
            $trx->tran_type   = 26;
            $trx->account_id  = $request->payment_id;
            $trx->amount      = $price;
            $trx->description = $request->note;
            $trx->debit       = $price;  // التحصيل يزيد رصيد الحساب
            $trx->credit      = 0;
            $trx->balance     = null;
            $trx->date        = now();
            $trx->customer_id = $customer->id;
            $trx->seller_id   = $sellerId;
            $trx->img         = $imgPath;
            $trx->save();

            // ========= 7) Update order reference + verify =========
            $order->transaction_reference = $newRef; // بدّل الاسم لو مختلف
            $order->save();

            $verifiedRef = (float) Order::where('id', $order->id)->value('transaction_reference');
            if (abs($verifiedRef - $newRef) > 0.00001) {
                Log::error('Order transaction_reference verification failed', [
                    'order_id'  => $order->id,
                    'expected'  => $newRef,
                    'actual'    => $verifiedRef,
                    'seller_id' => $sellerId,
                ]);
                throw new \RuntimeException('فشل تأكيد تحديث transaction_reference على الفاتورة.', 500);
            }

            // ========= 8) Update seller & customer =========
            $seller->commission = (float) $seller->commission + $price;
            $seller->credit     = (float) $seller->credit + $price;
            $seller->save();

            $customer->credit   = (float) $customer->credit - $price;
            $customer->save();
        }, 3); // retries

        Log::info('DB transaction committed for installment', [
            'installment_id'  => $installmentId,
            'paid_so_far'     => $verifiedRef,
            'effective_total' => $effectiveAmount,
            'return_amount'   => $returnAmount,
            'original_qty'    => $originalQty,
            'returned_qty'    => $returnedQty,
            'unit_price'      => $unitPrice,
        ]);

        // ======== SUCCESS RESPONSE (بدون أي تغيير في الشكل/المفاتيح) ========
        return response()->json([
            'status'          => true,
            'message'         => 'Installment placed successfully',
            'installment_id'  => $installmentId,
            'effective_total' => $effectiveAmount,
            'return_amount'   => $returnAmount,
            'unit_price'      => $unitPrice,
            'original_qty'    => $originalQty,
            'returned_qty'    => $returnedQty,
            'paid_so_far'     => $verifiedRef,
            'remaining'       => max(0, $effectiveAmount - $verifiedRef),
        ], 200);

    } catch (\Throwable $e) {
        // أي خطأ هنا يعني إن الـ transaction اتعمله rollback تلقائي
        $deleteImageIfAny();

        $status = ($e instanceof \RuntimeException && $e->getCode() >= 400 && $e->getCode() < 500) ? $e->getCode() : 500;

        Log::error('Installment placement failed; transaction rolled back', [
            'exception'  => $e,
            'seller_id'  => Auth::id(),
            'user_id'    => $request->user_id ?? null,
            'order_id'   => $request->order_id ?? null,
            'payment_id' => $request->payment_id ?? null,
            'price'      => $request->price ?? null,
            'status'     => $status,
        ]);

        return response()->json([
            'status'  => false,
            'message' => $status === 400 ? $e->getMessage() : 'Failed to place installment',
            'error'   => config('app.debug') ? $e->getMessage() : null,
        ], $status);
    }
}


    /**
     * @param Request $request
     * @return JsonResponse
     */
public function getSearch(Request $request): JsonResponse
{
    $limit = $request->input('limit', 10000); // Default limit to 10
    $offset = $request->input('offset', 1); // Default offset to 1 (current page)
    $search = $request->input('name');
    $type = $request->input('type');
    $products = [];

    if (!empty($search)) {
        // Get product IDs based on search criteria
        $product_ids = $this->product
            ->where('product_code', 'LIKE', "%$search%")
            ->orWhere('name', 'LIKE', "%$search%")
            ->pluck('id');

        // Fetch stocks for the seller
        $result = $this->stock
            ->whereIn('product_id', $product_ids)
            ->where('seller_id', Auth::id())
            ->get();

        // Transform the result into a resource collection
        $products = StocksResource::collection($result);

        if ($type != 4) {
            // If type is not 4, fetch products based on category
            $cat_ids = \App\Models\Seller::find(auth()->user()->id)->cats->pluck('cat_id');
            $result = $this->product
                ->whereIn('category_id', $cat_ids)
                ->where(function ($query) use ($search) {
                    $query->where('product_code', 'LIKE', "%$search%")
                          ->orWhere('name', 'LIKE', "%$search%");
                })
                ->get();

            // Loop through products to check seller prices
            foreach ($result as $product) {
                // Check if there is a seller price
                $seller_price = \App\Models\SellerPrice::where(['seller_id' => Auth::user()->id, 'product_id' => $product->id])->first();
                
                // Set the price based on seller price or default to selling price
                $product->selling_price = $seller_price ? $seller_price->price : $product->selling_price;
            }

            // Transform the result into a resource collection
            $products = ProductsResource::collection($result);
        }
    }

    // Calculate pagination data
    $total = count($products);
    $last_page = ceil($total / $limit); // Last page is the total products divided by limit, rounded up
    $current_page = $offset; // Current page is the offset provided in the request

    // Prepare the response data
    $data = [
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'current_page' => $current_page,
        'last_page' => $last_page,
        'products' => $products,
    ];

    return response()->json($data, 200);
}
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Throwable
     */
    public function delete(Request $request): JsonResponse
    {
        try {
            $product = $this->product->findOrFail($request->id);
            $image_path = public_path('/storage/app/public/product/') . $product->image;
            if (!is_null($image_path)) {
                $product->delete();
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            return response()->json([
                'success' => true,
                'message' => translate('Product deleted successfully'),
            ], 200);
        } catch (\Throwable $th) {
            throw $th;
            return redirect()->back()->with('success', 'Product not deleted!');
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function orderGetSearch(Request $request): JsonResponse
    {
        $limit = $request['limit'] ?? 100000;
        $offset = $request['offset'] ?? 1;
        $search = $request->name;
        if (!empty($search)) {
            $result = $this->order->where('id', 'like', '%' . $search . '%')->latest()->paginate($limit, ['*'], 'page', $offset);
            $data = [
                'total' => $result->total(),
                'limit' => $limit,
                'orders' => $result->items(),
            ];
        } else {
            $data = [
                'total' => 0,
                'limit' => $limit,
                'offset' => $offset,
                'orders' => [],
            ];
        }
        return response()->json($data, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function customerOrders(Request $request): JsonResponse
    {
        $limit = $request['limit'] ?? 100000;
        $offset = $request['offset'] ?? 1;

        $orders = $this->order->with('account')->where('user_id', $request->customer_id)->latest()->paginate($limit, ['*'], 'page', $offset);
        $data = [
            'total' => $orders->total(),
            'limit' => $limit,
            'offset' => $offset,
            'orders' => $orders->items(),
        ];
        return response()->json($data, 200);
    }
    public function processConfirmedReturn(Request $request)
{
    // Start database transaction
    \DB::beginTransaction();

    try {
        // 1. Validate inputs
        $request->validate([
            'order_id'                  => 'required|integer|exists:orders,id',
            'return_quantities_hidden'  => 'required|array',
            'date'                      => 'required|date',
            'type'                      => 'nullable|integer',
        ]);

        // 2. Retrieve old order and its details
        $orderId       = $request->input('order_id');
        $oldOrder      = \App\Models\Order::with('details.product')
                              ->findOrFail($orderId);
    // only sales invoices (type = 4) can be returned
if ($oldOrder->type == 7 || $oldOrder->type==12 || $oldOrder->type==24 ) {
    return response()->json([
        'error'   => true,
        'message' => 'لا يمكنك سوى عمل مرتجع لفواتير البيع فقط',
    ], 422);
}
                   
                              
        $orderProducts = $oldOrder->details;

        // 3. Retrieve financial parameters from the original order
        $extraDiscount     = $oldOrder->extra_discount ?? 0;
        $totalTax          = $oldOrder->total_tax ?? 0;
        $baseOrderAmount   = $oldOrder->order_amount ?? 0;

        // 4. Compute total discount on products
        $totalProductDiscount = $orderProducts->sum(function($item) {
            return ($item->discount_on_product * $item->quantity);
        });

        // 5. Compute overall order amount and discount ratio
        $orderAmount   = $baseOrderAmount + $extraDiscount + $totalProductDiscount - $totalTax;
        $orderAmount   = max($orderAmount, 1);  // avoid division by zero
        $discountRatio = ($extraDiscount / $orderAmount) * 100;

        // 6. Retrieve return requests
        $returnQuantities = $request->input('return_quantities_hidden', []);
        $returnType       = $request->input('type', 7);

        // 7. Validation: ensure we don't return more than ordered
        foreach ($orderProducts as $product) {
    $pid       = $product->product_id;
    $newReturn = isset($returnQuantities[$pid]) 
                    ? (float)$returnQuantities[$pid] 
                    : 0;

    if ($newReturn > 0) {
        // Decode product details to obtain unit_value
        $details   = json_decode($product->product_details);
        $unitValue = $details->unit_value ?? 1;

        // Which unit did the user choose for this return?

        // Grab the old order detail record
        $oldDetail = $oldOrder->details
                        ->where('product_id', $pid)
                        ->first();

        // Compute how much was already returned, in the "base" (large) unit:
        if ($oldDetail) {
                // old detail was recorded in small units → convert back to large
                $alreadyReturned = ($oldDetail->quantity_returned ?? 0);
          
        } else {
            $alreadyReturned = 0;
        }

        // Figure out how many units are actually available on the original order
            // returning in small units → total stock = original qty × unitValue
            $availableQty       = $oldDetail->quantity ;
            $newReturnConverted = $newReturn;  // user already gave it in small units

        // Final check: can't return more than what's available
        if (($alreadyReturned + $newReturnConverted) > $availableQty) {
            \DB::rollBack();

            $availableToReturn = $availableQty - $alreadyReturned;
              return response()->json([
                    'error'               => true,
                    'message'             => "لقد قمت بإرجاع {$alreadyReturned} من المنتج {$oldDetail->product->name}. المتاح للإرجاع هو {$availableToReturn}",
                    'available_to_return' => $availableToReturn
                ], 422);
        }
    }
}


        // 8. Prepare aggregation variables
        $productsReturnData       = [];
        $totalReturnPrice         = 0;
        $totalReturnDiscount      = 0;
        $totalReturnExtraDiscount = 0;
        $totalReturnTax           = 0;
        $totalReturnOverall       = 0;
        $totalPriceAllProducts    = 0;

        // 9. Process each detail for return
        foreach ($orderProducts as $detail) {
            $pid            = $detail->product_id;
            $productInfo    = json_decode($detail->product_details);
            $unitValue      = $productInfo->unit_value ?? 1;
            $returnQuantity = (float) ($returnQuantities[$pid] ?? 0);
            if ($returnQuantity <= 0) continue;

            // 9a. Adjust price components by unit
     
                $price            = $detail->price;
                $discount         = $detail->discount_on_product;
                $extraDiscPerUnit = ($discountRatio / 100) * $detail->price;
                $tax              = $detail->tax_amount;
            

            $finalUnitPrice = $price - $discount - $extraDiscPerUnit + $tax;
            $productsReturnData[] = compact(
                'pid', 'price', 'discount', 'extraDiscPerUnit', 'tax', 'returnQuantity'
            );

            // 9b. Update aggregates
            $totalReturnPrice         += $returnQuantity * $price;
            $totalReturnDiscount      += $returnQuantity * $discount;
            $totalReturnExtraDiscount += $returnQuantity * $extraDiscPerUnit;
            $totalReturnTax           += $returnQuantity * $tax;
            $totalReturnOverall       += $returnQuantity * $finalUnitPrice;

            // 9c. Handle stock and product logs
            $baseQty =$returnQuantity;
            $totalPriceAllProducts += $baseQty * $detail->product->purchase_price;
    $stock = Stock::where('seller_id', Auth::id())
                  ->where('product_id', $pid)
                  ->first();

    
            if (!$stock ) {
    // Retrieve the store associated with the seller
    $store = Store::where('seller_id', Auth::id())->first();

    if ($store) {
        // Create a new stock entry for the product
        $stock = Stock::create([
            'seller_id' => Auth::id(),
            'store_id' => $store->id,
            'product_id' => $pid,
            'main_stock' => $baseQty,
            'stock' => $baseQty,
        ]);
    } else {
        // Return error response if no store is found for the seller
        return response()->json(['error' => 'No store found for the seller'], 404);
    }
}elseif($stock){
    $stock->stock=$stock->stock + $baseQty;
         $stock->save();
   
}
            
        }

        // 10. Create new Order for return
        $newOrder = new \App\Models\Order();
        $newOrder->owner_id             = auth()->id();
        $newOrder->user_id              = $oldOrder->user_id;
        $newOrder->parent_id            = $oldOrder->id;
        $newOrder->cash                 = 2;
        $newOrder->type                 = $returnType;
        $newOrder->total_tax            = $totalReturnTax;
        $newOrder->order_amount         = $totalReturnOverall;
        $newOrder->extra_discount       = $totalReturnExtraDiscount;
        $newOrder->coupon_discount_amount = 0;
        $newOrder->collected_cash       = 0;
        $newOrder->transaction_reference = 0;
        $newOrder->save();

        // 11. Create CurrentOrder record
        $newCurrentOrder = new \App\Models\CurrentOrder();
        $newCurrentOrder->id=$newOrder->id;
        $newCurrentOrder->owner_id             = auth()->id();
        $newCurrentOrder->user_id              = $oldOrder->user_id;
        $newCurrentOrder->cash                 = 2;
        $newCurrentOrder->type                 = $returnType;
        $newCurrentOrder->total_tax            = $totalReturnTax;
        $newCurrentOrder->order_amount         = $totalReturnOverall;
        $newCurrentOrder->extra_discount       = $totalReturnExtraDiscount;
        $newCurrentOrder->coupon_discount_amount = 0;
        $newCurrentOrder->collected_cash       = 0;
        $newCurrentOrder->transaction_reference = 0;
        $newCurrentOrder->save();

        // 12. Accounting: customer payable
        $customer       = \App\Models\Customer::findOrFail($oldOrder->user_id);
       
        // 15. Update customer balance

        $newOrder->save();

        // Commit transaction
        \DB::commit();
        $customer = $this->customer->find($oldOrder->user_id);
     $account = $this->account->find($request->payment_id);
        $transaction_data = [
            'tran_type' => 7,
            'seller_id' => Auth::id(),
            'amount' => $totalReturnOverall,
            'debit' => $totalReturnOverall,
            'credit' => 0,
            'balance' =>$customer->credit-$totalReturnOverall- $customer->balance-$customer->discount,

            'cash' => 2,
            'description' => 'مرتجع مبيعات',
            'date' => now(),
            // 'balance' => $account->balance + $request->collected_cash,
            'customer_id' => $oldOrder->user_id,
            'order_id' => $newOrder->id,
            'img' => '',
        ];
    // Create the transaction record
    $this->transection->create($transaction_data);
    
        $customer->balance += $totalReturnOverall;
        $customer->save();

        // 17. Save return order details
        foreach ($productsReturnData as $pd) {
            $detail        = new \App\Models\OrderDetail();
            $detail->order_id            = $newOrder->id;
            $detail->product_id          = $pd['pid'];
            $detail->product_details     = json_encode($pd);
            $detail->quantity            = $pd['returnQuantity'];
            $detail->price               = $pd['price'];
            $detail->tax_amount          = $pd['tax'];
            $detail->discount_on_product = $pd['discount'];
            $detail->discount_type       = 'discount_on_product';
            $detail->save();
        }

        // 18. Update original details' returned_quantity
        foreach ($productsReturnData as $pd) {
            $pid         = $pd['pid'];
            $returnQty   = $pd['returnQuantity'];

            $oldDetail = $oldOrder->details->firstWhere('product_id', $pid);
            if ($oldDetail) {
                $info      = json_decode($oldDetail->product_details);
                $unitValue = $info->unit_value ?? 1;
                $returnInBase = $returnQty;

                $oldDetail->quantity_returned = ($oldDetail->quantity_returned ?? 0) + $returnInBase;
                $oldDetail->save();
            }
        }

        // 19. Return success JSON
        return response()->json([
            'success'      => true,
            'message'      => 'تم تنفيذ المرتجع بنجاح',
            'return_order' => $newOrder->load('details'),
            'totals'       => [
                'price'         => $totalReturnPrice,
                'discount'      => $totalReturnDiscount,
                'extraDiscount' => $totalReturnExtraDiscount,
                'tax'           => $totalReturnTax,
                'overall'       => $totalReturnOverall,
            ],
        ], 200);

    } catch (\Exception $e) {
        \DB::rollBack();
        return response()->json([
            'error'   => true,
            'message' => $e->getMessage(),
        ], 500);
    }
}
    public function reservationsInvoice(Request $request, $id)
{
    $reserveProducts =ReserveProduct::
        where('seller_id', Auth::id())->where('id',$id)
        ->with(['customer', 'seller']) // جلب البيانات بالعلاقات لتقليل الاستعلامات
        ->get();
$seller=Seller::where('id', Auth::id())->first();
    $reserveProducts->transform(function ($item) {
        $item->data = json_decode($item->data);
        
        // تحسين عرض بيانات البائع
    

        // إضافة product_code لكل منتج في البيانات
        foreach ($item->data as $value) {
            $product = Product::find($value->product_id);
            if ($product) {
                $value->product_code = $product->product_code??'';
            }
        }

        return $item;
    });

    return response()->json([
        'total' => $reserveProducts->count(),
        'reservations' => $reserveProducts,
        'seller'=>$seller
    ], 200);
}
}
