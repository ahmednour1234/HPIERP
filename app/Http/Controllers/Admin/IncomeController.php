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


class IncomeController extends Controller
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
            $query = $this->transection->where('tran_type','Income')->
                    where(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->orWhere('description', 'like', "%{$value}%");
                        }
                });
            $query_param = ['search' => $request['search']];
        }else
         {
            $query = $this->transection->where('tran_type','Income')
                                ->when($from!=null, function($q) use ($request){
                                     return $q->whereBetween('date', [$request['from'], $request['to']]);
            });

         }
        $incomes = $query->latest()->paginate(Helpers::pagination_limit())->appends(['search' => $request['search'],'from'=>$request['from'],'to'=>$request['to']]);
        return view('admin-views.income.add',compact('accounts','incomes','search','from','to'));
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */


public function store(Request $request): RedirectResponse
{
    $request->validate([
        'account_id' => 'required',
        'description' => 'required',
        'amount' => 'required|numeric|min:1',
    ]);

    $img = null;
    if ($request->hasFile('img')) {
        $img = Helpers::update('shop/', null, 'png', $request->file('img'));
    }

    $account = $this->account->find($request->account_id);
    $transection = $this->transection;
    $transection->tran_type = 'Income';
    $transection->account_id = $request->account_id;
    $transection->amount = $request->amount;
    $transection->description = $request->description;
    $transection->balance = $account->balance + $request->amount;
    $transection->date = $request->date;
    $transection->img = $img;
    
    // Set the seller_id from the authenticated admin user
    $transection->seller_id = Auth::guard('admin')->id();

    $transection->save();

    $account->total_in += $request->amount;
    $account->balance += $request->amount;
    $account->save();

    Toastr::success(translate('New Income Added successfully'));
    return back();
}


}
