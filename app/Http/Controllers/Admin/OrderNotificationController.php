<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\CPU\Helpers;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderNotification;
use App\Models\OrderDetailNotification;
use App\Models\Product;
use App\Models\Customer;
use App\Models\CurrentOrder;
use App\Models\ReserveProduct;
use App\Models\Stock;
use App\Models\Transection;
use App\Models\SellerPrice;
use App\Models\Account;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;


class OrderNotificationController extends Controller
{
 
    public function __construct(
        private Product $product,
        private Order $order,
        private CurrentOrder $current_order,
        private OrderDetail $order_detail,
        private Stock $transection,
                private Stock $account,

  
    ){}

   public function index()
    {
        // Retrieve all order notifications with pagination, filtered by order_type and active status
        $notifications = Order::where('type', 4)->where('active', 0)->paginate(10);

        // Pass the notifications to the Blade view
        return view('admin-views.pos.ordernotification.index', ['notifications' => $notifications]);
    }

    public function search(Request $request)
    {
        $search = $request->input('search');

        // Perform search query using relationships and filtered by order_type and active status
        $notifications = \App\Models\Order::with(['seller', 'customer'])
            ->where('type', 4)
            ->where('active', 0)
            ->where(function ($query) use ($search) {
                $query->whereHas('seller', function ($query) use ($search) {
                    $query->where('f_name', 'LIKE', "%{$search}%")
                          ->orWhere('l_name', 'LIKE', "%{$search}%");
                })
                ->orWhereHas('customer', function ($query) use ($search) {
                    $query->where('name', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('admin-views.pos.ordernotification.index', compact('notifications'));
    }

    public function show($order_id)
    {
        // Retrieve order notification by order_id
        $order = Order::where('type', 4)->where('active', 0)->findOrFail($order_id);

        // Retrieve all order details associated with this order
        $orderDetails = OrderDetail::where('order_id', $order_id)->get();
        $active = $order ? $order->active : 0; // Default to 0 if not found

        // Retrieve all products
        $products = Product::all();

        // Pass the order details and products to the Blade view
        return view('admin-views.pos.ordernotification.details', [
            'order' => $order,
            'orderDetails' => $orderDetails,
            'products' => $products,
            'active' => $active
        ]);
    }
  public function placeOrder(Request $request): RedirectResponse
    {
        // Validate if cart exists and is not empty
        if (!$request->has('cart') || count(json_decode($request->cart, true)) < 1) {
            return redirect()->back()->with('message', 'Cart empty');
        }

        // Retrieve request data
        $user_id = $request->user_id;
        $order_id = $request->order_id;
        $coupon_discount = $request->coupon_discount ?? 0;
        $total_tax_amount = $request->total_tax ?? 0;
        $total_price = $request->subtotal ?? 0;
        $ext_discount = $request->extra_discount ?? 0;
        $grand_total = $request->total ?? 0;
        $order_type = $request->order_type;
        $collected_cash = $request->collected_cash ?? $grand_total;

        // Check if an order already exists
        $order = $this->order->where('id', $order_id)->first();

        // If order exists, update existing order; otherwise, create a new one
        if ($order) {
            $this->updateOrder($order, $total_tax_amount, $total_price, $ext_discount, $coupon_discount, $collected_cash, $order_type);
        } else {
            $orderData = [
                'user_id' => $user_id,
                'total_tax' => $total_tax_amount,
                'order_amount' => $total_price,
                'extra_discount' => $ext_discount,
                'coupon_discount_amount' => $coupon_discount,
                'collected_cash' => $collected_cash,
                'type' => $order_type,
                'active' => 1, // Set active to 1 for new order
            ];

            // Create new order using helper function
            $order = $this->createOrder($this->order, $orderData);
        }

        // Update order details
        $cartItems = json_decode($request->cart, true);
        $this->updateOrderDetails($order->id, $cartItems);

        // Perform additional transactions and updates

        return redirect()->route('admin.dashboard')->with('message', 'Order placed successfully')->with('order_id', $order->id);
    }

    private function createOrder($orderModel, $orderData): Order
    {
        return $orderModel->create($orderData);
    }

private function updateOrder(Order $order, $total_tax_amount, $total_price, $ext_discount, $coupon_discount, $collected_cash, $order_type)
{
    $order->update([
        'total_tax' => $total_tax_amount,
        'order_amount' => $total_price,
        'extra_discount' => $ext_discount,
        'coupon_discount_amount' => $coupon_discount,
        'collected_cash' => $collected_cash,
        'active' => 1, // Set active to 1 for existing order
    ]);

    // Update current_order if needed
    $current_order = $this->current_order->where('id', $order->id)->first();
    if ($current_order) {
        $current_order->update([
            'total_tax' => $total_tax_amount,
            'order_amount' => $total_price,
            'extra_discount' => $ext_discount,
            'coupon_discount_amount' => $coupon_discount,
            'collected_cash' => $collected_cash,
        ]);
    }
}

private function updateOrderDetails($order_id, $cartItems)
{
    // Get existing order details keyed by product_id
    $existing_order_details = $this->order_detail->where('order_id', $order_id)->get()->keyBy('product_id');

    foreach ($cartItems as $item) {
        $product_id = $item['id'];
        $quantity = $item['quantity'];

        // Find the product
        $product = $this->product->find($product_id);

        // Calculate price based on quantity and selling price
        $price = $quantity * $product->selling_price;

        // Check if product already exists in order details
        if ($existing_order_details->has($product_id)) {
            $existing_order_detail = $existing_order_details->get($product_id);
            // Update existing order detail
            $existing_order_detail->update([
                'quantity' => $quantity,
                'price' => $price,
                'product_details' => $product, // Assuming 'product_details' is a field in your OrderDetail model
                // You can update other fields if needed
            ]);
        } else {
            // Create new order detail for new product
            $this->order_detail->create([
                'order_id' => $order_id,
                'product_id' => $product_id,
                'quantity' => $quantity,
                'price' => $price,
                'product_details' => $product, // Assuming 'product_details' is a field in your OrderDetail model
                // Add other fields as needed
            ]);
        }
    }
}
public function Productunlike(Request $request)
{
    // Validate request inputs
    $request->validate([
        'user_id' => 'nullable|exists:customers,id',
        'start_date' => 'nullable|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
    ]);

    $customerId = $request->input('user_id');
    $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
    $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;
    $customers = Customer::all(); // Fetch all customers to display in the form

    // Step 1: Fetch products that have been sold within the date range (order_details table)
    $soldProductIds = OrderDetail::when($customerId, function($query) use ($customerId) {
            return $query->whereHas('order', function ($q) use ($customerId) {
                $q->where('user_id', $customerId);
            });
        })
        ->when($startDate, function($query) use ($startDate) {
            return $query->where('created_at', '>=', $startDate);
        })
        ->when($endDate, function($query) use ($endDate) {
            return $query->where('created_at', '<=', $endDate);
        })
        ->pluck('product_id')
        ->toArray();

    // Step 2: If no sold product IDs found, ensure we avoid SQL issues
    if (empty($soldProductIds)) {
        $soldProductIds = [-1]; // This ensures whereNotIn does not fail
    }

    // Step 3: Fetch products that are **not sold** (not present in the soldProductIds)
    $unsoldProducts = Product::whereIn('id', $soldProductIds)->get();

 // Step 4: Fetch the last sale date for products in the `order_details` table
$lastSaleDates = OrderDetail::select('product_id', \DB::raw('MAX(created_at) as last_sale_date'))
    ->whereIn('product_id', $soldProductIds)
    ->groupBy('product_id')
    ->pluck('last_sale_date', 'product_id')
    ->toArray();

// Step 5: Attach last sale date and stagnation period to each product
$unsoldProducts->each(function ($product) use ($lastSaleDates) {
    // Retrieve last sale date for the current product
    $lastSaleDate = $lastSaleDates[$product->id] ?? null;

    // Debugging step: check what is being assigned
    // You can use this to check the value of $lastSaleDate for specific product IDs
    // dd($product->id, $lastSaleDate); 

    // Assign the last sale date to the product object
    $product->last_sale_date = $lastSaleDate ? Carbon::parse($lastSaleDate) : null;

    // Calculate the stagnation period in days, if there is a valid last sale date
    $product->stagnation_period = $lastSaleDate ? now()->diffInDays(Carbon::parse($lastSaleDate)) : null;
});

    // Step 6: Return the view with unsold products and customer data (no pagination)
    return view('admin-views.pos.ordernotification.productunlike', [
        'products' => $unsoldProducts,
        'customers' => $customers,
    ]);
}






}
