<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Factory;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\View;
use App\Models\Account;
use App\Models\Transection;
use function App\CPU\translate;
use App\CPU\Helpers;
use Illuminate\Support\Facades\DB;

class FactoryController extends Controller
{
    // عرض جميع المصانع
public function index(Request $request)
{
    $search = $request->input('search');

    $factories = Factory::query()
        ->when($search, function ($query) use ($search) {
            $query->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
        })
        ->latest()
        ->get();

    return view('admin-views.factories.index', compact('factories'));
}


    // عرض مصنع محدد
    public function show(Request $request, $id)
    {
        $factory = Factory::findOrFail($id);
              $accounts =Account::orderBy('id')->get();

        $from = $request->from;
        $to = $request->to;

        $query = Transection::where('factory_id',$id)
            ->when($from!=null, function($q) use ($request){
                return $q->whereBetween('date', [$request['from'], $request['to']]);
            });

        $transections = $query->latest()->paginate(Helpers::pagination_limit())->appends(['from'=>$request['from'],'to'=>$request['to']]);
        return view('admin-views.factories.show', compact('factory','transections','accounts'));
    }
// عرض فورم إنشاء مصنع
public function create()
{
    return view('admin-views.factories.create');
}

// عرض فورم تعديل مصنع
public function edit($id)
{
    $factory = Factory::findOrFail($id);
    return view('admin-views.factories.edit', compact('factory'));
}

    // إنشاء مصنع جديد
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => 'nullable|string|max:20',
            'email'   => 'nullable|email',
            'address' => 'nullable|string',
            'lang'    => 'nullable|string',
            'late'    => 'nullable|string',
        ]);

        Factory::create($validated);
        Toastr::success('تم إنشاء المصنع بنجاح', 'نجاح');

        return redirect()->back();
    }

    // تعديل بيانات مصنع
    public function update(Request $request, $id)
    {
        $factory = Factory::findOrFail($id);

        $validated = $request->validate([
            'name'    => 'sometimes|required|string|max:255',
            'phone'   => 'nullable|string|max:20',
            'email'   => 'nullable|email',
            'address' => 'nullable|string',
            'lang'    => 'nullable|string',
            'late'    => 'nullable|string',
        ]);

        $factory->update($validated);
        Toastr::success('تم تحديث بيانات المصنع بنجاح', 'تم التحديث');

        return redirect()->back();
    }

    // تبديل حالة التفعيل
    public function toggleActive($id)
    {
        $factory = Factory::findOrFail($id);
        $factory->active = !$factory->active;
        $factory->save();

        $status = $factory->active ? 'تم تفعيل المصنع' : 'تم إلغاء تفعيل المصنع';
        Toastr::info($status, 'تم التحديث');

        return redirect()->back();
    }
    public function update_credit(Request $request)
{
    $request->validate([
        'factory_id' => 'required',
        'amount' => 'required|numeric|min:0',
        'account_id' => 'required',
        'date' => 'required|date',
        'description' => 'nullable|string',
    ]);

    $factory = Factory::find($request->factory_id);
    $amount = $request->amount;
    $account = Account::find($request->account_id);
    $seller_id = auth('admin')->id(); // Get the admin ID from the authenticated admin user

    DB::beginTransaction(); // Start the transaction
     $img = null;
    if ($request->hasFile('img')) {
        $img = Helpers::update('shop/', null, 'png', $request->file('img'));
    }

    try {
        if ($account->balance >= $amount) {



            // Process the transaction
            $transaction = new Transection;
            $transaction->tran_type = 26;
            $transaction->account_id = $account->id;
            $transaction->amount = $amount;
            $transaction->description = $request->description;
            $transaction->debit = $factory->daen;
            $transaction->credit = $factory->maden+$amount;
            $transaction->balance = $account->balance + $amount;
            $transaction->date = $request->date;
            $transaction->factory_id = $request->factory_id;
            $transaction->seller_id = $seller_id;
            $transaction->img = $img;
            $transaction->save();

            // Update account balance
            $account->total_out += $amount;
            $account->balance -= $amount;
            $account->save();

            // Update customer credit
            $factory->maden += $amount;
            $factory->save();

            DB::commit(); // Commit the transaction

            Toastr::success(translate('تم دفع النقدية'));
        } else {
            Toastr::error(translate('الرصيد غير كافٍ')); // Not enough balance
        }
    } catch (\Exception $e) {
        DB::rollBack(); // Rollback transaction
        Toastr::error(translate('لم يتم دفع النقدية: ') . $e->getMessage()); // Show error message
    }

    return redirect()->back(); // Redirect back after processing
}
    public function update_debit(Request $request)
{
    $request->validate([
        'factory_id' => 'required',
        'amount' => 'required|numeric|min:0',
        'account_id' => 'required',
        'date' => 'required|date',
        'description' => 'nullable|string',
    ]);

    $factory = Factory::find($request->factory_id);
    $amount = $request->amount;
    $account = Account::find($request->account_id);
    $seller_id = auth('admin')->id(); // Get the admin ID from the authenticated admin user

    DB::beginTransaction(); // Start the transaction
     $img = null;
    if ($request->hasFile('img')) {
        $img = Helpers::update('shop/', null, 'png', $request->file('img'));
    }

    try {



            // Process the transaction
            $transaction = new Transection;
            $transaction->tran_type = 13;
            $transaction->account_id = $account->id;
            $transaction->amount = $amount;
            $transaction->description = $request->description;
            $transaction->debit = $factory->daen+$amount;
            $transaction->credit = $factory->maden;
            $transaction->balance = $account->balance + $amount;
            $transaction->date = $request->date;
            $transaction->factory_id = $request->factory_id;
            $transaction->seller_id = $seller_id;
            $transaction->img = $img;
            $transaction->save();

            // Update account balance
            $account->total_in += $amount;
            $account->balance += $amount;
            $account->save();

            // Update customer credit
            $factory->daen += $amount;
            $factory->save();

            DB::commit(); // Commit the transaction

            Toastr::success(translate('تم دفع النقدية'));
      
    } catch (\Exception $e) {
        DB::rollBack(); // Rollback transaction
        Toastr::error(translate('لم يتم دفع النقدية: ') . $e->getMessage()); // Show error message
    }

    return redirect()->back(); // Redirect back after processing
}
}
