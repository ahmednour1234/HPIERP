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
/** Rows per notification list. */
private const notificationPageSize = 15;

public function listItems(Request $request)
{
    $adminId  = Auth::guard('admin')->id();
    $sellerId = AdminSeller::where('admin_id', $adminId)->pluck('seller_id');

    $perPage = self::notificationPageSize;

    // Each list gets its own page parameter so paging one does not reset the
    // others. The relations are eager loaded because the view reads
    // ->customer and ->seller on every row — without this each row costs two
    // extra queries.
    $orders = $this->order
        ->with(['customer:id,name', 'seller:id,f_name,l_name'])
        ->where('notification', 1)
        ->where('type', 4)
        ->whereIn('owner_id', $sellerId)
        ->latest('created_at')
        ->paginate($perPage, ['*'], 'orders_page')
        ->withQueryString();

    $refundOrders = $this->order
        ->with(['customer:id,name', 'seller:id,f_name,l_name'])
        ->where('notification', 1)
        ->where('type', 7)
        ->whereIn('owner_id', $sellerId)
        ->latest('created_at')
        ->paginate($perPage, ['*'], 'refunds_page')
        ->withQueryString();

    $installments = $this->installment
        ->with(['customer:id,name', 'seller:id,f_name,l_name'])
        ->where('notification', 1)
        ->whereIn('seller_id', $sellerId)
        ->latest('created_at')
        ->paginate($perPage, ['*'], 'installments_page')
        ->withQueryString();

    $TransactionSellers = $this->TransactionSeller
        ->with(['sellers:id,f_name,l_name,email'])
        ->whereIn('seller_id', $sellerId)
        ->latest('created_at')
        ->paginate($perPage, ['*'], 'transfers_page')
        ->withQueryString();

    $reserveProducts = $this->reserveProduct
        ->with(['customer:id,name', 'seller:id,f_name,l_name'])
        ->where('notification', 1)
        ->where('type', 4)
        ->whereIn('seller_id', $sellerId)
        ->latest('created_at')
        ->paginate($perPage, ['*'], 'reservations_page')
        ->withQueryString();

    $reReserveProducts = $this->reserveProduct
        ->with(['customer:id,name', 'seller:id,f_name,l_name'])
        ->where('notification', 1)
        ->where('type', 7)
        ->whereIn('seller_id', $sellerId)
        ->latest('created_at')
        ->paginate($perPage, ['*'], 'rereservations_page')
        ->withQueryString();

    // total(), not count(): count() would only describe the current page.
    $orderCount              = $orders->total();
    $refundOrderCount        = $refundOrders->total();
    $installmentCount        = $installments->total();
    $reserveProductCount     = $reserveProducts->total();
    $reReserveProductCount   = $reReserveProducts->total();
    $TransactionSellersCount = $TransactionSellers->total();

    $totalNotificationCount = $orderCount + $refundOrderCount + $installmentCount
        + $reserveProductCount + $reReserveProductCount + $TransactionSellersCount;

    return view('admin-views.Notification.index', compact(
        'orders',
        'refundOrders',
        'installments',
        'reserveProducts',
        'reReserveProducts',
        'TransactionSellers',
        'orderCount',
        'refundOrderCount',
        'installmentCount',
        'reserveProductCount',
        'reReserveProductCount',
        'TransactionSellersCount',
        'totalNotificationCount'
    ));
}

/**
 * المناديب التابعون للحساب الحالي.
 *
 * كل شاشات الإشعارات تعرض بيانات هؤلاء فقط، فيلزم فحص الملكية قبل عرض أي
 * سجل بمعرّفه.
 */
private function ownedSellerIds(): array
{
    $adminId = Auth::guard('admin')->id();

    return AdminSeller::where('admin_id', $adminId)
        ->pluck('seller_id')
        ->map(fn ($v) => (int) $v)
        ->all();
}

/**
 * هل السجل يخص أحد مناديب الحساب الحالي؟
 *
 * كانت السجلات تُجلب بالمعرّف وحده، فيكفي تغيير الرقم في الرابط لعرض بيانات
 * مندوب تابع لحساب آخر: اسم العميل والصيدلية والمبلغ. الفحص هنا يمنع ذلك
 * في كل الأنواع دفعة واحدة.
 */
private function ownsRecord($record, string $type): bool
{
    if (!$record) {
        return false;
    }

    $owned = $this->ownedSellerIds();

    // عمود المالك يختلف بين الجداول.
    $ownerId = match ($type) {
        'order'             => $record->owner_id,
        'installment'       => $record->seller_id,
        'reserveProduct'    => $record->seller_id,
        'TransactionSeller' => $record->seller_id,
        default             => null,
    };

    return $ownerId !== null && in_array((int) $ownerId, $owned, true);
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

    // لا يُعلَّم كمقروء سجل لا يخص هذا الحساب.
    if (!$this->ownsRecord($notification, $type)) {
        return redirect()->route('admin.admin.notifications.listItems');
    }

    // Update the notification column to 0 without mass assignment
if ($notification && $notification->notification == 1) {
    $notification->notification = 0;
    $notification->save();
}

    // Redirect to the item detail page
    return redirect()->route('admin.admin.notifications.show', ['id' => $id, 'type' => $type]);
}
// RedirectResponse is in the union because the not-found paths below redirect;
// without it PHP threw a TypeError and the page answered 500.
public function showItemById($id, $type): Factory|View|Application|RedirectResponse
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

            // فحص الملكية: بدونه يكفي تغيير المعرّف في الرابط لعرض بيانات
            // مندوب تابع لحساب آخر.
            if (!$this->ownsRecord($notification, 'order')) {
                $notification = null;
            }
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

            // فحص الملكية: بدونه يكفي تغيير المعرّف في الرابط لعرض بيانات
            // مندوب تابع لحساب آخر.
            if (!$this->ownsRecord($notification, 'installment')) {
                $notification = null;
            }

            // القالب يعرض بطاقتَي ملخص. كان collectedCashSum غير مُمرَّر
            // فتنهار الصفحة بـ Undefined variable. الصفحة تعرض سجلًا واحدًا،
            // فالملخص هو قيمة هذا السجل نفسه.
            $totalAmount      = (float) optional($notification)->total_price;
            $collectedCashSum = (float) optional(optional($notification)->order)->collected_cash;

            if ($notification) {
                $installments = HistoryInstallment::where('id', $id)
                    ->when($fromDate && $toDate, fn($query) => $query->whereBetween('created_at', [$fromDate, $toDate]))
                    ->paginate(Helpers::pagination_limit())
                    ->appends(compact('search', 'fromDate', 'toDate'));

                return view('admin-views.pos.installment.list', compact('installments', 'search', 'fromDate', 'toDate', 'regions', 'regionId', 'totalAmount', 'collectedCashSum'));
            }
            break;

        case 'reserveProduct':
            $notification = $this->reserveProduct->with(['customer', 'seller'])->find($id);

            // فحص الملكية: بدونه يكفي تغيير المعرّف في الرابط لعرض بيانات
            // مندوب تابع لحساب آخر.
            if (!$this->ownsRecord($notification, 'reserveProduct')) {
                $notification = null;
            }

            // القالب يفحص $type لتحديد ما يعرضه، ولم يكن مُمرَّرًا فتنهار
            // الصفحة. نوع الحجز مأخوذ من السجل نفسه.
            $type = optional($notification)->type;

            if ($notification) {
                $reservations = ReserveProduct::where('id', $id)
                    ->when($fromDate && $toDate, fn($query) => $query->whereBetween('created_at', [$fromDate, $toDate]))
                    ->paginate(Helpers::pagination_limit())
                    ->appends(compact('search', 'fromDate', 'toDate'));

                return view('admin-views.pos.reservations.list_notification', compact('reservations', 'search', 'fromDate', 'toDate', 'regions', 'regionId', 'type'));
            }
            break;

        case 'TransactionSeller':
            $notification = $this->TransactionSeller->find($id);

            // فحص الملكية: بدونه يكفي تغيير المعرّف في الرابط لعرض بيانات
            // مندوب تابع لحساب آخر.
            if (!$this->ownsRecord($notification, 'TransactionSeller')) {
                $notification = null;
            }
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