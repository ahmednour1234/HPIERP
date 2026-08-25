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
use App\Models\CurrentOrder;
use App\Models\Coupon;
use App\Models\Transection;
use App\Models\Account;
use App\Models\OrderDetail;
use App\Models\Customer;
use App\Models\HistoryInstallment;
use App\Models\ReserveProduct;
use App\Models\CurrentReserveProduct;
use App\Models\ReserveProductNotification;
use App\Models\StockOrder;
use App\Models\Seller;
use App\Models\Stock;
use App\Models\SellerPrice;
use App\Models\Region;
use App\Models\CustomerPrice;
use App\Models\AdminSeller;
use App\Models\Transaction;
use App\Models\TransactionSeller;
use Illuminate\Support\Facades\Auth;
use App\CPU\Helpers;
use Brian2694\Toastr\Facades\Toastr;
use function App\CPU\translate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class NotificationController extends Controller
{
    public function __construct(
        private Category $category,
        private Product $product,
        private Order $order,
        private Coupon $coupon,
        private Transection $transection,
        private TransactionSeller $TransactionSeller,
        private Region $regions,
        private Account $account,
        private OrderDetail $order_details,
        private StockOrder $stock_order,
        private Customer $customer,
        private CurrentReserveProduct $current_reserve_products,
        private HistoryInstallment $installment,
        private ReserveProduct $reserveProduct,
        private ReserveProductNotification $reserveProductNotification,
    ){}
public function listItems(Request $request)
{
    $adminId = Auth::guard('admin')->id();

        $sellerId = AdminSeller::where('admin_id', $adminId)->pluck('seller_id');

 $refundOrders = $this->order
        ->where('notification', 1)
        ->where('type', 7)
        ->whereIn('owner_id', $sellerId)
        ->orderBy('created_at', 'desc')
        ->get();

    $orders = $this->order
        ->where('notification', 1)
        ->where('type', 4)
        ->whereIn('owner_id', $sellerId)
        ->orderBy('created_at', 'desc')
        ->get();

    $installments = $this->installment
        ->where('notification', 1)
        ->whereIn('seller_id', $sellerId)
        ->orderBy('created_at', 'desc')
        ->get();
        $TransactionSellers = $this->TransactionSeller
        ->whereIn('seller_id', $sellerId)
        ->orderBy('created_at', 'desc')
        ->get();

    $reserveProducts = $this->reserveProduct
        ->where('notification', 1)
        ->where('type', 4)
        ->whereIn('seller_id', $sellerId)
        ->orderBy('created_at', 'desc')
        ->get();
         $reReserveProducts = $this->reserveProduct
        ->where('notification', 1)
        ->where('type', 7)
        ->whereIn('seller_id', $sellerId)
        ->orderBy('created_at', 'desc')
        ->get();

    // Count the items
    $orderCount = $orders->count();
    $refundOrderCount = $refundOrders->count();
    $installmentCount = $installments->count();
    $reserveProductCount = $reserveProducts->count();
    $reReserveProductCount = $reReserveProducts->count();
    $TransactionSellersCount = $TransactionSellers->count();

    // Total notification count
    $totalNotificationCount = $orderCount + $refundOrderCount + $installmentCount + $reserveProductCount + $reReserveProductCount+$TransactionSellersCount;

    // Pass data and counts to the view
    return view('admin-views.Notification.index', compact(
        'orders',
        'refundOrders',
        'installments',
        'reserveProducts',
        'reReserveProducts',
        'orderCount',
        'refundOrderCount',
        'installmentCount',
        'reserveProductCount',
        'reReserveProductCount',
        'totalNotificationCount',
        'TransactionSellersCount',
        'TransactionSellers'
    ));
}

public function markAsRead($id, $type): RedirectResponse
{
    if ($type === 'order') {
        $notification = $this->order->where('id', $id)->first();
    } elseif ($type === 'installment') {
        $notification = $this->installment->where('id', $id)->first();
    } elseif ($type === 'reserveProduct') {
        $notification = $this->reserveProduct->where('id', $id)->first();
    }else{
            return redirect()->route('admin.admin.notifications.show', ['id' => $id, 'type' => $type]);

    }

    // Update the notification column to 0
// Update the notification column to 0 without mass assignment
if ($notification && $notification->notification == 1) {
    $notification->notification = 0;
    $notification->save();
}

    // Redirect to the item detail page
    return redirect()->route('admin.admin.notifications.show', ['id' => $id, 'type' => $type]);
}
public function showItemById($id, $type): Factory|View|Application
{
    // Mark the notification as read
    $this->markAsRead($id, $type);

    // Initialize variables
    $notification = null;
    $search = request('search', '');
    $fromDate = request('from_date', '');
    $toDate = request('to_date', '');
    $regions = $this->regions->get();
    $regionId = 1;

    // Determine the type and fetch the corresponding data
    switch ($type) {
        case 'order':
            $notification = $this->order->with(['customer', 'seller'])->find($id);
$orderAmountSum=0;
$collectedCashSum=0;
$productCount=0;
$quantitySum=0;

            if ($notification) {
                $orders = Order::where('id', $id)
                    ->when($fromDate && $toDate, fn($query) => $query->whereBetween('created_at', [$fromDate, $toDate]))
                    ->paginate(Helpers::pagination_limit())
                    ->appends(compact('search', 'fromDate', 'toDate'));

                return view('admin-views.pos.order.list', compact('orders', 'search', 'fromDate', 'toDate', 'regions', 'regionId','orderAmountSum','collectedCashSum','productCount','quantitySum'));
            }
            break;

        case 'installment':
            $notification = $this->installment->with(['customer', 'seller'])->find($id);
$totalAmount=0;

            if ($notification) {
                $installments = HistoryInstallment::where('id', $id)
                    ->when($fromDate && $toDate, fn($query) => $query->whereBetween('created_at', [$fromDate, $toDate]))
                    ->paginate(Helpers::pagination_limit())
                    ->appends(compact('search', 'fromDate', 'toDate'));

                return view('admin-views.pos.installment.list', compact('installments', 'search', 'fromDate', 'toDate', 'regions', 'regionId','totalAmount'));
            }
            break;

        case 'reserveProduct':
            $notification = $this->reserveProduct->with(['customer', 'seller'])->find($id);

            if ($notification) {
                $reservations = ReserveProduct::where('id', $id)
                    ->when($fromDate && $toDate, fn($query) => $query->whereBetween('created_at', [$fromDate, $toDate]))
                    ->paginate(Helpers::pagination_limit())
                    ->appends(compact('search', 'fromDate', 'toDate'));

                return view('admin-views.pos.reservations.list_notification', compact('reservations', 'search', 'fromDate', 'toDate', 'regions', 'regionId'));
            }
            break;

        case 'TransactionSeller':
            $notification = $this->TransactionSeller->find($id);
$sellers=Seller::all();
            if ($notification) {
                $transactions = TransactionSeller::where('id', $id)
                    ->when($fromDate && $toDate, fn($query) => $query->whereBetween('created_at', [$fromDate, $toDate]))
                    ->paginate(Helpers::pagination_limit())
                    ->appends(compact('search', 'fromDate', 'toDate'));

                return view('admin-views.transaction_sellers.index', compact('transactions', 'search', 'fromDate', 'toDate', 'regions', 'regionId','sellers'));
            }
            break;
    }

    // Handle case when notification is not found
    Toastr::error(translate('Notification not found'));
    return redirect()->back();
}
}