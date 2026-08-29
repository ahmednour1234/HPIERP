<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransactionSeller;
use App\Models\Seller;
use App\Models\Account;
use App\Models\Transection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Brian2694\Toastr\Facades\Toastr;

class TransactionSellerController extends Controller
{
    // List all transactions for the authenticated admin
public function index(Request $request)
{
    $adminId = Auth::guard('admin')->id();

    // جلب البائعين المرتبطين بالإداري الحالي
    $sellers = Seller::join('admin_sellers', 'admins.id', '=', 'admin_sellers.seller_id')
        ->where('admin_sellers.admin_id', $adminId)
        ->select('admins.*')
        ->get();

    // استلام فلاتر الطلب
    $sellerId  = $request->input('seller_id');
    $startDate = $request->input('start_date');
    $endDate   = $request->input('end_date');
    $search    = $request->input('search');

    // بداية الاستعلام بدون شروط
    // accounts مُحمَّلة مسبقًا: الجدول يعرض اسم الحساب لكل صف.
    $query = TransactionSeller::query()->with(['sellers', 'accounts']);

    // فلترة حسب البائع
    if (!empty($sellerId)) {
        $query->where('seller_id', $sellerId);
    }

    // فلترة بالتاريخ.
    // كانت تستخدم whereBetween على created_at وهو datetime، فتاريخ النهاية
    // يُقرأ 00:00:00 ويسقط يوم النهاية بالكامل، كما كانت تشترط إدخال
    // التاريخين معًا فيُتجاهل الفلتر لو أُدخل أحدهما. whereDate يقارن
    // الجزء التاريخي فقط، وكل شرط مستقل عن الآخر.
    if (!empty($startDate)) {
        $query->whereDate('created_at', '>=', $startDate);
    }
    if (!empty($endDate)) {
        $query->whereDate('created_at', '<=', $endDate);
    }

    // فلترة بالبحث (اسم البائع أو البريد)
    if (!empty($search)) {
        $query->whereHas('sellers', function ($q) use ($search) {
            $q->where('f_name', 'like', "%$search%")
              ->orWhere('l_name', 'like', "%$search%")
              ->orWhere('email', 'like', "%$search%");
        });
    }

    // جلب النتائج مع ترقيم الصفحات
    $transactions = $query->latest()->paginate(10)->appends($request->query());

    return view('admin-views.transaction_sellers.index', compact('transactions', 'sellers'));
}


public function status(Request $request, $id)
{
    $transactionseller = TransactionSeller::findOrFail($id);

    $amount = $transactionseller->amount;
    $seller = $transactionseller->sellers;
    $account = Account::findOrFail($transactionseller->account_id);

    // تحديث الحالة فقط
    $transactionseller->active = $request->input('active');
    $transactionseller->save();

    // إذا كانت الحالة = 1 نفذ عملية التحويل
    if ($request->input('active') == 1) {
        $transaction = new Transection;
        $transaction->tran_type = 26;
        $transaction->account_id = $account->id;
        $transaction->amount = $amount;
        $transaction->description = $transactionseller->note;
        $transaction->balance = $account->balance + $amount;
        $transaction->date = now();
        $transaction->seller_id = $seller->id;
        $transaction->img = $transactionseller->img;
        $transaction->save();

        // تحديث الحساب
        $account->total_in += $amount;
        $account->balance += $amount;
        $account->save();

        // خصم المبلغ من رصيد المندوب
        if ($seller) {
            $seller->credit -= $amount;
            $seller->save();
        }

        Toastr::success('تمت الموافقة على التحويل');
    } elseif ($request->input('active') == 2) {
        Toastr::warning('تم رفض عملية التحويل');
    }

    return redirect()->back();
}

}
