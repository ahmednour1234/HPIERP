<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\CurrentOrder;
use App\Models\Coupon;
use App\Models\Transection;
use App\Models\Account;
use App\Models\OrderDetail;
use App\Models\Region;
use App\Models\Customer;
use App\Models\HistoryInstallment;
use App\Models\Installment;
use App\Models\HistoryTransection;
use App\Models\ReserveProduct;
use App\Models\CurrentReserveProduct;
use App\Models\ReserveProductNotification;
use App\Models\StockOrder;
use App\Models\Seller;
use App\Models\Stock;
use App\Models\SellerPrice;
use App\Models\AdminSeller;
use App\Models\CustomerPrice;
use App\Models\Transaction;
use App\CPU\Helpers;
use Brian2694\Toastr\Facades\Toastr;
use function App\CPU\translate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class POSController extends Controller
{
    use \App\Traits\ExportsCsv;

    public function __construct(
        private Category $category,
        private Product $product,
        private Order $order,
        private Region $regions,
                private Purchase $purchase,
        private CurrentOrder $c_order,
        private Coupon $coupon,
        private Transection $transection,
        private Account $account,
        private OrderDetail $order_details,
        private StockOrder $stock_order,
        private Customer $customer,
        private CurrentReserveProduct $current_reserve_products,
        private HistoryInstallment $installment,
        private ReserveProduct $reserveProduct,
        private HistoryTransection $history_transection,
        private ReserveProductNotification $reserveProductNotification,
    ){}

    /**
     * @param Request $request
     * @return Application|Factory|View
     */
    public function index($type,Request $request): Factory|View|Application
    {
        $category = $request->query('category_id', 0);
        $keyword = $request->query('search', false);
        $key = explode(' ', $keyword);
        $categories = $this->category->where('status', 1)->where('position', 0)->where('type',1)->latest()->get();

        $products = $this->product->where('quantity', '>', 0)->active()
            ->when($request->has('category_id') && $request['category_id'] != 0, function ($query) use ($request) {
                $query->whereJsonContains('category_id', [['id' => (string)$request['category_id']]]);
            })->latest()->paginate(Helpers::pagination_limit());

        $cart_id = 'wc-' . rand(10, 1000);

        if (!session()->has('current_user')) {
            session()->put('current_user', $cart_id);
        }
        if (strpos(session('current_user'), 'wc')) {
            $user_id = 0;
        } else {
            $user_id = explode('-', session('current_user'))[1];
        }

        if (!session()->has('cart_name')) {
            if (!in_array($cart_id, session('cart_name') ?? [])) {
                session()->push('cart_name', $cart_id);
            }
        }

        return view('admin-views.pos.index', compact('categories', 'products', 'cart_id', 'category','user_id','type'));
    }

    /**
     * @return RedirectResponse
     */
    public function clear_cart_ids(): RedirectResponse
    {
        session()->forget('cart_name');
      session()->forget(session('current_user'));
        session()->forget('current_user');

return redirect()->route('admin.pos.index', ['type' =>4]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function quick_view(Request $request): JsonResponse
    {
        $product = $this->product->findOrFail($request->product_id);

        return response()->json([
            'success' => 1,
            'view' => view('admin-views.pos._quick-view-data', compact('product'))->render(),
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
  public function addToCart(Request $request,$type): JsonResponse
{
    $cart_id = session('current_user');
    $user_id = 0;
    $user_type = 'wc';
    if (Str::contains(session('current_user'), 'sc')) {
        $user_id = explode('-', session('current_user'))[1];
        $user_type = 'sc';
    }

    // Retrieve the product
    $product = $this->product->find($request->id);
    
    // Check the user type to determine the selling price
    switch ($user_type) {
        case '1':
            $selling_price = $product->selling_price;  // Replace with the appropriate price logic
            break;
        case '2':
            $selling_price = $product->selling_price1; // Assume these attributes exist on the Product model
            break;
        case '3':
            $selling_price = $product->selling_price2; 
            break;
        case '4':
            $selling_price = $product->selling_price3; 
            break;
        case '5':
            $selling_price = $product->selling_price4; 
            break;
        default:
            $selling_price = $product->selling_price; // Fallback to default
            break;
    }

    $cart = session($cart_id);
    
    // Check existing cart
    if (session()->has($cart_id) && count($cart) > 0) {
        foreach ($cart as $key => $cartItem) {
            if (is_array($cartItem) && $cartItem['id'] == $request['id']) {
                $qty = $product->quantity - $cartItem['quantity'];
                if ($qty == 0) {
                    return response()->json([
                        'qty' => $qty,
                        'user_type' => $user_type,
                        'user_id' => $user_id,
                        'view' => view('admin-views.pos._cart', compact('cart_id'))->render()
                    ]);
                }
            }
        }
    }

    // Initialize data for the cart
    $data = array();
    $data['id'] = $product->id;
    $cart_keeper = [];
    $item_exist = 0;

    if (session()->has($cart_id) && count($cart) > 0) {
        foreach ($cart as $key => $cartItem) {
            if (is_array($cartItem) && $cartItem['id'] == $request['id']) {
                $cartItem['quantity'] += 1;
                $item_exist = 1;
            }
            array_push($cart_keeper, $cartItem);
        }
    }
    
    session()->put($cart_id, $cart_keeper);

    if ($item_exist == 0) {
        $data['quantity'] = $request['quantity'];
        $data['price'] = $selling_price; // Use the selling price based on user type
        $data['name'] = $product->name;
        $data['discount'] = Helpers::discount_calculate($product, $selling_price); // Updated to use dynamic price
        $taxafter=$selling_price-  $data['discount'] ;
        $data['image'] = $product->image;
        $data['tax'] = Helpers::tax_calculate($product, $taxafter); // Updated to use dynamic price
        
        if ($request->session()->has($cart_id)) {
            $keeper = [];
            foreach (session($cart_id) as $item) {
                array_push($keeper, $item);
            }
            $keeper[] = $data;
            $request->session()->put($cart_id, $keeper);
        } else {
            $request->session()->put($cart_id, [$data]);
        }
    }

    return response()->json([
        'user_type' => $user_type,
        'user_id' => $user_id,
        'type'=>$type,
        'view' => view('admin-views.pos._cart', compact('cart_id'))->render()
    ]);
}


    /**
     * @return Application|Factory|View
     */
    public function cart_items(): Factory|View|Application
    {
        return view('admin-views.pos._cart');
    }
public function reserveProduct(Request $request)
{
    // Validate if 'data' exists and is not empty
    if (!$request->has('data') || empty($request->data)) {
        return response()->json(['message' => 'Data empty'], 403);
    }

    // Initialize variables
    $data = [];
    foreach ($request->data as $i => $item) {
        $data[$i]['product_name'] = $item['product_name'];
        $data[$i]['product_id'] = $item['product_id'];
        $data[$i]['stock'] = $item['stock'];
        $data[$i]['balance'] = $item['balance'];
    }

    // Generate a unique order ID
    $order_id = 20000000 + ReserveProduct::count() + 1;
    if (ReserveProduct::find($order_id)) {
        $order_id = ReserveProduct::orderBy('id', 'DESC')->first()->id + 1;
    }

    // Save current reservation products
    $current_reserve_products = new CurrentReserveProduct;
    $current_reserve_products->id = $order_id;
    $current_reserve_products->data = json_encode($data);
    $current_reserve_products->seller_id = $request->seller_id;
    $current_reserve_products->date = date('Y-m-d');
    $current_reserve_products->customer_id = $request->customer_id;
    $current_reserve_products->type = $request->type;
    $current_reserve_products->save();

    // Save reserve product details
    $reserveProduct = new ReserveProduct;
    $reserveProduct->id = $order_id;
    $reserveProduct->data = json_encode($data);
    $reserveProduct->seller_id = $request->seller_id;
    $reserveProduct->date = date('Y-m-d');
    $reserveProduct->customer_id = $request->customer_id;
    $reserveProduct->type = $request->type;
    $reserveProduct->save();

    // Update reserve_product_notifications table
    $notification = ReserveProductNotification::find($request->notification_id);
    if ($notification) {
        $notification->active = 1;
        $notification->save();
    }
 $cart = [];
    foreach ($request->data as $i => $item) {
        $cart[$i]['product_name'] = $item['product_name'];
        $cart[$i]['product_id'] = $item['product_id'];
        $cart[$i]['stock'] = $item['stock'];
        $cart[$i]['balance'] = $item['balance'];
    }


    // Initialize variables for order processing
    $user_id = $request->customer_id;
    $coupon_discount = $request->coupon_discount ?? 0;
    $order_details = [];
    $product_price = 0;
    $product_discount = 0;
    $product_tax = 0;

    // Generate unique order ID for the order
    $order_id = Order::count() + 1;
    if (Order::find($order_id)) {
        $order_id = Order::orderBy('id', 'DESC')->first()->id + 1;
    }

    // Create new order
    $order = new Order;
    $order->id = $order_id;
    $order->user_id = $user_id;
        $order->owner_id = $request->seller_id;
    $order->coupon_code = $request->cart['coupon_code'] ?? null;
    $order->coupon_discount_title = $request->cart['coupon_title'] ?? null;
    $order->payment_id = $request->type;
    $order->total_tax = $request->type;
    $order->created_at = now();
    $order->updated_at = now();

    foreach ($cart as $c) {
        if (is_array($c)) {
            $product = Product::find($c['product_id']);
            $seller_price = $product->selling_price;
            if ($product) {
                $stock = Stock::where('seller_id', $request->seller_id)->where('product_id', $c['product_id'])->first();
                if ($stock) {
                    if ($request->order_type == 4) { // e.g., sale
                        if ($stock->stock >= $c['stock']) {
                            $stock->stock -= $c['stock'];
                        } else {
                            continue;
                        }
                    } else { // e.g., restock
                        $stock->stock += $c['stock'];
                        $stock->main_stock += $c['stock'];
                    }
                    $stock->update();
                } else {
                    if ($request->order_type == 7) { // e.g., initial stock
                        $exist_stock = Stock::where('seller_id', $request->seller_id)->where('product_id', $c['id'])->first();
                        if ($exist_stock) {
                            $exist_stock->main_stock += $c['stock'];
                            $exist_stock->stock += $c['stock'];
                            $exist_stock->update();
                        } else {
                            $new_stock = new Stock;
                            $new_stock->seller_id = $request->seller_id;
                            $new_stock->product_id = $c['id'];
                            $new_stock->main_stock = $c['stock'];
                            $new_stock->stock = $c['stock'];
                            $new_stock->save();
                        }
                    } else {
                        continue;
                    }
                }

                $price =$seller_price;
                $customerPrice = CustomerPrice::where('product_id', $product->id)->where('customer_id', $user_id)->first();
                $order_details[] = [
                    'order_id' => $order_id,
                    'product_id' => $c['product_id'],
                    'product_details' => $product,
                    'quantity' => $c['stock'],
                    'price' => $price,
                    'tax_amount' => Helpers::tax_calculate($product,  $price),
                    'discount_on_product' => Helpers::discount_calculate($product, $product->selling_price),
                    'discount_type' => 'discount_on_product',
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                $product_price += $price * $c['stock'];
                $product_discount += $c['stock'];
                $product_tax += $product->tax * $c['stock'];

                if ($c['stock'] > $product->quantity) {
                    return redirect()->back()->with('Check On Quantity Product');
                }

                $product->order_count++;
                $product->save();
            }
        }
    }

    $total_price = $product_price;
    $ext_discount = $request->ext_discount_type == 'percent' ? ($product_price * $request->extra_discount) / 100 : $request->extra_discount;
    $total_tax_amount = $request->total_tax;
    $grand_total = $total_price + $total_tax_amount - $ext_discount - $coupon_discount;

    $order->total_tax = $product_tax;
    $order->order_amount = $total_price;
    $order->coupon_discount_amount = $coupon_discount;
    $order->collected_cash = $request->collected_cash ?? $grand_total;
    $order->extra_discount = $ext_discount;
    $order->type = $request->type;
    $order->save();

    foreach ($order_details as $detail) {
        $orderDetail = new OrderDetail($detail);
        $orderDetail->save();
    }

    // Real-time transactions
    $account = Account::find(1);
  

    return redirect()->back()->with('message', 'Order stored successfully');
}



    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function emptyCart(Request $request): JsonResponse
    {
        $cart_id = session('current_user');
        $user_id = 0;
        $user_type = 'wc';
        if (Str::contains(session('current_user'), 'sc')) {
            $user_id = explode('-', session('current_user'))[1];
            $user_type = 'sc';
        }
        session()->forget($cart_id);
        return response()->json([
            'user_type' => $user_type,
            'user_id' => $user_id,
            'view' => view('admin-views.pos._cart', compact('cart_id'))->render()
        ], 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updateQuantity(Request $request): JsonResponse
    {
        $cart_id = session('current_user');
        $user_id = 0;
        $user_type = 'wc';
        if (Str::contains(session('current_user'), 'sc')) {
            $user_id = explode('-', session('current_user'))[1];
            $user_type = 'sc';
        }
        if ($request->quantity > 0) {

            $product = $this->product->find($request->key);
            $cart = session($cart_id);
            $keeper = [];
            foreach ($cart as $item) {
                if (is_array($item)) {
                    if ($item['id'] == $request->key) {
                        $qty = $product->quantity - $request->quantity;
                        if ($qty < 0) {
                            return response()->json([
                                'qty' => $qty,
                                'user_type' => $user_type,
                                'user_id' => $user_id,
                                'view' => view('admin-views.pos._cart', compact('cart_id'))->render()
                            ]);
                        }
                        $item['quantity'] = $request->quantity;
                    }
                    $keeper[] = $item;
                }
            }
            session()->put($cart_id, $keeper);

            return response()->json([
                'user_type' => $user_type,
                'user_id' => $user_id,
                'view' => view('admin-views.pos._cart', compact('cart_id'))->render()
            ], 200);
        } else {
            return response()->json([
                'upQty' => 'zeroNegative',
                'user_type' => $user_type,
                'user_id' => $user_id,
                'view' => view('admin-views.pos._cart', compact('cart_id'))->render()
            ]);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function removeFromCart(Request $request): JsonResponse
    {
        $cart_id = session('current_user');
        $user_id = 0;
        $user_type = 'wc';
        if (Str::contains(session('current_user'), 'sc')) {
            $user_id = explode('-', session('current_user'))[1];
            $user_type = 'sc';
        }
        $cart = session($cart_id);
        $cart_keeper = [];
        if (session()->has($cart_id) && count($cart) > 0) {
            foreach ($cart as $cartItem) {
                if (is_array($cartItem) && $cartItem['id'] != $request['key']) {
                    array_push($cart_keeper, $cartItem);
                }
            }
        }
        session()->put($cart_id, $cart_keeper);

        return response()->json([
            'user_type' => $user_type,
            'user_id' => $user_id,
            'view' => view('admin-views.pos._cart', compact('cart_id'))->render()
        ], 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function update_discount(Request $request): JsonResponse
    {
        $cart_id = session('current_user');
        $user_id = 0;
        $user_type = 'wc';
        if (Str::contains(session('current_user'), 'sc')) {
            $user_id = explode('-', session('current_user'))[1];
            $user_type = 'sc';
        }
        $cart = session($cart_id, collect([]));
        if ($cart != null) {
            $total_product_price = 0;
            $product_discount = 0;
            $product_tax = 0;
            $ext_discount = 0;
            $coupon_discount = $cart['coupon_discount'] ?? 0;
            foreach ($cart as $ct) {
                if (is_array($ct)) {
                    $total_product_price += $ct['price'] * $ct['quantity'];
                    $product_discount += $ct['discount'] * $ct['quantity'];
                    $product_tax += $ct['tax'] * $ct['quantity'];
                }
            }
            $price_discount = 0;
            if ($request->type == 'percent') {
                $price_discount = ($total_product_price / 100) * $request->discount;
            } else {
                $price_discount = $request->discount;
            }
            $ext_discount = $price_discount;
            $total = $total_product_price - $product_discount + $product_tax - $coupon_discount - $ext_discount;

            if ($total < 0) {
                return response()->json([
                    'extra_discount' => "amount_low",
                    'user_type' => $user_type,
                    'user_id' => $user_id,
                    'view' => view('admin-views.pos._cart', compact('cart_id'))->render()
                ]);
            } else {
                $cart['ext_discount'] = $request->discount;
                $cart['ext_discount_type'] = $request->type;
                session()->put($cart_id, $cart);

                return response()->json([
                    'extra_discount' => "success",
                    'user_type' => $user_type,
                    'user_id' => $user_id,
                    'view' => view('admin-views.pos._cart', compact('cart_id'))->render()
                ]);
            }
        } else {
            return response()->json([
                'extra_discount' => "empty",
                'user_type' => $user_type,
                'user_id' => $user_id,
                'view' => view('admin-views.pos._cart', compact('cart_id'))->render()
            ]);
        }
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function update_tax(Request $request): RedirectResponse
    {
        $cart = $request->session()->get('cart', collect([]));
        $cart['tax'] = $request->tax;
        $request->session()->put('cart', $cart);
        return back();
    }

    /**
     * @param $cart
     * @param $price
     * @return float|int
     */
    public function extra_dis_calculate($cart, $price): float|int
    {

        if ($cart['ext_discount_type'] == 'percent') {
            $price_discount = ($price / 100) * $cart['ext_discount'];
        } else {
            $price_discount = $cart['ext_discount'];
        }
        return $price_discount;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function coupon_discount(Request $request): JsonResponse
    {
        $cart_id = session('current_user');
        $user_id = 0;
        $user_type = 'wc';
        if (Str::contains(session('current_user'), 'sc')) {
            $user_id = explode('-', session('current_user'))[1];
            $user_type = 'sc';
        }
        if ($user_id != 0) {
            $couponLimit = $this->order->where('user_id', $user_id)
                ->where('coupon_code', $request['coupon_code'])->count();

            $coupon = $this->coupon->where(['code' => $request['coupon_code']])
                ->where('user_limit', '>', $couponLimit)
                ->where('status', '=', 1)
                ->whereDate('start_date', '<=', now())
                ->whereDate('expire_date', '>=', now())->first();
        } else {
            $coupon = $this->coupon->where(['code' => $request['coupon_code']])
                ->where('status', '=', 1)
                ->whereDate('start_date', '<=', now())
                ->whereDate('expire_date', '>=', now())->first();
        }

        $carts = session($cart_id);
        $total_product_price = 0;
        $product_discount = 0;
        $product_tax = 0;
        $ext_discount = 0;

        if ($coupon != null) {
            if ($carts != null) {
                foreach ($carts as $cart) {
                    if (is_array($cart)) {
                        $total_product_price += $cart['price'] * $cart['quantity'];
                        $product_discount += $cart['discount'] * $cart['quantity'];
                        $product_tax += $cart['tax'] * $cart['quantity'];
                    }
                }
                if ($total_product_price >= $coupon['min_purchase']) {
                    if ($coupon['discount_type'] == 'percent') {

                        $discount = (($total_product_price / 100) * $coupon['discount']) > $coupon['max_discount'] ? $coupon['max_discount'] : (($total_product_price / 100) * $coupon['discount']);
                    } else {
                        $discount = $coupon['discount'];
                    }
                    if (isset($carts['ext_discount_type'])) {
                        $ext_discount = $this->extra_dis_calculate($carts, $total_product_price);
                    }
                    $total = $total_product_price - $product_discount + $product_tax - $discount - $ext_discount;
                    if ($total < 0) {
                        return response()->json([
                            'coupon' => "amount_low",
                            'user_type' => $user_type,
                            'user_id' => $user_id,
                            'view' => view('admin-views.pos._cart', compact('cart_id'))->render()
                        ]);
                    }

                    $cart = session($cart_id, collect([]));
                    $cart['coupon_code'] = $request['coupon_code'];
                    $cart['coupon_discount'] = $discount;
                    $cart['coupon_title'] = $coupon->title;
                    $request->session()->put($cart_id, $cart);

                    return response()->json([
                        'coupon' => 'success',
                        'user_type' => $user_type,
                        'user_id' => $user_id,
                        'view' => view('admin-views.pos._cart', compact('cart_id'))->render()
                    ]);
                }
            } else {
                return response()->json([
                    'coupon' => 'cart_empty',
                    'user_type' => $user_type,
                    'user_id' => $user_id,
                    'view' => view('admin-views.pos._cart', compact('cart_id'))->render()
                ]);
            }

            return response()->json([
                'coupon' => 'coupon_invalid',
                'user_type' => $user_type,
                'user_id' => $user_id,
                'view' => view('admin-views.pos._cart', compact('cart_id'))->render()
            ]);
        }

        return response()->json([
            'coupon' => 'coupon_invalid',
            'user_type' => $user_type,
            'user_id' => $user_id,
            'view' => view('admin-views.pos._cart', compact('cart_id'))->render()
        ]);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
public function place_order(Request $request): RedirectResponse
{
    // Retrieve the cart ID and determine user type (wc or sc)
    $cart_id = session('current_user');
    
    // Retrieve type from the route
    $type = $request->type;
    // dd($request->type);

    $user_type = 'wc';
    $user_id = 0;
// dd($request->type);
    if (Str::contains(session('current_user'), 'sc')) {
        $user_id = explode('-', session('current_user'))[1];
        $user_type = 'sc';
    }

    // Check if the cart is empty
    if (!session($cart_id) || count(session($cart_id)) < 1) {
        Toastr::error(translate('cart_empty_warning'));
        return back();
    }

    // Retrieve cart and order information
    $cart = session($cart_id);
    $coupon_code = 0;
    $product_price = 0;
    $order_details = [];
    $product_discount = 0;
    $product_tax = 0;
    $ext_discount = 0;
    $coupon_discount = $cart['coupon_discount'] ?? 0;

    // Generate unique order ID
    $order_id = 100000 + $this->order->all()->count() + 1;
    if ($this->order->find($order_id)) {
        $order_id = $this->order->orderBy('id', 'DESC')->first()->id + 1;
    }

    // Image upload logic
    $img = null;
   if ($request->hasFile('img')) {
    // Get the uploaded file
    $file = $request->file('img');

    // Store the file in the 'shop/' directory
    $path = $file->store('shop', 'public'); // 'public' disk for public accessibility

    // Optionally, save the path to the database
    $img = $path; // This will be something like 'shop/filename.png'
}


    // Create a new order
    $order = $this->order;
    $order->id = $order_id;
    $order->user_id = $user_id;
    $order->coupon_code = $cart['coupon_code'] ?? null;
    $order->coupon_discount_title = $cart['coupon_title'] ?? null;
    $order->payment_id = $request->payment_id;
    $order->type = $request->type;
    $order->cash = $request->cash;
    $order->owner_id = auth('admin')->user()->id; // Get the authenticated admin's ID
    $order->transaction_reference = $request->transaction_reference ?? 0;
    $order->created_at = now();
    $order->updated_at = now();
    $order->img = $img;

    // Process each cart item and update product stock
    foreach ($cart as $c) {

        if (is_array($c)) {

            $product = $this->product->find($c['id']);
            if ($product) {

                $price = $c['price'];
                                    $discount_on_product = Helpers::discount_calculate($product, $product->selling_price);
                                    $taxafter=$product->selling_price-$discount_on_product;
                $or_d = [
                    'order_id' => $order->id, // Use the saved order ID
                    'product_id' => $c['id'],
                    'product_details' => $product,
                    'quantity' => $c['quantity'],
                    'price' => $product->selling_price,
                    'tax_amount' => Helpers::tax_calculate($product, $product->selling_price),
                    'discount_on_product' => Helpers::discount_calculate($product,$taxafter),
                    'discount_type' => 'discount_on_product',
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                $product_price += $price * $c['quantity'];
                $product_discount += $c['discount'] * $c['quantity'];
                $product_tax += $c['tax'] * $c['quantity'];
                $order_details[] = $or_d;

                // Update product stock
             if ($type == 4 || $type == 24 || $type == 12) {
    // Reduce stock quantity if this is a sale transaction
    if ($product->quantity >= $c['quantity']) {
        $product->quantity -= $c['quantity'];
    } else {
        // Return an error response if there is insufficient stock
return redirect()->back()->with('error', 'Insufficient stock');
    }
} else {
    // Increase stock quantity for other transaction types (e.g., restock or return)
    $product->quantity += $c['quantity'];
}


                $product->order_count++;
                $product->save();
            }
        }
    }

    // Calculate total price and taxes
    $total_price = $product_price - $product_discount;
    if (isset($cart['ext_discount_type'])) {
        $ext_discount = $this->extra_dis_calculate($cart, $product_price);
        $order->extra_discount = $ext_discount;
    }

    $total_tax_amount = $product_tax;
    $grand_total = $total_price + $total_tax_amount - $ext_discount - $coupon_discount;
    try {
        // Special handling for type 12 and 24: skip transactions and set total order to 0
        if (in_array($type, [12, 24])) {
            // dd($request->payment_id);
            $order->total_tax = $total_tax_amount;
            $order->order_amount = $grand_total;
            $order->coupon_discount_amount = 0;
            $order->collected_cash = 0;
            $order->transaction_reference = 0;
            $order->type = $request->type;
            $order->payment_id = $request->payment_id;
            $order->save();
                    $this->order_details->insert($order_details);

        } else {
            // Handle credit sale for type 4
            if ($type == 4) {
                if ($request->cash == 2) {
                    // Credit sale logic
                    $remaining_balance = $grand_total - $request->transaction_reference;
                    $customer = $this->customer->where('id', $user_id)->first();
                    $customer->credit += $remaining_balance;
                    $customer->save();

                    // Transaction entry for sales
                    $payable_account = Account::find($request->payment_id);
                    $payable_transaction = new Transection;
                    $payable_transaction->tran_type =$request->type;
                    $payable_transaction->seller_id = auth('admin')->user()->id;
                    $payable_transaction->account_id = $request->payment_id;
                    $payable_transaction->amount = $request->transaction_reference;
                    $payable_transaction->description = 'فاتورة مبيعات';
                    $payable_transaction->debit = $customer->balance;
                    $payable_transaction->credit = $customer->credit;
                    $payable_transaction->balance = $payable_account->balance + $request->collected_cash;
                    $payable_transaction->date = date("Y/m/d");
                    $payable_transaction->customer_id = $user_id;
                    $payable_transaction->order_id = $order_id;
                    $payable_transaction->img = $img;
                    $payable_transaction->save();
  $payable_account->total_in += $request->transaction_reference;
    $payable_account->balance += $request->transaction_reference; // Update balance as well
    $payable_account->save();
                    // Finalize and save the order
                    $order->total_tax = $total_tax_amount;
                    $order->order_amount = $grand_total;
                    $order->coupon_discount_amount = $coupon_discount;
                    $order->collected_cash = $request->transaction_reference;
                    $order->transaction_reference = $request->transaction_reference;
                    $order->type = $request->type;    
                    $order->save();
                    $this->order_details->insert($order_details);

                } else {
                    // Cash sale logic
                    $payable_account = Account::find($request->payment_id);
                    $payable_transaction = new Transection;
                    $payable_transaction->tran_type = $request->type; // Sales
                    $payable_transaction->seller_id = auth('admin')->user()->id;
                    $payable_transaction->account_id = $request->payment_id;
                    $payable_transaction->amount = $request->collected_cash;
                    $payable_transaction->description = 'فاتورة مبيعات';
                    $payable_transaction->balance = $payable_account->balance + $request->collected_cash;
                    $payable_transaction->date = date("Y/m/d");
                    $payable_transaction->customer_id = $user_id;
                    $payable_transaction->order_id = $order_id;
                    $payable_transaction->img = $img;
                    $payable_transaction->save();

                    $payable_account->balance += $grand_total;
                    $payable_account->total_in += $request->collected_cash;
                    $payable_account->save();

                    $order->total_tax = $total_tax_amount;
                    $order->order_amount = $grand_total;
                    $order->coupon_discount_amount = $coupon_discount;
                    $order->collected_cash = $request->collected_cash;
                    $order->transaction_reference = $request->collected_cash;
                    $order->type = $request->type;
                    $order->save();
                            $this->order_details->insert($order_details);

                                        // dd($order);

                }
            } elseif($type == 7) {
                // Handling other types (e.g., returns)
                if ($request->cash == 2) {
                    $remaining_balance = $grand_total - $request->transaction_reference;
                    $customer = $this->customer->where('id', $user_id)->first();
                    $customer->balance += $remaining_balance;
                    $customer->save();

                    $payable_account = Account::find($request->payment_id);
                    $payable_transaction = new Transection;
                    $payable_transaction->tran_type = $request->type; // Return sales
                    $payable_transaction->seller_id = auth('admin')->user()->id;
                    $payable_transaction->account_id = $request->payment_id;
                    $payable_transaction->amount = $request->transaction_reference;
                    $payable_transaction->description = 'مردود مبيعات';
                    $payable_transaction->date = date("Y/m/d");
                    $payable_transaction->customer_id = $user_id;
                    $payable_transaction->order_id = $order_id;
                    $payable_transaction->img = $img;
                    $payable_transaction->save();
                           $payable_account->balance -= $remaining_balance;
                    $payable_account->total_out += $request->transaction_reference;
                    $payable_account->save();

                    $order->total_tax = $total_tax_amount;
                    $order->order_amount = $grand_total;
                    $order->coupon_discount_amount = $coupon_discount;
                    $order->collected_cash = $request->transaction_reference;
                    $order->transaction_reference = $request->transaction_reference;
                    $order->type = $request->type;
                    $order->save();
                            $this->order_details->insert($order_details);

                } else {
                    // Cash return logic

                    $payable_account = Account::find($request->payment_id);
                    $payable_transaction = new Transection;
                    $payable_transaction->tran_type = $request->type; // Return sales
                    $payable_transaction->seller_id = auth('admin')->user()->id;
                    $payable_transaction->account_id = $request->payment_id;
                    $payable_transaction->amount = $request->collected_cash;
                    $payable_transaction->description = 'مردود مبيعات';
                    $payable_transaction->balance = $payable_account->balance - $request->collected_cash;
                    $payable_transaction->date = date("Y/m/d");
                    $payable_transaction->customer_id = $user_id;
                    $payable_transaction->order_id = $order_id;
                    $payable_transaction->img = $img;
                    $payable_transaction->save();
                    $payable_account->total_out += $request->collected_cash;
                           $payable_account->balance -= $request->collected_cash;

                    $payable_account->save();

                    $order->total_tax = $total_tax_amount;
                    $order->order_amount = $grand_total;
                    $order->coupon_discount_amount = $coupon_discount;
                    $order->collected_cash = $request->collected_cash;
                $order->transaction_reference = $request->collected_cash;
                    $order->save();
                            $this->order_details->insert($order_details);
                }
            }
        }
    } catch (\Exception $e) {
        DB::rollBack();

        // كان هنا dd() يوقف الطلب ويعرض تفريغًا خامًا للمستخدم، فلا تصل
        // الرسالة ولا تحدث العودة. التسجيل في اللوج هو مكان التفاصيل.
        \Illuminate\Support\Facades\Log::error('POS order failed: ' . $e->getMessage(), [
            'exception' => $e,
        ]);

        Toastr::error(translate('order_failed_warning') . ' ' . $e->getMessage());
        return back();
    }
        // Insert order details
    // Finalize the order and clear the cart session
    session()->forget($cart_id);
    Toastr::success(translate('order_placed_successfully'));
    return redirect()->back();
}

public function storeplaceorder(Request $request): RedirectResponse
{
    // Decode cart JSON
    $request->cart = json_decode($request->cart, true);

    // Validate input
    $request->validate([
        'cart' => 'required',
        'cart.*.id' => 'required|integer|exists:products,id',
        'cart.*.quantity' => 'required|integer|min:1',
        'type' => 'required|string|in:4,7', // Assuming type is either '4' or '7'
        'owner_id' => 'required|integer',
    ]);

    if (empty($request->cart)) {
        return redirect()->back()->with([
            'message' => 'The cart cannot be empty.',
        ])->withInput();
    }

    // Initialize success flag
    $success = false;

    DB::beginTransaction();
    try {
        foreach ($request->cart as $item) {
            $product = Product::find($item['id']);

            if (!$product) {
                return redirect()->back()->with([
                    'message' => 'Product not found: ' . $item['id']
                ])->withInput();
            }

            // Retrieve stock
            $stock = Stock::where('product_id', $item['id'])
                ->where('seller_id', $request->owner_id)
                ->first();
// 
if ($request->type == 4) { // Increase stock, decrease product quantity

                if ($product->quantity < $item['quantity']) {
                    return redirect()->back()->with([
                        'message' => 'Insufficient product quantity for: ' . $product->name
                    ])->withInput();
                }

                if ($stock) {
                    $stock->stock += $item['quantity'];
                } else {
                    // Create new stock record if not exists
                    $stock = new Stock();
                    $stock->seller_id = $request->owner_id;
                    $stock->product_id = $item['id'];
                    $stock->stock = $item['quantity'];
                    $stock->main_stock = $item['quantity'];
                }

                // Decrease product quantity
                $product->quantity -= $item['quantity'];
            } elseif ($request->type == '7') { // Decrease stock, increase product quantity
                if ($stock && $stock->stock < $item['quantity']) {
                    return redirect()->back()->with([
                        'message' => 'Insufficient stock for product: ' . $product->name
                    ])->withInput();
                }

                if ($stock) {
                    $stock->stock -= $item['quantity'];
                }

                // Increase product quantity
                $product->quantity += $item['quantity'];
            }

            // Save updated stock and product details
            if ($stock) {
                $stock->save();
            }
            $product->save();
        }

        DB::commit();
     $reserved_product_ids = $request->reservation_id; // Collect IDs from cart
        $this->reserveProduct->where('id', $reserved_product_ids)->update(['active' => 0]);

        return redirect()->back()->with([
            'message' => 'Stock and product quantities updated successfully.',
        ]);
    } catch (\Exception $e) {
        DB::rollBack();

        return redirect()->back()->with([
            'message' => 'Failed to update stock and product quantities.',
            'error' => $e->getMessage()
        ])->withInput();
    }
}

public function deactivateReservedProductsByReservationId(Request $request, $reservationId)
{
    // Retrieve the reserved products associated with the given reservation ID
    $reservedProducts = $this->reserveProduct
        ->where('id', $reservationId)
        ->get();

    // Check if there are any reserved products for the given reservation ID
    if ($reservedProducts->isEmpty()) {
        // Handle case where no products are found for the given reservation ID
        return response()->json([
            'status' => false,
            'error' => 'No valid product IDs found for the given reservation ID.'
        ], 400);
    }

    // Loop through each reserved product and set the active status to 0 (deactivate)
    foreach ($reservedProducts as $reservedProduct) {
        $reservedProduct->active = 0;
        $reservedProduct->save();
    }

    // Redirect back with a success message
    return redirect()->back()->with('status', 'Reserved products deactivated successfully.');
}




    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function search_product(Request $request): JsonResponse
    {

        $request->validate([
            'name' => 'required',
        ], [
            'name.required' => translate('Product name is required'),
        ]);

        $key = explode(' ', $request['name']);
        $products = $this->product->where('quantity', '>', 0)->active()->where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->where('name', 'like', "%{$value}%");
            }
        })->orWhere(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->where('product_code', 'like', "%{$value}%");
            }
        })->paginate(6);

        $count_p = $products->count();

        return response()->json([
            'result' => view('admin-views.pos._search-result', compact('products'))->render(),
            'count' => $count_p
        ]);
    }


    /**
     * @param Request $request
     * @return JsonResponse|void
     */
    public function search_by_add_product(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ], [
            'name.required' => translate('Product name is required'),
        ]);

        if (is_numeric($request['name'])) {
            $products = $this->product->where('quantity', '>', 0)->active()->where('product_code', $request['name'])->paginate(6);
        } else {
            $products = $this->product->where('quantity', '>', 0)->active()->where('name', $request['name'])->paginate(6);
        }

        $count_p = $products->count();
        if ($count_p > 0) {
            return response()->json([
                'count' => $count_p,
                'id' => $products[0]->id,
            ]);
        }
    }

    /**
     * @param Request $request
     * @return Application|Factory|View
     */

public function order_list(Request $request): Factory|View|Application
{
    $orders = $this->salesInvoiceQuery($request)
        ->paginate(Helpers::pagination_limit())
        ->appends($request->query());

    // Totals over the whole filtered set, not the page. The previous version
    // called ->sum() on the paginator, so the figures under the table
    // described only the 25 rows on screen.
    $totals = $this->salesInvoiceTotals($request);

    $search   = $request->input('search');
    $fromDate = $request->input('from_date');
    $toDate   = $request->input('to_date');
    $regionId = $this->selectedRegions($request);
    $done     = $request->input('done');

    // With counts, so the picker shows which regions hold invoices.
    $regions = $this->regionsWithCounts(4);
    $sellers = \App\Models\Seller::where('role', 'seller')
        ->orderBy('f_name')->get(['id', 'f_name', 'l_name', 'mandob_code']);

    $orderAmountSum   = $totals['total'];
    $collectedCashSum = $totals['collected'];
    $remainingSum     = $totals['remaining'];
    $quantitySum      = $totals['quantity'];
    $productCount     = $totals['lines'];

    // الحسابات لنافذة التحصيل من الويب.
    $accounts = \App\Models\Account::orderBy('account')->get(['id', 'account']);

    return view('admin-views.pos.order.list', compact(
        'orders', 'search', 'fromDate', 'toDate', 'regions', 'regionId', 'sellers',
        'orderAmountSum', 'collectedCashSum', 'remainingSum', 'quantitySum',
        'productCount', 'done', 'accounts'
    ));
}

/**
 * The sales-invoice query shared by the listing, the totals and the export,
 * so all three always describe the same set of rows.
 */


/**
 * Write rows out as CSV. maatwebsite/excel is not installed and Excel opens
 * CSV directly; the BOM keeps the Arabic headings readable.
 */
private function streamCsv($rows, string $filename)
{
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

/** Refunds matching the current filter, as CSV. */
public function refund_export(Request $request)
{
    $adminId   = Auth::guard('admin')->id();
    $sellerIds = AdminSeller::where('admin_id', $adminId)->pluck('seller_id');

    $query = $this->order
        ->where('type', 7)
        ->where(function ($q) use ($sellerIds, $adminId) {
            $q->whereIn('owner_id', $sellerIds)->orWhere('owner_id', $adminId);
        })
        ->with(['customer', 'seller', 'details']);

    if ($request->filled('search')) {
        $search = $request->input('search');
        $query->where(function ($q) use ($search) {
            $q->where('id', 'like', "%{$search}%")
              ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"))
              ->orWhereHas('seller', fn ($s) =>
                    $s->where('f_name', 'like', "%{$search}%")
                      ->orWhere('l_name', 'like', "%{$search}%"));
        });
    }

    if ($request->filled('from_date')) {
        $query->whereDate('created_at', '>=', $request->input('from_date'));
    }
    if ($request->filled('to_date')) {
        $query->whereDate('created_at', '<=', $request->input('to_date'));
    }

    $this->applyRegionFilter($query, $request);

    $rows = $query->latest('id')->get()->map(fn ($refund) => [
        'رقم المرتجع'  => $refund->id,
        'الفاتورة الأصلية' => $refund->parent_id ?: '',
        'التاريخ'      => optional($refund->created_at)->format('Y-m-d H:i'),
        'المندوب'      => trim(($refund->seller->f_name ?? '') . ' ' . ($refund->seller->l_name ?? '')),
        'كود المندوب'  => $refund->seller->mandob_code ?? '',
        'العميل'       => $refund->customer->name ?? '',
        'المنطقة'      => optional($refund->customer->regions ?? null)->name ?? '',
        'عدد الأصناف'  => $refund->details->count(),
        'الكمية'       => (float) $refund->details->sum('quantity'),
        'قيمة المرتجع' => round((float) $refund->order_amount, 2),
        'الضريبة'      => (float) $refund->total_tax,
    ]);

    return $this->streamCsv($rows, 'refunds-' . now()->format('Y-m-d') . '.csv');
}

/** Collections matching the current filter, as CSV. */
public function installment_export(Request $request)
{
    $adminId   = Auth::guard('admin')->id();
    $sellerIds = AdminSeller::where('admin_id', $adminId)->pluck('seller_id')->all();
    $sellerIds[] = $adminId;

    $query = \App\Models\Installment::with(['customer', 'seller'])
        ->whereIn('seller_id', $sellerIds);

    if ($request->filled('search')) {
        $search = $request->input('search');
        $query->where(function ($q) use ($search) {
            $q->where('id', 'like', "%{$search}%")
              ->orWhere('order_id', 'like', "%{$search}%")
              ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
        });
    }

    if ($request->filled('from_date')) {
        $query->whereDate('created_at', '>=', $request->input('from_date'));
    }
    if ($request->filled('to_date')) {
        $query->whereDate('created_at', '<=', $request->input('to_date'));
    }

    $this->applyRegionFilter($query, $request);

    $rows = $query->latest('id')->get()->map(fn ($i) => [
        'رقم التحصيل' => $i->id,
        'رقم الفاتورة' => $i->order_id ?: '',
        'التاريخ'     => optional($i->created_at)->format('Y-m-d H:i'),
        'المندوب'     => trim(($i->seller->f_name ?? '') . ' ' . ($i->seller->l_name ?? '')),
        'العميل'      => $i->customer->name ?? '',
        'المنطقة'     => optional($i->customer->regions ?? null)->name ?? '',
        'المبلغ'      => round((float) $i->total_price, 2),
        'ملاحظات'     => $i->note,
    ]);

    return $this->streamCsv($rows, 'collections-' . now()->format('Y-m-d') . '.csv');
}
/**
 * Narrow a query to one or more customer regions.
 *
 * `region_id` may be a single value or an array, so the page can offer a
 * multi-select while older links carrying one id keep working.
 */

/**
 * Regions with the number of matching invoices of a given type, so the picker
 * can show which ones actually hold anything.
 */
private function regionsWithCounts(int $orderType): \Illuminate\Support\Collection
{
    $adminId   = Auth::guard('admin')->id();
    $sellerIds = AdminSeller::where('admin_id', $adminId)->pluck('seller_id')->all();
    $sellerIds[] = $adminId;

    $counts = \App\Models\Order::query()
        ->join('customers', 'customers.id', '=', 'orders.user_id')
        ->where('orders.type', $orderType)
        ->whereIn('orders.owner_id', $sellerIds)
        ->groupBy('customers.region_id')
        // selectRaw with an alias: pluck(DB::raw(...)) does not give the
        // expression a usable column name.
        ->selectRaw('customers.region_id as region_id, COUNT(orders.id) as total')
        ->pluck('total', 'region_id');

    return $this->regions->orderBy('name')->get()->map(function ($region) use ($counts) {
        $region->invoice_count = (int) ($counts[$region->id] ?? 0);
        return $region;
    });
}
private function applyRegionFilter($query, Request $request): void
{
    $regions = array_filter((array) $request->input('region_id'), fn ($v) => $v !== '' && $v !== null);

    if (!$regions) {
        return;
    }

    $query->whereHas('customer', fn ($q) => $q->whereIn('region_id', $regions));
}

/** The region ids currently selected, for re-checking the control. */
private function selectedRegions(Request $request): array
{
    return array_map('strval', array_filter(
        (array) $request->input('region_id'),
        fn ($v) => $v !== '' && $v !== null
    ));
}
private function salesInvoiceQuery(Request $request)
{
    $adminId   = Auth::guard('admin')->id();
    $sellerIds = AdminSeller::where('admin_id', $adminId)->pluck('seller_id');

    $orders = $this->order
        ->where('type', 4)
        ->where(function ($query) use ($sellerIds, $adminId) {
            $query->whereIn('owner_id', $sellerIds)->orWhere('owner_id', $adminId);
        })
        ->with(['customer', 'seller', 'details']);

    // الفواتير المؤرشفة تخرج من قوائم العمل اليومية. الأرشفة وسم لا حذف،
    // فهي تبقى في التقارير والأرصدة، ولها شاشتها الخاصة. show_archived=1
    // يعيدها إلى العرض عند الحاجة دون تغيير أي شيء آخر.
    if (!$request->boolean('show_archived')) {
        $orders->whereNull('archived_at');
    }

    if ($request->filled('search')) {
        $search = $request->input('search');

        $orders->where(function ($query) use ($search) {
            $query->where('id', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('seller', fn ($q) =>
                        $q->where('f_name', 'like', "%{$search}%")
                          ->orWhere('l_name', 'like', "%{$search}%")
                          ->orWhere('mandob_code', 'like', "%{$search}%"));
        });
    }

    // Each end applies on its own rather than needing both.
    if ($request->filled('from_date')) {
        $orders->whereDate('created_at', '>=', $request->input('from_date'));
    }
    if ($request->filled('to_date')) {
        $orders->whereDate('created_at', '<=', $request->input('to_date'));
    }

    $this->applyRegionFilter($orders, $request);

    if ($request->filled('seller_id')) {
        $orders->where('owner_id', $request->input('seller_id'));
    }

    // Cash or credit.
    if ($request->filled('cash')) {
        $orders->where('cash', (int) $request->input('cash'));
    }

    // Settled vs outstanding. This was commented out, so the control on the
    // page submitted and nothing changed.
    if ($request->filled('done')) {
        $done = (string) $request->input('done');

        if ($done === '1') {
            $orders->whereColumn('collected_cash', '>=', 'order_amount');
        } elseif ($done === 'returned') {
            // الفواتير التي صدر عليها مرتجع: المرتجع فاتورة نوع 7 تحمل
            // parent_id بالفاتورة الأصلية.
            $orders->whereIn('id', function ($q) {
                $q->select('parent_id')
                  ->from('orders')
                  ->where('type', 7)
                  ->whereNotNull('parent_id');
            });
        } else {
            $orders->whereColumn('collected_cash', '<', 'order_amount');
        }
    }

    return $orders->latest('id');
}

/** Totals for the current filter, computed in SQL over every matching row. */
private function salesInvoiceTotals(Request $request): array
{
    $row = $this->salesInvoiceQuery($request)
        ->reorder()
        ->selectRaw('COUNT(*) as invoices,
                     COALESCE(SUM(order_amount), 0)   as total,
                     COALESCE(SUM(collected_cash), 0) as collected')
        ->first();

    $total     = (float) ($row->total ?? 0);
    $collected = (float) ($row->collected ?? 0);

    // The line figures need the details, so they are summed separately.
    $ids = (clone $this->salesInvoiceQuery($request))->reorder()->pluck('id');

    $lines = \App\Models\OrderDetail::whereIn('order_id', $ids);

    return [
        'invoices'  => (int) ($row->invoices ?? 0),
        'total'     => round($total, 2),
        'collected' => round($collected, 2),
        'remaining' => round(max($total - $collected, 0), 2),
        'quantity'  => (float) (clone $lines)->sum('quantity'),
        'lines'     => (clone $lines)->count(),
    ];
}

/**
 * The current filter as CSV. maatwebsite/excel is not installed and Excel
 * opens CSV directly; the BOM keeps the Arabic headings readable.
 */
public function order_export(Request $request)
{
    $rows = $this->salesInvoiceQuery($request)->get()->map(function ($order) {
        $total     = (float) $order->order_amount;
        $collected = (float) $order->collected_cash;

        return [
            'رقم الفاتورة' => $order->id,
            'التاريخ'      => optional($order->created_at)->format('Y-m-d H:i'),
            'المندوب'      => trim(($order->seller->f_name ?? '') . ' ' . ($order->seller->l_name ?? '')),
            'كود المندوب'  => $order->seller->mandob_code ?? '',
            'العميل'       => $order->customer->name ?? '',
            'المنطقة'      => optional($order->customer->regions ?? null)->name ?? '',
            'طريقة الدفع'  => (int) $order->cash === 2 ? 'آجل' : 'كاش',
            'عدد الأصناف'  => $order->details->count(),
            'الكمية'       => (float) $order->details->sum('quantity'),
            'إجمالي الفاتورة' => round($total, 2),
            'خصم إضافي'    => (float) $order->extra_discount,
            'الضريبة'      => (float) $order->total_tax,
            'المحصّل'      => round($collected, 2),
            'المتبقي'      => round(max($total - $collected, 0), 2),
            'الحالة'       => $collected >= $total ? 'محصّلة' : ($collected > 0 ? 'محصّلة جزئياً' : 'غير محصّلة'),
        ];
    });

    $filename = 'sales-invoices-' . now()->format('Y-m-d') . '.csv';

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

/**
 * Reverse a collection on an invoice.
 *
 * The money comes back off the account and the customer owes it again — the
 * invoice becomes outstanding rather than being cancelled. Every write shares
 * one transaction and the rows are locked, so two admins reversing at once
 * cannot both read the same collected figure.
 */
/**
 * تحصيل فاتورة من الويب.
 *
 * كان التحصيل متاحًا من التطبيق فقط (api/v2/orders/{id}/collect). هذه الشاشة
 * تستخدم نفس خدمة OrderService::collectPayment بدل تكرار منطق المال، فتبقى
 * القيود واحدة: لا تحصيل على مرتجع، ولا مبلغ يتجاوز المتبقي، والعملية كلها
 * داخل معاملة واحدة مع قفل على الفاتورة والحساب.
 */
public function collect_payment(Request $request, $id): RedirectResponse
{
    $request->validate([
        'amount'     => ['required', 'numeric', 'gt:0'],
        'account_id' => ['required', 'exists:accounts,id'],
        'date'       => ['nullable', 'date'],
        'note'       => ['nullable', 'string', 'max:255'],
        'img'        => ['nullable', 'image'],
    ]);

    // الخدمة تتحقق أن المستدعي هو مالك الفاتورة نفسه، وهو المنطق الصحيح
    // للتطبيق. من الويب يحصّل الإداري نيابةً عن مناديبه، فنفحص الملكية هنا
    // ثم نمرّر مالك الفاتورة للخدمة.
    $order = $this->order->find($id);

    if (!$order) {
        Toastr::error(translate('الفاتورة غير موجودة'));
        return back();
    }

    $adminId   = (int) Auth::guard('admin')->id();
    $ownedIds  = AdminSeller::where('admin_id', $adminId)->pluck('seller_id')->map(fn ($v) => (int) $v)->all();
    $ownedIds[] = $adminId;

    if (!in_array((int) $order->owner_id, $ownedIds, true)) {
        Toastr::error(translate('هذه الفاتورة لا تخص مناديبك'));
        return back();
    }

    try {
        app(\App\Services\OrderService::class)->collectPayment(
            (int) $id,
            (int) $order->owner_id,
            [
                'amount'     => $request->input('amount'),
                'account_id' => $request->input('account_id'),
                'date'       => $request->input('date') ?: now()->toDateString(),
                'note'       => $request->input('note'),
            ],
            $request->file('img')
        );
    } catch (\Throwable $e) {
        Toastr::error(translate($e->getMessage()));
        return back();
    }

    Toastr::success(translate('تم تحصيل المبلغ بنجاح'));
    return back();
}

public function order_reverse_collection(Request $request, $id): RedirectResponse
{
    $request->validate([
        'amount' => ['required', 'numeric', 'gt:0'],
        'note'   => ['nullable', 'string', 'max:255'],
    ]);

    $amount = (float) $request->input('amount');

    try {
        \DB::transaction(function () use ($id, $amount, $request) {
            $order = $this->order->lockForUpdate()->findOrFail($id);

            if ((int) $order->type === 7) {
                throw new \InvalidArgumentException('لا يمكن رد تحصيل فاتورة مرتجع');
            }

            $collected = (float) $order->collected_cash;

            if ($amount > $collected) {
                throw new \InvalidArgumentException(
                    'المبلغ أكبر من المحصّل على الفاتورة (' . round($collected, 2) . ')'
                );
            }

            // Reverse the ledger entry: money out of the account.
            $account = $order->payment_id
                ? \App\Models\Account::lockForUpdate()->find($order->payment_id)
                : null;

            \App\Models\Transection::create([
                'tran_type'   => 'Receivable',
                'account_id'  => $order->payment_id,
                'seller_id'   => $order->owner_id,
                'customer_id' => $order->user_id,
                'order_id'    => $order->id,
                'amount'      => $amount,
                'description' => $request->input('note') ?: 'رد تحصيل فاتورة',
                'debit'       => 0,
                'credit'      => 1,
                'balance'     => $account ? $account->balance - $amount : 0,
                'date'        => now()->toDateString(),
                'cash'        => $order->cash,
            ]);

            if ($account) {
                $account->balance   = $account->balance - $amount;
                $account->total_out = (float) $account->total_out + $amount;
                $account->save();
            }

            // The customer owes it again.
            $customer = \App\Models\Customer::lockForUpdate()->find($order->user_id);
            if ($customer) {
                $customer->balance = (float) $customer->balance + $amount;
                $customer->save();
            }

            $order->collected_cash = $collected - $amount;
            $order->save();
        });
    } catch (\InvalidArgumentException $e) {
        Toastr::error($e->getMessage());
        return back();
    }

    Toastr::success(translate('تم رد التحصيل'));

    return back();
}



public function refund_list(Request $request): Factory|View|Application
{
    $search = $request->input('search');
    $fromDate = $request->input('from_date');
    $toDate = $request->input('to_date');
    $toNewDate = date('Y-m-d', strtotime("+1 day", strtotime($toDate)));
    $regionId = $this->selectedRegions($request); // Capture region_id from the request

    $adminId = Auth::guard('admin')->id(); // Get the authenticated admin ID

    // Retrieve seller_id(s) associated with the authenticated admin
    $sellerIds = AdminSeller::where('admin_id', $adminId)->pluck('seller_id'); 

  $query = $this->order
    ->where('type', 7)
    ->where(function ($query) use ($sellerIds, $adminId) {
        $query->whereIn('owner_id', $sellerIds)
              ->orWhere('owner_id', $adminId);
    })
    ->latest()
    // regions مُحمَّلة مسبقًا: العمود يعرض اسم المنطقة لكل صف، وبدونها
    // ينفَّذ استعلام لكل سطر.
    ->with(['customer.regions', 'seller']);

    $regions = $this->regions->get();

    // Apply search filter
    if ($search) {
        $query->where(function($query) use ($search) {
            $query->where('id', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($query) use ($search) {
                      $query->where('name', 'like', "%{$search}%"); // Search by customer name
                  })
                  ->orWhereHas('seller', function($query) use ($search) {
                      $query->where('f_name', 'like', "%{$search}%")
                            ->orWhere('l_name', 'like', "%{$search}%"); // Search by seller name
                  });
        });
    }

    // Apply date filter
    if ($fromDate && $toNewDate) {
        $query->whereBetween('created_at', [$fromDate, $toNewDate]);
    }

    // Apply region filter
    $this->applyRegionFilter($query, $request);

    // Paginate the results
    $refunds = $query->paginate(Helpers::pagination_limit())->appends($request->query());

    return view('admin-views.pos.refund.list', compact('refunds', 'search', 'fromDate', 'toDate', 'regionId', 'regions'));
}

/**
 * تصدير قوائم الطلبات ذات الشكل الواحد (عينات، تبرعات) إلى اكسيل.
 *
 * العينات والتبرعات والمرتجعات تشترك في نفس الفلاتر ونفس الأعمدة تقريبًا،
 * فبدل تكرار الدالة لكل نوع نمرّر نوع الطلب واسم الملف.
 */
private function orderTypeExport(Request $request, int $type, string $prefix, string $idLabel)
{
    $adminId   = Auth::guard('admin')->id();
    $sellerIds = AdminSeller::where('admin_id', $adminId)->pluck('seller_id');

    $query = $this->order
        ->where('type', $type)
        ->where(function ($q) use ($sellerIds, $adminId) {
            $q->whereIn('owner_id', $sellerIds)->orWhere('owner_id', $adminId);
        })
        ->with(['customer.regions', 'seller', 'details']);

    if ($request->filled('search')) {
        $search = $request->input('search');
        $query->where(function ($q) use ($search) {
            $q->where('id', 'like', "%{$search}%")
              ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"))
              ->orWhereHas('seller', fn ($sq) =>
                    $sq->where('f_name', 'like', "%{$search}%")
                       ->orWhere('l_name', 'like', "%{$search}%"));
        });
    }

    if ($request->filled('from_date')) {
        $query->whereDate('created_at', '>=', $request->input('from_date'));
    }
    if ($request->filled('to_date')) {
        $query->whereDate('created_at', '<=', $request->input('to_date'));
    }

    $this->applyRegionFilter($query, $request);

    $rows = $query->latest('id')->get()->map(fn ($order) => [
        $idLabel        => $order->id,
        'التاريخ'       => optional($order->created_at)->format('Y-m-d H:i'),
        'المندوب'       => trim(($order->seller->f_name ?? '') . ' ' . ($order->seller->l_name ?? '')),
        'كود المندوب'   => $order->seller->mandob_code ?? '',
        'العميل'        => $order->customer->name ?? '',
        'المنطقة'       => optional($order->customer->regions ?? null)->name ?? '',
        'عدد الأصناف'   => $order->details->count(),
        'الكمية'        => (float) $order->details->sum('quantity'),
        'القيمة'        => round((float) $order->order_amount, 2),
        'المحصل'        => round((float) $order->transaction_reference, 2),
    ]);

    return $this->streamCsvRows($rows, $this->exportFilename($prefix));
}

/** العينات كملف اكسيل، بنفس فلاتر الشاشة. */
public function sample_export(Request $request)
{
    return $this->orderTypeExport($request, 12, 'samples', 'رقم العينة');
}

/** التبرعات كملف اكسيل، بنفس فلاتر الشاشة. */
public function donation_export(Request $request)
{
    return $this->orderTypeExport($request, 24, 'donations', 'رقم التبرع');
}

/** حركة المخزون كملف اكسيل، بنفس فلاتر الشاشة. */
public function stock_history_export(Request $request)
{
    $adminId   = Auth::guard('admin')->id();
    $sellerIds = AdminSeller::where('admin_id', $adminId)->pluck('seller_id')->all();
    $sellerIds[] = $adminId;

    $query = \App\Models\StockHistory::with(['product', 'seller'])
        ->whereIn('seller_id', $sellerIds);

    if ($request->filled('search')) {
        $search = $request->input('search');
        $query->where(function ($q) use ($search) {
            $q->where('order_id', 'like', "%{$search}%")
              ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$search}%")
                                                  ->orWhere('product_code', 'like', "%{$search}%"));
        });
    }

    if ($request->filled('from_date')) {
        $query->whereDate('created_at', '>=', $request->input('from_date'));
    }
    if ($request->filled('to_date')) {
        $query->whereDate('created_at', '<=', $request->input('to_date'));
    }

    $rows = $query->latest('id')->get()->map(fn ($h) => [
        'رقم التسوية' => $h->order_id,
        'التاريخ'     => optional($h->created_at)->format('Y-m-d H:i'),
        'المندوب'     => trim(($h->seller->f_name ?? '') . ' ' . ($h->seller->l_name ?? '')),
        'المنتج'      => $h->product->name ?? '',
        'كود المنتج'  => $h->product->product_code ?? '',
        'ما كان بحوزته' => $h->main_stock,
        'المباع'      => $h->stock,
        'المتبقي'     => max(0, (int) $h->main_stock - (int) $h->stock),
    ]);

    return $this->streamCsvRows($rows, $this->exportFilename('stock-history'));
}

public function sample_list(Request $request): Factory|View|Application
{
    
    $search = $request->input('search');
    $fromDate = $request->input('from_date');
    $toDate = $request->input('to_date');
    $regionId = $this->selectedRegions($request);
    $done = $request->input('done'); // For the 'done' filter (1 or 0)
    $toNewDate = date('Y-m-d', strtotime("+1 day", strtotime($toDate)));
    $adminId = Auth::guard('admin')->id();

    // Retrieve seller_id(s) associated with the authenticated admin
    $sellerIds = AdminSeller::where('admin_id', $adminId)->pluck('seller_id');
    $orders = $this->order
        ->where('type', 12)
        ->where(function ($query) use ($sellerIds, $adminId) {
            $query->whereIn('owner_id', $sellerIds)
                  ->orWhere('owner_id', $adminId);
        })
        ->latest()
        ->with(['customer', 'seller', 'details']); // Assuming relationship for details is set
// dd($orders);

    // Apply search filter
    if (!empty($search)) {
        $orders->where(function($query) use ($search) {
            $query->where('id', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($query) use ($search) {
                      $query->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('seller', function($query) use ($search) {
                      $query->where('email', 'like', "%{$search}%")
                            ->orWhere('l_name', 'like', "%{$search}%");
                  });
        });
    }

    // Apply date filter
    if (!empty($fromDate) && !empty($toNewDate)) {
        $orders->whereBetween('created_at', [$fromDate, $toNewDate]);
    }

    // Apply region filter for customer
    // Accepts a single region or several.
    $this->applyRegionFilter($orders, $request);

    // // Apply done status filter (1 or 0)
    // if (isset($done)) {
    //     if ($done == 1) {
    //     $orders = $orders->where('order_amount', '=', \DB::raw('transaction_reference'));
    //     } else {
    //     $orders = $orders->where('order_amount', '>', \DB::raw('transaction_reference'));
    //     }
    // }

    // // Apply done status filter before pagination
    // if ($done == 1) {
    //     // Filter where order_amount = transaction_reference
    //     $orders = $orders->where('order_amount', '=', \DB::raw('transaction_reference'));
    // } else {
    //     // Filter where order_amount > transaction_reference
    //     $orders = $orders->where('order_amount', '>', \DB::raw('transaction_reference'));
    // }

    // Paginate the filtered orders
    $orders = $orders->paginate(Helpers::pagination_limit())->appends([
        'search' => $search,
        'from_date' => $fromDate,
        'to_date' => $toDate,
        'region_id' => $regionId,
        'done' => $done,
    ]);

    // Calculate sums after pagination
    $orderAmountSum = $orders->sum('order_amount');
    $collectedCashSum = $orders->sum('collected_cash');
    $quantitySum = $orders->sum(function ($order) {
        return $order->details->sum('quantity'); // Use 'details' instead of 'orderDetails'
    });
    $productCount = $orders->sum(function ($order) {
        return $order->details->count(); // Use 'details' instead of 'orderDetails'
    });

    // Get regions for the dropdown
    $regions = $this->regions->get();

    // Return the view with the necessary data
    return view('admin-views.pos.sample.list', compact(
        'orders',
        'search',
        'fromDate',
        'toDate',
        'regions',
        'regionId',
        'orderAmountSum',
        'collectedCashSum',
        'quantitySum',
        'productCount',
        'done'
    ));
}

public function donation_list(Request $request): Factory|View|Application
{
       
    $search = $request->input('search');
    $fromDate = $request->input('from_date');
    $toDate = $request->input('to_date');
    $regionId = $this->selectedRegions($request);
    $done = $request->input('done'); // For the 'done' filter (1 or 0)
    $toNewDate = date('Y-m-d', strtotime("+1 day", strtotime($toDate)));
    $adminId = Auth::guard('admin')->id();

    // Retrieve seller_id(s) associated with the authenticated admin
    $sellerIds = AdminSeller::where('admin_id', $adminId)->pluck('seller_id');
    $orders = $this->order
        ->where('type', 24)
        ->where(function ($query) use ($sellerIds, $adminId) {
            $query->whereIn('owner_id', $sellerIds)
                  ->orWhere('owner_id', $adminId);
        })
        ->latest()
        ->with(['customer', 'seller', 'details']); // Assuming relationship for details is set
// dd($orders);

    // Apply search filter
    if (!empty($search)) {
        $orders->where(function($query) use ($search) {
            $query->where('id', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($query) use ($search) {
                      $query->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('seller', function($query) use ($search) {
                      $query->where('email', 'like', "%{$search}%")
                            ->orWhere('l_name', 'like', "%{$search}%");
                  });
        });
    }

    // Apply date filter
    if (!empty($fromDate) && !empty($toNewDate)) {
        $orders->whereBetween('created_at', [$fromDate, $toNewDate]);
    }

    // Apply region filter for customer
    // Accepts a single region or several.
    $this->applyRegionFilter($orders, $request);

    // // Apply done status filter (1 or 0)
    // if (isset($done)) {
    //     if ($done == 1) {
    //     $orders = $orders->where('order_amount', '=', \DB::raw('transaction_reference'));
    //     } else {
    //     $orders = $orders->where('order_amount', '>', \DB::raw('transaction_reference'));
    //     }
    // }

    // // Apply done status filter before pagination
    // if ($done == 1) {
    //     // Filter where order_amount = transaction_reference
    //     $orders = $orders->where('order_amount', '=', \DB::raw('transaction_reference'));
    // } else {
    //     // Filter where order_amount > transaction_reference
    //     $orders = $orders->where('order_amount', '>', \DB::raw('transaction_reference'));
    // }

    // Paginate the filtered orders
    $orders = $orders->paginate(Helpers::pagination_limit())->appends([
        'search' => $search,
        'from_date' => $fromDate,
        'to_date' => $toDate,
        'region_id' => $regionId,
        'done' => $done,
    ]);

    // Calculate sums after pagination
    $orderAmountSum = $orders->sum('order_amount');
    $collectedCashSum = $orders->sum('collected_cash');
    $quantitySum = $orders->sum(function ($order) {
        return $order->details->sum('quantity'); // Use 'details' instead of 'orderDetails'
    });
    $productCount = $orders->sum(function ($order) {
        return $order->details->count(); // Use 'details' instead of 'orderDetails'
    });

    // Get regions for the dropdown
    $regions = $this->regions->get();

    // Return the view with the necessary data
    return view('admin-views.pos.donation.list', compact(
        'orders',
        'search',
        'fromDate',
        'toDate',
        'regions',
        'regionId',
        'orderAmountSum',
        'collectedCashSum',
        'quantitySum',
        'productCount',
        'done'
    ));
}
    


public function installment_list(Request $request): Factory|View|Application
{
    $search    = $request->input('search');
    $fromDate  = $request->input('from_date');
    $toDate    = $request->input('to_date');
    $regionId  = $request->input('region_id');
    $done      = $request->input('done'); // لو محتاجه لاحقًا
    $regions   = $this->regions->get();

    $adminId  = Auth::guard('admin')->id();
    $sellerIds = AdminSeller::where('admin_id', $adminId)->pluck('seller_id');

    /* ===================== Orders: collected_cash sum فقط ===================== */
    $ordersCashQuery = $this->order
        ->where('type', 4)
        ->where(function ($q) use ($sellerIds, $adminId) {
            $q->whereIn('owner_id', $sellerIds)
              ->orWhere('owner_id', $adminId);
        })
        ->with(['customer', 'seller']);

    // نفس فلتر البحث المستخدم في installments:
    if (!empty($search)) {
        $ordersCashQuery->where(function ($q) use ($search) {
            $q->where('id', 'like', "%{$search}%")
              ->orWhereHas('customer', function ($sub) use ($search) {
                  $sub->where('name', 'like', "%{$search}%");
              })
              ->orWhereHas('seller', function ($sub) use ($search) {
                  $sub->where('f_name', 'like', "%{$search}%")
                      ->orWhere('l_name', 'like', "%{$search}%")
                      ->orWhereRaw("CONCAT(f_name,' ',l_name) LIKE ?", ["%{$search}%"]);
              });
        });
    }

    // نفس فلتر المنطقة:
    // Accepts a single region or several.
    $this->applyRegionFilter($ordersCashQuery, $request);

    // نفس فلتر التاريخ (استخدام Carbon مع نهاية اليوم لضمان الشمول):
    if (!empty($fromDate) && !empty($toDate)) {
        $ordersCashQuery->whereBetween('created_at', [
            Carbon::parse($fromDate)->startOfDay(),
            Carbon::parse($toDate)->endOfDay(),
        ]);
    }

    // مجموع الـ collected_cash من الطلبات فقط:
    $collectedCashSum = (float) $ordersCashQuery->sum('transaction_reference');

    /* ===================== Installments: نفس الفلاتر ===================== */
    $baseQuery = $this->installment
        ->where(function ($q) use ($sellerIds, $adminId) {
            $q->whereIn('seller_id', $sellerIds)
              ->orWhere('seller_id', $adminId);
        })
        ->with(['customer', 'seller']);

    if (!empty($search)) {
        $baseQuery->where(function ($q) use ($search) {
            $q->where('id', 'like', "%{$search}%")            // رقم القسط
              ->orWhere('order_id', 'like', "%{$search}%")     // رقم الفاتورة المرتبطة (لو موجود)
              ->orWhereHas('customer', function ($sub) use ($search) {
                  $sub->where('name', 'like', "%{$search}%");
              })
              ->orWhereHas('seller', function ($sub) use ($search) {
                  $sub->where('f_name', 'like', "%{$search}%")
                      ->orWhere('l_name', 'like', "%{$search}%")
                      ->orWhereRaw("CONCAT(f_name,' ',l_name) LIKE ?", ["%{$search}%"]);
              });
        });
    }

    // Accepts a single region or several.
    $this->applyRegionFilter($baseQuery, $request);

    if (!empty($fromDate) && !empty($toDate)) {
        $baseQuery->whereBetween('created_at', [
            Carbon::parse($fromDate)->startOfDay(),
            Carbon::parse($toDate)->endOfDay(),
        ]);
    }

    // إجمالي مبلغ الأقساط (إن كنت تحتاجه)
    $totalAmount = (clone $baseQuery)->sum('total_price');

    // Pagination للأقساط
    $installments = $baseQuery->latest()
        ->paginate(Helpers::pagination_limit())
        ->appends($request->query());

    // عرض الصفحة: أضفت collectedCashSum مع باقي الداتا
    return view('admin-views.pos.installment.list', compact(
        'installments',
        'search',
        'fromDate',
        'toDate',
        'regions',
        'regionId',
        'totalAmount',
        'collectedCashSum'
    ));
}






    public function generate_installments_invoice($id)
    {
        // نفس الفحص: التحصيل يخص مندوبًا، فلا يُعرض إلا لمن يملكه.
        $installment = $this->installment
            ->where('id', $id)
            ->whereIn('seller_id', $this->invoiceOwnerIds())
            ->first();

        return response()->json([
            'success' => 1,
            'view' => view('admin-views.pos.installment.invoice', compact('installment'))->render(),
        ]);
    }
public function reservation_list(Request $request, $type, $active): Factory|View|Application
{
    $reservations = $this->reservationQuery($request, $type, $active)
        ->paginate(Helpers::pagination_limit())
        ->appends($request->query());

    $search   = $request->input('search');
    $fromDate = $request->input('from_date');
    $toDate   = $request->input('to_date');

    $sellers = \App\Models\Seller::where('role', 'seller')
        ->orderBy('f_name')->get(['id', 'f_name', 'l_name', 'mandob_code']);

    return view('admin-views.pos.reservations.list',
        compact('reservations', 'search', 'fromDate', 'toDate', 'sellers', 'type', 'active'));
}

/**
 * The reservation query shared by the listing and the export, so a download
 * always matches what was on screen.
 */
private function reservationQuery(Request $request, $type, $active)
{
    $adminId   = Auth::guard('admin')->id();
    $sellerIds = AdminSeller::where('admin_id', $adminId)->pluck('seller_id')->all();

    // Grouped: the previous version chained ->orwhere() straight onto the
    // builder, which cancelled the type and active filters and let rows
    // belonging to other admins through.
    $query = ReserveProduct::with(['customer', 'seller'])
        ->where('type', $type)
        ->where(function ($q) use ($sellerIds, $adminId) {
            $q->whereIn('seller_id', $sellerIds)->orWhere('seller_id', $adminId);
        });

    if ($active === 'all') {
        $query->whereIn('active', [0, 1]);
    } else {
        $query->where('active', $active);
    }

    if ($request->filled('seller_id')) {
        $query->where('seller_id', $request->input('seller_id'));
    }

    if ($request->filled('search')) {
        $search = $request->input('search');

        $query->where(function ($q) use ($search) {
            $q->where('id', $search)
              // The note the seller typed when placing the request.
              ->orWhere('note', 'like', "%{$search}%")
              ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"))
              ->orWhereHas('seller', fn ($s) =>
                    $s->where('f_name', 'like', "%{$search}%")
                      ->orWhere('l_name', 'like', "%{$search}%")
                      ->orWhere('mandob_code', 'like', "%{$search}%"));
        });
    }

    // Each end applies on its own; the previous version needed both or it
    // ignored the range entirely.
    if ($request->filled('from_date')) {
        $query->whereDate('created_at', '>=', $request->input('from_date'));
    }
    if ($request->filled('to_date')) {
        $query->whereDate('created_at', '<=', $request->input('to_date'));
    }

    return $query->latest('id');
}

/**
 * The current filter as CSV. maatwebsite/excel is not installed, and Excel
 * opens CSV directly; the BOM keeps the Arabic headings readable.
 */
public function reservation_export(Request $request, $type, $active)
{
    $rows = $this->reservationQuery($request, $type, $active)->get()->map(function ($r) {
        $lines = json_decode($r->data, true) ?: [];

        $total = 0;
        $names = [];
        foreach ($lines as $line) {
            $total  += (float) ($line['price'] ?? 0) * (float) ($line['stock'] ?? 0);
            $names[] = ($line['product_name'] ?? '') . ' (' . (float) ($line['stock'] ?? 0) . ')';
        }

        return [
            'رقم الطلب'   => $r->id,
            'المندوب'     => trim(($r->seller->f_name ?? '') . ' ' . ($r->seller->l_name ?? '')),
            'كود المندوب' => $r->seller->mandob_code ?? '',
            'العميل'      => $r->customer->name ?? '',
            'النوع'       => (string) $r->type === '7' ? 'مرتجع' : 'طلب',
            'الحالة'      => (int) $r->active === 1 ? 'قيد التنفيذ' : 'مغلق',
            'عدد الأصناف' => count($lines),
            'الأصناف'     => implode(' | ', $names),
            'الإجمالي'    => round($total, 2),
            'ملاحظات المندوب' => $r->note,
            'التاريخ'     => optional($r->created_at)->format('Y-m-d H:i'),
        ];
    });

    $filename = 'reservations-' . $type . '-' . now()->format('Y-m-d') . '.csv';

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
      public function generate_reservation_invoicea2($id)
    {
        $reserveProduct = $this->reserveProduct->find($id);

        return response()->json([
            'success' => 1,
            'view' => view('admin-views.pos.reservations.invoicea2', compact('reserveProduct'))->render(),
        ]);
    }


public function generate_reservation_notification_invoice($id)
{
    // Find the reservation product by ID
    
    $reserveProduct = $this->reserveProductNotification->find($id);
    
    // Fetch all products (you might want to fetch only related products)
    $products = \App\Models\Product::all();
    $sellers = \App\Models\Admin::where('role', 'seller')->get(); // Adjust the query to suit your app's logic

    // Initialize the seller_id and customer_id
    $seller_id = auth()->user()->seller_id ?? null;
    $customer_id = auth()->id(); // Assuming the customer is the logged-in user

    // Loop through each product to get the appropriate price
    foreach ($products as $product) {
        $product->price = $this->getProductPrice($product, $seller_id, $customer_id);
    }

    return response()->json([
        'success' => 1,
        'view' => view('admin-views.pos.reservations.invoice_notification', compact('reserveProduct', 'products','sellers'))->render(),
    ]);
}

/**
 * Method to get the appropriate price for a product
 */
protected function getProductPrice($product, $seller_id = null, $customer_id = null)
{
    // Check if there is a seller-specific price
    if ($seller_id) {
        $sellerPrice = \App\Models\SellerPrice::where('product_id', $product->id)
                                  ->where('seller_id', $seller_id)
                                  ->first();
        if ($sellerPrice) {
            return $sellerPrice->price;
        }
    }

    // Check if there is a customer-specific price
    if ($customer_id) {
        $customerPrice = \App\Models\CustomerPrice::where('product_id', $product->id)
                                      ->where('customer_id', $customer_id)
                                      ->first();
        if ($customerPrice) {
            return $customerPrice->price;
        }
    }

    // Default product price
    return $product->selling_price;
}

    
    
  public function generate_reservation_invoice($id)
    {
        $reserveProduct = $this->reserveProduct->find($id);

        return response()->json([
            'success' => 1,
            'view' => view('admin-views.pos.reservations.invoice', compact('reserveProduct'))->render(),
        ]);
    }
public function generate_reservation_invoice_notification($id)
{
    // Find the reserve product by its ID
    $reserveProduct = $this->reserveProduct->find($id);

    // Fetch all products
    $products = \App\Models\Product::all();
    $sellers = \App\Models\Admin::where('role', 'seller')->get(); // Adjust the query to suit your app's logic

    // Initialize the seller_id and customer_id
    $seller_id = $reserveProduct->seller_id ?? null;
    $customer_id = $reserveProduct->customer_id; // Assuming the customer is the logged-in user

    // Loop through each product to get the appropriate price
    foreach ($products as $product) {
        // Start with the default product price
        $product->selling_price = $product->selling_price;

        // Check for a specific price in the customer_prices table
        $customerPrice = \DB::table('customer_prices')
            ->where('customer_id', $customer_id)
            ->where('product_id', $product->id)
            ->first();

        if ($customerPrice) {
            // Use price from customer_prices if available
            $product->selling_price = $customerPrice->price;
        } else {
            // If no customer-specific price, check the seller_prices table
            $sellerPrice = \DB::table('seller_prices')
                ->where('seller_id', $seller_id)
                ->where('product_id', $product->id)
                ->first();

            if ($sellerPrice) {
                // Use price from seller_prices if available
                $product->selling_price = $sellerPrice->price;
            }
        }
    }

    return view('admin-views.pos.reservations.invoice_notification', compact('reserveProduct', 'products','sellers'));
}


    
    public function generate_stocks_invoice($id)
    {
$adminId = Auth::guard('admin')->id(); // Get the authenticated admin ID

    // Retrieve seller_id(s) associated with the authenticated admin
    $sellerIds = AdminSeller::where('admin_id', $adminId)->pluck('seller_id'); 


        $order = $this->stock_order->whereIn('seller_id', $sellerIds)->orwhere('seller_id',$adminId)->
find($id);
        $order['statistcs'] = json_decode($order->statistcs);
        // return $order->statistcs->products;

        return response()->json([
            'success' => 1,
            'view' => view('admin-views.pos.stocks.invoice', compact('order'))->render(),
        ]);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    /**
     * المناديب التابعون للحساب الحالي، بالإضافة إليه.
     *
     * الفواتير كانت تُجلب بالمعرّف وحده، فيكفي تغيير الرقم في الرابط لعرض
     * فاتورة مندوب تابع لحساب آخر بكل بياناتها. هذا الفحص يقصر العرض على
     * فواتير مناديب الحساب الحالي.
     */
    private function invoiceOwnerIds(): array
    {
        $adminId = Auth::guard('admin')->id();

        $ids = AdminSeller::where('admin_id', $adminId)
            ->pluck('seller_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        $ids[] = (int) $adminId;

        return array_values(array_unique($ids));
    }

    /** استعلام فاتورة مقصور على مناديب الحساب الحالي. */
    private function ownedOrderQuery($id)
    {
        return $this->order
            ->where('id', $id)
            ->whereIn('owner_id', $this->invoiceOwnerIds());
    }

    public function generate_invoice($id)
    {
        $order = $this->ownedOrderQuery($id)->with(['details'])->first();
        //return $order;
        return response()->json([
            'success' => 1,
            'view' => view('admin-views.pos.order.invoice', compact('order'))->render(),
        ]);
    }
/**
     * The refund invoice. The refunds listing used to call generate_invoice(),
     * which renders the *sales* invoice template, so the modal showed the wrong
     * document (and blew up on refunds whose seller/customer it did not eager
     * load). Refunds have their own template; render that one.
     */
    public function refund_generate_invoice($id)
    {
        $order = $this->ownedOrderQuery($id)
            ->where('type', 7)
            ->with(['details', 'customer', 'seller'])
            ->first();

        if (!$order) {
            return response()->json(['success' => 0, 'view' => ''], 404);
        }

        return response()->json([
            'success' => 1,
            'view' => view('admin-views.pos.refund.invoice', compact('order'))->render(),
        ]);
    }

public function generate_invoice_purchase($id)
{
    // 1. جلب الشراء بناءً على الـ ID
    $purchase = Purchase::with([
        'details.material', // تفاصيل المواد
        'supplier',         // بيانات المورد
        'admin'             // المستخدم الذي أضاف الفاتورة
    ])->findOrFail($id);

    // 2. جلب الحسابات
    $accounts = Account::all();

    // 3. عرض الفاتورة في JSON مع HTML
    return response()->json([
        'success' => 1,
        'view'    => view('admin-views.purchases.invoice', compact('purchase', 'accounts'))->render(),
    ]);
}


     public function sample_generate_invoice($id)
    {
        $order = $this->ownedOrderQuery($id)->with(['details'])->first();
        //return $order;
        return response()->json([
            'success' => 1,
            'view' => view('admin-views.pos.sample.invoice', compact('order'))->render(),
        ]);
    }
       public function donation_generate_invoice($id)
    {
        $order = $this->ownedOrderQuery($id)->with(['details'])->first();
        //return $order;
        return response()->json([
            'success' => 1,
            'view' => view('admin-views.pos.donation.invoice', compact('order'))->render(),
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function get_customers(Request $request): JsonResponse
    {
        $key = explode(' ', $request['q']);
        $data = DB::table('customers')
            ->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%")
                        ->orWhere('mobile', 'like', "%{$value}%");
                }
            })->limit(6)
            ->get([DB::raw('id, IF(id <> "0",CONCAT(name,  " (", mobile ,")"), name) as text')]);

        return response()->json($data);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function customer_balance(Request $request): JsonResponse
    {
        // optional(): an unknown customer id fataled here rather than being handled.
        $customer_balance = optional($this->customer->where('id', $request->customer_id)->first())->balance;
        return response()->json([
            'customer_balance' => $customer_balance
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function remove_coupon(Request $request): JsonResponse
    {
        $cart_id = ($request->user_id != 0 ? 'sc-' . $request->user_id : 'wc-' . rand(10, 1000));
        if (!in_array($cart_id, session('cart_name') ?? [])) {
            session()->push('cart_name', $cart_id);
        }

        $cart = session(session('current_user'));

        $cart_keeper = [];
        if (session()->has(session('current_user')) && count($cart) > 0) {
            foreach ($cart as $cartItem) {

                array_push($cart_keeper, $cartItem);
            }
        }
        if (session('current_user') != $cart_id) {
            $temp_cart_name = [];
            foreach (session('cart_name') as $cart_name) {
                if ($cart_name != session('current_user')) {
                    $temp_cart_name[] = $cart_name;
                }
            }
            session()->put('cart_name', $temp_cart_name);
        }
        session()->put('cart_name', $temp_cart_name);
        session()->forget(session('current_user'));
        session()->put($cart_id, $cart_keeper);
        session()->put('current_user', $cart_id);
        $user_id = explode('-', session('current_user'))[1];
        $current_customer = '';
        if (explode('-', session('current_user'))[0] == 'wc') {
            $current_customer = 'Walking Customer';
        } else {
            $current = $this->customer->where('id', $user_id)->first();
            $current_customer = $current->name . ' (' . $current->mobile . ')';
        }

        return response()->json([
            'cart_nam' => session('cart_name'),
            'current_user' => session('current_user'),
            'current_customer' => $current_customer,
            'view' => view('admin-views.pos._cart', compact('cart_id'))->render()
        ]);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function change_cart(Request $request): RedirectResponse
    {

        session()->put('current_user', $request->cart_id);

        // admin.pos.index يتطلّب {type}؛ بدونه يفشل توليد الرابط فينهار تبديل
        // السلة. نحافظ على النوع الحالي إن أُرسل، وإلا 4 (مبيعات) كما تفعل
        // باقي المسارات في هذا المتحكّم.
        return redirect()->route('admin.pos.index', ['type' => $request->input('type', 4)]);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
  public function new_cart_id(Request $request): RedirectResponse
{
    $cart_id = 'wc-' . rand(10, 1000);
    session()->put('current_user', $cart_id);

    // Check if the cart_id is not already in the session's cart_name array
    if (!in_array($cart_id, session('cart_name') ?? [])) {
        session()->push('cart_name', $cart_id);
    }

    // Get the 'type' parameter from the request or define a default value (e.g., 4)
    $type = $request->input('type', 4); // Default to 4 if 'type' is not provided in the request

    // Redirect with the 'type' parameter
    return redirect()->route('admin.pos.index', ['type' => $type]);
}

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function get_cart_ids(Request $request): JsonResponse
    {
        $cart_id = session('current_user');
        $user_id = 0;
        $user_type = 'wc';
        if (Str::contains(session('current_user'), 'sc')) {
            $user_id = explode('-', session('current_user'))[1];
            $user_type = 'sc';
        }
        $cart = session($cart_id);
        $cart_keeper = [];
        // count() على قيمة غير مصفوفة يرمي خطأً قاتلًا؛ جلسة السلة قد تكون
        // فارغة أو تحمل قيمة أخرى قبل أول إضافة، فنتحقق من النوع أولًا.
        if (session()->has($cart_id) && is_countable($cart) && count($cart) > 0) {
            foreach ($cart as $cartItem) {
                $cart_keeper[] = $cartItem;
            }
        }
        session()->put(session('current_user'), $cart_keeper);
        $user_id = explode('-', session('current_user'))[1];
        $current_customer = '';
        if (explode('-', session('current_user'))[0] == 'wc') {
            $current_customer = 'Walking Customer';
        } else {
            $current = $this->customer->where('id', $user_id)->first();
            $current_customer = $current->name . ' (' . $current->mobile . ')';
        }
        return response()->json([
            'current_user' => session('current_user'),
            'cart_nam' => session('cart_name') ?? '',
            'current_customer' => $current_customer,
            'user_type' => $user_type,
            'user_id' => $user_id,
            'view' => view('admin-views.pos._cart', compact('cart_id'))->render()
        ]);
    }
    public function reservation_list_notification(Request $request, $type, $active)
{
    $adminId = Auth::guard('admin')->id();
    $admin = DB::table('admins')->where('id', $adminId)->first();

    // بيانات البحث والتصفية
    $search = $request->input('search');
    $fromDate = $request->input('from_date');
    $toDate = $request->input('to_date');
    $branch_id = $request->input('branch_id');
    $toNewDate = $toDate ? date('Y-m-d', strtotime("+1 day", strtotime($toDate))) : null;
    

    // استرجاع الـ seller_id المرتبط بالمشرف
    $sellerIds = AdminSeller::where('admin_id', $adminId)->pluck('seller_id');

    // استعلام الحجزات
    $reservations = ReserveProduct::where('type', $type)
        ->where(function ($query) use ($sellerIds, $adminId) {
            $query->whereIn('seller_id', $sellerIds)
                  ->orWhere('seller_id', $adminId);
        })
        ->where('active', $active)
        ->latest()
        ->with(['customer', 'seller']); // العلاقات المفترضة

    // تطبيق البحث
    if ($search) {
        $reservations->where(function ($query) use ($search) {
            $query->whereHas('customer', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })->orWhereHas('seller', function ($query) use ($search) {
                $query->where('f_name', 'like', "%{$search}%")
                      ->orWhere('l_name', 'like', "%{$search}%");
            });
        });
    }

    // تطبيق التصفية حسب التواريخ
    if ($fromDate && $toNewDate) {
        $reservations->whereBetween('created_at', [$fromDate, $toNewDate]);
    }

    // تصفية حسب الفرع

    // تنفيذ الاستعلام مع الترقيم
    $reservations = $reservations->paginate(Helpers::pagination_limit())
                                 ->appends($request->query());

    return view('admin-views.pos.reservations.list_notification', compact(
        'reservations', 'search', 'fromDate', 'toDate', 'type'
    ));
}


  public function cancelInstallment(Request $request, $installment_id)
    {
        // 1) Ensure the installment exists
        $inst = Installment::find($installment_id);
        if (! $inst) {
            Toastr::error(\App\CPU\translate('القسط غير موجود'));
            return redirect()->back();
        }

        try {
            DB::transaction(function() use ($inst, $installment_id) {
                $amount   = $inst->total_price;
                $order    = Order::findOrFail($inst->order_id);
                $customer = Customer::findOrFail($inst->customer_id);
                                $order = Order::findOrFail($inst->order_id);

                // find seller by the original seller_id, not the current Auth::id()
                $seller   = Seller::findOrFail($inst->seller_id);

                // 2) Delete any history_installment entries for this installment
                HistoryInstallment::where('id', $installment_id)
                    ->delete();

                // 3) Delete the installment itself
                $inst->delete();

                // 4) Locate & delete the original transaction (tran_type = 26)
                $origTrans = Transection::where('tran_type', 26)
                    ->where('customer_id', $customer->id)
                    ->where('seller_id',   $seller->id)
                    ->where('amount',      $amount)
                    ->latest()
                    ->first();
                $accountId  = null;
                $origDebit  = 0;
                $origCredit = 0;
                if ($origTrans) {
                    $accountId  = $origTrans->account_id;
                    $origDebit  = $origTrans->debit;
                    $origCredit = $origTrans->credit;
                    $origTrans->delete();
                }

                // 5) Revert the order’s collected amount
                // $order->decrement('transaction_reference', $amount);

                // 6) Revert seller’s commission & credit
                // $seller->decrement('commission', $amount);
                // $seller->decrement('credit',     $amount);

                // 7) Restore customer’s credit
                $customer->increment('credit', $amount);

                // 8) Create the reversal transaction (tran_type = 13)
                Transection::create([
                    'tran_type'    => 13,
                    'account_id'   => $accountId,
                    'amount'       => $amount,
                    'description'  => "إلغاء قسط رقم {$installment_id}",
                    'debit'        => $origCredit,
                    'credit'       => $origDebit,
                    'balance'      => $amount,
                    'date'         => now(),
                    'customer_id'  => $customer->id,
                    'order_id'=>$order->id,
                    'seller_id'    => $seller->id,
                    'img'          => $inst->img,
                ]);
            });

            Toastr::success(\App\CPU\translate('تم عكس القسط بنجاح'));
            return redirect()->back();

        } catch (\Exception $e) {
            \Log::error('Cancel installment failed: ' . $e->getMessage());
            Toastr::error(\App\CPU\translate('فشل في عكس القسط') . ': ' . $e->getMessage());
            return redirect()->back();
        }
    }

}
