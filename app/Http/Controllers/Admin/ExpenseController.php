<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\Transection;
use App\CPU\Helpers;
use Brian2694\Toastr\Facades\Toastr;
use function App\CPU\translate;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    use \App\Traits\ExportsCsv;

    public function __construct(
        private Transection $transection,
        private Account $account,
    ){}

    /**
     * @param Request $request
     * @return Application|Factory|View
     */
    public function add(Request $request): View|Factory|Application
    {
        $accounts = $this->account->orderBy('id','desc')->get();
        $search = $request['search'];
        $from = $request->from;
        $to = $request->to;

        if ($request->has('search')) {
            $key = explode(' ', $request['search']);
            $query = $this->transection->where('tran_type','Expense')->
                    where(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->orWhere('description', 'like', "%{$value}%");
                        }
                });
            $query_param = ['search' => $request['search']];
         }else
         {
            $query = $this->transection->where('tran_type','Expense')
                                ->when($from!=null, function($q) use ($request){
                                     return $q->whereBetween('date', [$request['from'], $request['to']]);
            });

         }
        $expenses = $query->orderBy('id','desc')->paginate(Helpers::pagination_limit())->appends(['search' => $request['search'],'from'=>$request['from'],'to'=>$request['to']]);
        return view('admin-views.expense.add',compact('accounts','expenses','search','from','to'));
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */

public function store(Request $request): RedirectResponse
{
    // Validate the incoming request
    $request->validate([
        'account_id' => 'required',
        'description' => 'required',
        'amount' => 'required|min:1',
    ]);

    // Handle image upload
    $img = null;
    if ($request->hasFile('img')) {
        $img = Helpers::update('shop/', null, 'png', $request->file('img'));
    }

    // Find the account
    $account = $this->account->find($request->account_id);

    // Check if account has sufficient balance
    if ($account->balance < $request->amount) {
        Toastr::warning(\App\CPU\translate('لا يوجد مبلغ كافي في هذا الحساب من أجل الصرف'));
        return back();
    }

    // Create a new transaction
    $transection = $this->transection;
    $transection->tran_type = 'Expense';
    $transection->account_id = $request->account_id;
    $transection->amount = $request->amount;
    $transection->description = $request->description;
    $transection->debit = 1;
    $transection->credit = 0;
    $transection->balance = $account->balance - $request->amount;
    $transection->date = $request->date;
    $transection->img = $img;

    // Set seller_id from authenticated admin user
    $transection->seller_id = Auth::guard('admin')->id();

    $transection->save();

    // Update the account's balance and total out
    $account->total_out += $request->amount;
    $account->balance -= $request->amount;
    $account->save();

    // Success message
    Toastr::success(translate('تم صرف المبلغ بنجاح'));
    return back();
}

/**
 * تعديل مصروف مُسجَّل.
 *
 * المصروف يغيّر رصيد الحساب، فأي تعديل يجب أن يعكس أثر القيد القديم أولًا
 * ثم يطبّق الجديد، وإلا انحرف رصيد الحساب عن الواقع. كل ذلك داخل معاملة
 * حتى لا يبقى نصف تعديل عند حدوث خطأ.
 */
public function edit($id): View|Factory|Application|RedirectResponse
{
    $expense = $this->transection->where('tran_type', 'Expense')->find($id);

    if (!$expense) {
        Toastr::error(translate('المصروف غير موجود'));
        return back();
    }

    $accounts = $this->account->orderBy('id', 'desc')->get();

    return view('admin-views.expense.edit', compact('expense', 'accounts'));
}

public function update(Request $request, $id): RedirectResponse
{
    $request->validate([
        'account_id'  => 'required',
        'description' => 'required',
        'amount'      => 'required|numeric|min:0.01',
    ]);

    $expense = $this->transection->where('tran_type', 'Expense')->find($id);

    if (!$expense) {
        Toastr::error(translate('المصروف غير موجود'));
        return back();
    }

    try {
        \DB::transaction(function () use ($request, $expense) {
            $oldAccount = $this->account->find($expense->account_id);
            $oldAmount  = (float) $expense->amount;

            // 1) عكس أثر القيد القديم على حسابه.
            if ($oldAccount) {
                $oldAccount->balance   += $oldAmount;
                $oldAccount->total_out -= $oldAmount;
                $oldAccount->save();
            }

            // 2) تطبيق القيد الجديد على الحساب الجديد (قد يكون نفسه).
            $newAccount = $this->account->find($request->account_id);
            $newAmount  = (float) $request->amount;

            if (!$newAccount) {
                throw new \RuntimeException('الحساب غير موجود');
            }

            // يُعاد تحميل الرصيد بعد العكس أعلاه لو كان نفس الحساب.
            $newAccount->refresh();

            if ($newAccount->balance < $newAmount) {
                throw new \RuntimeException('لا يوجد مبلغ كافي في هذا الحساب من أجل الصرف');
            }

            $newAccount->balance   -= $newAmount;
            $newAccount->total_out += $newAmount;
            $newAccount->save();

            if ($request->hasFile('img')) {
                $expense->img = Helpers::update('shop/', $expense->img, 'png', $request->file('img'));
            }

            $expense->account_id  = $request->account_id;
            $expense->amount      = $newAmount;
            $expense->description = $request->description;
            $expense->date        = $request->date ?: $expense->date;
            $expense->balance     = $newAccount->balance;
            $expense->save();
        });
    } catch (\Throwable $e) {
        Toastr::warning(translate($e->getMessage()));
        return back()->withInput();
    }

    Toastr::success(translate('تم تعديل المصروف بنجاح'));
    return redirect()->route('admin.account.add-expense');
}

/**
 * حذف مصروف ورد المبلغ إلى الحساب.
 *
 * الحذف وحده لا يكفي: القيد كان قد خصم من الرصيد، فلا بد من رده وإلا بقي
 * الحساب ناقصًا بقيمة مصروف لم يعد له وجود.
 */
public function delete($id): RedirectResponse
{
    $expense = $this->transection->where('tran_type', 'Expense')->find($id);

    if (!$expense) {
        Toastr::error(translate('المصروف غير موجود'));
        return back();
    }

    \DB::transaction(function () use ($expense) {
        $account = $this->account->find($expense->account_id);
        $amount  = (float) $expense->amount;

        if ($account) {
            $account->balance   += $amount;
            $account->total_out -= $amount;
            $account->save();
        }

        $expense->delete();
    });

    Toastr::success(translate('تم حذف المصروف ورد المبلغ إلى الحساب'));
    return back();
}

/**
 * دفتر المصروف كملف اكسيل، بنفس فلاتر الشاشة وعلى كامل النتيجة لا على
 * الصفحة المعروضة وحدها.
 */
public function export(Request $request)
{
    $query = $this->transection->where('tran_type', 'Expense')->with(['account', 'seller']);

    if ($request->filled('search')) {
        $key = explode(' ', $request->input('search'));
        $query->where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('description', 'like', "%{$value}%");
            }
        });
    }

    if ($request->filled('from')) {
        $query->whereDate('date', '>=', $request->input('from'));
    }
    if ($request->filled('to')) {
        $query->whereDate('date', '<=', $request->input('to'));
    }

    $rows = $query->orderByDesc('id')->get()->map(fn ($t) => [
        'رقم القيد' => $t->id,
        'التاريخ'   => $t->date,
        'الحساب'    => $t->account->account ?? '',
        'الكاتب'    => $t->seller->email ?? '',
        'المبلغ'    => $t->amount,
        'الوصف'     => $t->description,
        'الرصيد'    => $t->balance,
    ]);

    return $this->streamCsvRows($rows, $this->exportFilename('expenses'));
}

}
