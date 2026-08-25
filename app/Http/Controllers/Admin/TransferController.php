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


class TransferController extends Controller
{
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
            $query = $this->transection->where('tran_type','Transfer')->
                    where(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->orWhere('description', 'like', "%{$value}%");
                        }
                });
            $query_param = ['search' => $request['search']];
        }else
         {
            $query = $this->transection->where('tran_type','Transfer')
                ->when($from!=null, function($q) use ($request){
                    return $q->whereBetween('date', [$request['from'], $request['to']]);
            });

         }
        $transfers = $query->latest()->paginate(Helpers::pagination_limit())->appends(['search' => $request['search'],'from'=>$request['from'],'to'=>$request['to']]);
        return view('admin-views.transfer.add',compact('accounts','transfers','search','from','to'));
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */

public function store(Request $request): RedirectResponse
{
    $request->validate([
        'account_from_id' => 'required',
        'account_to_id' => 'required',
        'description' => 'required',
        'amount' => 'required|min:1',
    ]);

    // Handle image upload
    $img = null;
    if ($request->hasFile('img')) {
        $img = Helpers::update('shop/', null, 'png', $request->file('img'));
    }

    // Find the account to transfer from
    $acc_from = $this->account->find($request->account_from_id);

    // Check if there is enough balance
    if ($acc_from->balance < $request->amount) {
        Toastr::warning(\App\CPU\translate('لا يوجد مبلغ كافي في هذا الحساب'));
        return back();
    }

    // Create transaction for the account_from (debit)
    $transection_from = $this->transection;
    $transection_from->tran_type = 'Transfer';
    $transection_from->account_id = $request->account_from_id;
    $transection_from->amount = $request->amount;
    $transection_from->description = $request->description;
    $transection_from->debit = 1;
    $transection_from->credit = 0;
    $transection_from->balance = $acc_from->balance - $request->amount;
    $transection_from->date = $request->date;
    $transection_from->img = $img;
    $transection_from->seller_id = Auth::guard('admin')->id(); // Set seller_id from admin guard
    $transection_from->save();

    // Update account_from balance and total out
    $acc_from->total_out += $request->amount;
    $acc_from->balance -= $request->amount;
    $acc_from->save();

    // Find the account to transfer to
    $acc_to = $this->account->find($request->account_to_id);

    // Create transaction for the account_to (credit)
    $transection_to = $this->transection;
    $transection_to->tran_type = 'Transfer';
    $transection_to->account_id = $request->account_to_id;
    $transection_to->amount = $request->amount;
    $transection_to->description = $request->description;
    $transection_to->debit = 0;
    $transection_to->credit = 1;
    $transection_to->balance = $acc_to->balance + $request->amount;
    $transection_to->date = $request->date;
    $transection_to->img = $img;
    $transection_to->seller_id = Auth::guard('admin')->id(); // Set seller_id from admin guard
    $transection_to->save();

    // Update account_to balance and total in
    $acc_to->total_in += $request->amount;
    $acc_to->balance += $request->amount;
    $acc_to->save();

    Toastr::success(translate('تم تحويل المبلغ بنجاح'));
    return back();
}

}
