<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Supplier;
use App\Models\Product;
use Brian2694\Toastr\Facades\Toastr;
use App\CPU\Helpers;
use App\Models\Account;
use App\Models\Purchase;
use App\Models\Transection;
use App\Models\Order;
use App\Models\Installment;
use App\Models\HistoryInstallment;
use function App\CPU\translate;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    public function __construct(
        private Supplier $supplier,
        private Product $product,
        private Transection $transection,
        private Purchase $purchase,
        private Account $account,
        private Order $order,
        private Installment $installment,
        private HistoryInstallment $history_installment
    ){}

    /**
     * @return Application|Factory|View
     */
    public function index(): View|Factory|Application
    {
        return view('admin-views.supplier.index');
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required',
            'mobile'=> 'required|unique:suppliers',
            'email' => 'required|email|unique:suppliers',
            'state' => 'required',
            'city' => 'required',
            'zip_code' => 'required',
            'address' => 'required',

        ]);

        if (!empty($request->file('image'))) {
            $image_name =  Helpers::upload('supplier/', 'png', $request->file('image'));
        } else {
            $image_name = 'def.png';
        }

        $supplier = $this->supplier;
        $supplier->name = $request->name;
        $supplier->mobile = $request->mobile;
        $supplier->email = $request->email;
        $supplier->image = $image_name;
        $supplier->state = $request->state;
        $supplier->city = $request->city;
        $supplier->due_amount = $request->balance;
        $supplier->zip_code = $request->zip_code;
        $supplier->address = $request->address;

        $supplier->save();

        Toastr::success(translate('Supplier Added successfully'));
        return redirect()->route('admin.supplier.list');
    }

    /**
     * @param Request $request
     * @return Application|Factory|View
     */
    public function list(Request $request): Factory|View|Application
    {
        $query_param = [];
        $search = $request['search'];
        if ($request->has('search')) {
            $key = explode(' ', $request['search']);
            $suppliers = $this->supplier->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%")
                        ->orWhere('mobile', 'like', "%{$value}%");
                }
            });
            $query_param = ['search' => $request['search']];
        } else {
            $suppliers = $this->supplier;
        }
            $total_due_amount = $this->supplier->sum('due_amount'); 
            $total_credit = $this->supplier->sum('credit'); 
        $suppliers = $suppliers->latest()->paginate(Helpers::pagination_limit())->appends($query_param);
                $accounts = $this->account->orderBy('id')->get();

        return view('admin-views.supplier.list',compact('suppliers','search','total_due_amount','accounts','total_credit'));
    }

    /**
     * @param Request $request
     * @param $id
     * @return Application|Factory|View
     */
    public function view(Request $request, $id): Factory|View|Application
    {
         $supplier = $this->supplier->where('id', $id)->first();
    if (isset($supplier)) {
        $query_param = [];
        $search = $request['search'];
        $orderType = $request['order_type'];
        $startDate = $request['start_date'];
        $endDate = $request['end_date'];

        $orders = $this->purchase->where(['supplier_id' => $id]);

        if ($request->has('search')) {
            $key = explode(' ', $request['search']);
            $orders->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->where('id', 'like', "%{$value}%");
                }
            });
        }

        if ($orderType) {
            $orders->where('type', $orderType);
        }

        if ($startDate) {
            $orders->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $orders->where('created_at', '<=', $endDate);
        }

        $query_param = array_merge($query_param, [
            'search' => $search,
            'order_type' => $orderType,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
}
        $orders = $orders->latest()->paginate(Helpers::pagination_limit())->appends($query_param);
        $supplier = $this->supplier->find($id);
        return view('admin-views.supplier.view',compact('supplier','orders','search'));
    }

    /**
     * @param Request $request
     * @param $id
     * @return Application|Factory|View
     */
    public function product_list(Request $request, $id): Factory|View|Application
    {
        $supplier = $this->supplier->find($id);
        $query_param = [];
        $search = $request['search'];
        $sort_oqrderQty= $request['sort_oqrderQty'];
        if ($request->has('search')) {
            $key = explode(' ', $request['search']);
            $query = $this->product->where('supplier_id',$id)->
                where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('name', 'like', "%{$value}%")
                            ->orWhere('product_code', 'like', "%{$value}%");
                    }
            });
            $query_param = ['search' => $request['search']];
        } else {
            $query = $this->product->where('supplier_id',$id)
                ->when($request->sort_oqrderQty=='quantity_asc', function($q) use ($request){
                    return $q->orderBy('quantity', 'asc');
                })
                ->when($request->sort_oqrderQty=='quantity_desc', function($q) use ($request){
                    return $q->orderBy('quantity', 'desc');
                })
                ->when($request->sort_oqrderQty=='order_asc', function($q) use ($request){
                    return $q->orderBy('order_count', 'asc');
                })
                ->when($request->sort_oqrderQty=='order_desc', function($q) use ($request){
                    return $q->orderBy('order_count', 'desc');
                })
                ->when($request->sort_oqrderQty=='default', function($q) use ($request){
                    return $q->orderBy('id');
                });
        }

        $products = $query->latest()->paginate(Helpers::pagination_limit())->appends(['search'=>$search,'sort_oqrderQty'=>$request->sort_oqrderQty]);
        return view('admin-views.supplier.product-list',compact('supplier','products','search','sort_oqrderQty'));
    }

    /**
     * @param Request $request
     * @param $id
     * @return Application|Factory|View
     */
    public function transaction_list(Request $request, $id): View|Factory|Application
    {
        $supplier = $this->supplier->find($id);
        $accounts = $this->account->orderBy('id')->get();

        $from = $request->from;
        $to = $request->to;

        $query = $this->transection->where('supplier_id',$id)
            ->when($from!=null, function($q) use ($request){
                return $q->whereBetween('date', [$request['from'], $request['to']]);
            });

        $transections = $query->latest()->paginate(Helpers::pagination_limit())->appends(['from'=>$request['from'],'to'=>$request['to']]);
        return view ('admin-views.supplier.transaction-list',compact('supplier','accounts','transections','from','to'));
    }

    public function add_new_purchase(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required',
            'purchased_amount'=> 'required',
            'paid_amount' => 'required',
            'due_amount' => 'required',
            'payment_account_id' => 'required',
        ]);

        $payment_account = $this->account->find($request->payment_account_id);

        if($payment_account->balance < $request->paid_amount)
        {
            Toastr::warning(\App\CPU\translate('you_do_not_have_sufficent_balance'));
            return back();
        }
        if($request->paid_amount > 0)
        {
            $payment_transaction = new Transection;
            $payment_transaction->tran_type = 13;
            $payment_transaction->account_id = $payment_account->id;
            $payment_transaction->amount = $request->paid_amount;
            $payment_transaction->description = 'Supplier payment';
            $payment_transaction->debit = 1;
            $payment_transaction->credit = 0;
            $payment_transaction->balance = $payment_account->balance - $request->paid_amount;
            $payment_transaction->date = date("Y/m/d");
            $payment_transaction->supplier_id = $request->supplier_id;
            $payment_transaction->save();

            $payment_account->total_out = $payment_account->total_out + $request->paid_amount;
            $payment_account->balance = $payment_account->balance - $request->paid_amount;
            $payment_account->save();
        }

        if($request->due_amount > 0)
        {
            $payable_account = $this->account->find(2);
            $payable_transaction = new Transection;
            $payable_transaction->tran_type =13;
            $payable_transaction->account_id = $payable_account->id;
            $payable_transaction->amount = $request->due_amount;
            $payable_transaction->description = 'Supplier payment';
            $payable_transaction->debit = 0;
            $payable_transaction->credit = 1;
            $payable_transaction->balance = $payable_account->balance + $request->due_amount;
            $payable_transaction->date = date("Y/m/d");
            $payable_transaction->supplier_id = $request->supplier_id;
            $payable_transaction->save();

            $payable_account->total_in = $payable_account->total_in + $request->due_amount;
            $payable_account->balance = $payable_account->balance + $request->due_amount;
            $payable_account->save();

            $supplier = $this->supplier->find($request->supplier_id);
            $supplier->due_amount = $supplier->due_amount + $request->due_amount;
            $supplier->save();
        }

        Toastr::success(translate('Supplier new payment added successfully'));
        return back();

    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
        public function update_balance(Request $request): RedirectResponse
{
    $request->validate([
        'supplier_id' => 'required',
        'amount' => 'required|numeric|min:0',
        'account_id' => 'required',
        'date' => 'required|date',
        'description' => 'nullable|string', // Ensure description is optional
    ]);

    $supplier = $this->supplier->find($request->supplier_id);
    $amount = $request->amount;
    $account = Account::find($request->account_id);
$seller_id = auth('admin')->id(); // Get the admin ID from the authenticated admin user
     $img = null;
    if ($request->hasFile('img')) {
        $img = Helpers::update('shop/', null, 'png', $request->file('img'));
    }
    if ($account->balance >= $amount) {
        // Process the transaction
        $transaction = new Transection;
        $transaction->tran_type = 13;
        $transaction->account_id = $account->id;
        $transaction->amount = $amount;
        $transaction->description = $request->description;
        $transaction->debit = $supplier->due_amount;
        $transaction->credit = $supplier->credit;
        $transaction->balance = $account->balance + $amount;
        $transaction->date = $request->date;
        $transaction->supplier_id = $request->supplier_id;
        $transaction->seller_id = $seller_id;
        $transaction->img = $img;
        $transaction->save();

        $account->total_out += $amount;
        $account->balance -= $amount;
        $account->save();

        $supplier->due_amount -= $amount;
        $supplier->save();

        Toastr::success(translate('تم استلام النقدية'));
    }
    // } else {
    //     // Handle the case where account balance is less than requested amount
    //     $remaining_balance = $amount - $account->balance;

    //     if ($customer->balance >= $remaining_balance) {
    //         // Process the payable transaction
    //         $payable_account = Account::find(2); // Replace with appropriate account ID
    //         $payable_transaction = new Transection;
    //         $payable_transaction->tran_type = 'Payable';
    //         $payable_transaction->account_id = $payable_account->id;
    //         $payable_transaction->amount = $remaining_balance;
    //         $payable_transaction->description = $request->description;
    //         $payable_transaction->debit = $remaining_balance;
    //         $payable_transaction->credit = 0;
    //         $payable_transaction->balance = $payable_account->balance + $remaining_balance;
    //         $payable_transaction->date = $request->date;
    //         $payable_transaction->customer_id = $request->customer_id;
    //         $payable_transaction->save();

    //         $payable_account->total_in += $remaining_balance;
    //         $payable_account->balance += $remaining_balance;
    //         $payable_account->save();

    //         // Update the receiving account
    //         $receive_account = Account::find($request->account_id);
    //         $receive_transaction = new Transection;
    //         $receive_transaction->tran_type = 'Income';
    //         $receive_transaction->account_id = $receive_account->id;
    //         $receive_transaction->amount = $account->balance;
    //         $receive_transaction->description = $request->description;
    //         $receive_transaction->debit = 0;
    //         $receive_transaction->credit = $account->balance;
    //         $receive_transaction->balance = $receive_account->balance + $account->balance;
    //         $receive_transaction->date = $request->date;
    //         $receive_transaction->customer_id = $request->customer_id;
    //         $receive_transaction->save();

    //         $receive_account->total_in += $account->balance;
    //         $receive_account->balance += $account->balance;
    //         $receive_account->save();

    //         // Update the receivable account
    //         $receivable_account = Account::find(3); // Replace with appropriate account ID
    //         $receivable_transaction = new Transection;
    //         $receivable_transaction->tran_type = 'Receivable';
    //         $receivable_transaction->account_id = $receivable_account->id;
    //         $receivable_transaction->amount = $remaining_balance;
    //         $receivable_transaction->description = 'update customer balance';
    //         $receivable_transaction->debit = $remaining_balance;
    //         $receivable_transaction->credit = 0;
    //         $receivable_transaction->balance = $receivable_account->balance - $remaining_balance;
    //         $receivable_transaction->date = $request->date;
    //         $receivable_transaction->customer_id = $request->customer_id;
    //         $receivable_transaction->save();

    //         $receivable_account->total_out += $remaining_balance;
    //         $receivable_account->balance -= $remaining_balance;
    //         $receivable_account->save();

    //         $customer->balance -= $amount;
    //         $customer->save();

    //         Toastr::success(translate('Customer balance updated successfully'));
    //     } 
    else {
            Toastr::error(translate('المبلغ المتواجد في هذا الحساب اقل من المبلغ اللي تريد تسليمه لهذا المورد'));
        }
    

    return back();
}
public function update_credit(Request $request)
{
    $request->validate([
        'supplier_id' => 'required',
        'amount' => 'required|numeric|min:0',
        'account_id' => 'required',
        'date' => 'required|date',
        'description' => 'nullable|string',
    ]);

    $supplier = $this->supplier->find($request->supplier_id);
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
            // Create installment record
            $installment = $this->installment;
            $installment->seller_id = $seller_id; 
            $installment->supplier_id = $request->supplier_id;
            $installment->total_price = $request->amount;
            $installment->note = $request->description;
            $installment->img = $img;
            $installment->save();
            
            // Create history installment record
            $history_installment = $this->history_installment;
            $history_installment->seller_id = $seller_id; 
            $history_installment->supplier_id = $request->supplier_id;
            $history_installment->total_price = $request->amount;
            $history_installment->note = $request->description;
            $history_installment->img = $img;
            $history_installment->save();

            // Process the transaction
            $transaction = new Transection;
            $transaction->tran_type = 26;
            $transaction->account_id = $account->id;
            $transaction->amount = $amount;
            $transaction->description = $request->description;
            $transaction->debit = $supplier->due_amount;
            $transaction->credit = $supplier->credit;
            $transaction->balance = $account->balance + $amount;
            $transaction->date = $request->date;
            $transaction->supplier_id = $request->supplier_id;
            $transaction->seller_id = $seller_id;
            $transaction->img = $img;
            $transaction->save();

            // Update account balance
            $account->total_in += $amount;
            $account->balance += $amount;
            $account->save();

            // Update customer credit
            $supplier->credit -= $amount;
            $supplier->save();

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
    public function pay_due(Request $request): RedirectResponse
    {
        $request->validate([
            'supplier_id' => 'required',
            'total_due_amount'=> 'required',
            'pay_amount' => 'required',
            'remaining_due_amount' => 'required',
            'payment_account_id' => 'required',
        ]);

        $payment_account = $this->account->find($request->payment_account_id);
        if($payment_account->balance < $request->pay_amount)
        {
            Toastr::warning(\App\CPU\translate('you_do_not_have_sufficent_balance!'));
            return back();
        }

        if($request->pay_amount > 0 )
        {
            $payment_transaction = new Transection;
            $payment_transaction->tran_type = 13;
            $payment_transaction->account_id = $payment_account->id;
            $payment_transaction->amount = $request->pay_amount;
            $payment_transaction->description = 'Supplier due payment';
            $payment_transaction->debit = 1;
            $payment_transaction->credit = 0;
            $payment_transaction->balance = $payment_account->balance - $request->pay_amount;
            $payment_transaction->date = date("Y/m/d");
            $payment_transaction->supplier_id = $request->supplier_id;
            $payment_transaction->save();

            $payment_account->total_out = $payment_account->total_out + $request->pay_amount;
            $payment_account->balance = $payment_account->balance - $request->pay_amount;
            $payment_account->save();

            $payable_account = $this->account->find(2);
            $payable_transaction = new Transection;
            $payable_transaction->tran_type = 13;
            $payable_transaction->account_id = $payable_account->id;
            $payable_transaction->amount = $request->pay_amount;
            $payable_transaction->description = 'Supplier due payment';
            $payable_transaction->debit = 1;
            $payable_transaction->credit = 0;
            $payable_transaction->balance = $payable_account->balance - $request->pay_amount;
            $payable_transaction->date = date("Y/m/d");
            $payable_transaction->supplier_id = $request->supplier_id;
            $payable_transaction->save();

            $payable_account->total_out = $payable_account->total_out + $request->pay_amount;
            $payable_account->balance = $payable_account->balance - $request->pay_amount;
            $payable_account->save();
        }

        $supplier = $this->supplier->find($request->supplier_id);
        $supplier->due_amount = $supplier->due_amount - $request->pay_amount;
        $supplier->save();

        Toastr::success(translate('Supplier payment due successfully'));
        return back();
    }

    /**
     * @param $id
     * @return Application|Factory|View
     */
    public function edit($id): View|Factory|Application
    {
        $supplier = $this->supplier->find($id);
        return view('admin-views.supplier.edit', compact('supplier'));
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function update(Request $request): RedirectResponse
    {
        $supplier = $this->supplier->where('id',$request->id)->first();
        $request->validate([
            'name' => 'required',
            'mobile'=> 'required|unique:suppliers,mobile,'.$supplier->id,
            'email' => 'required|email|unique:suppliers,email,'.$supplier->id,
            'state' => 'required',
            'city' => 'required',
            'zip_code' => 'required',
            'address' => 'required',
            
        ]);

        $supplier->name = $request->name;
        $supplier->mobile = $request->mobile;
        $supplier->email = $request->email;
        $supplier->image = $request->has('image') ? Helpers::update('supplier/', $supplier->image, 'png', $request->file('image')) : $supplier->image;
        $supplier->state = $request->state;
        $supplier->city = $request->city;
                $supplier->due_amount = $request->balance;

        $supplier->zip_code = $request->zip_code;
        $supplier->address = $request->address;

        $supplier->save();

        Toastr::success(translate('Supplier updated successfully'));
        return back();
    }
    public function status(Request $request): RedirectResponse
{
    $supplier = $this->supplier->find($request->id);
    
    // Toggle the active status (if 0, set 1; if 1, set 0)
    $supplier->active = $supplier->active ? 0 : 1;
    
    $supplier->save();

    Toastr::success(translate('تم تغيير حالة المورد'));

    return back();
}

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function delete(Request $request): RedirectResponse
    {
        $supplier = $this->supplier->find($request->id);
        Helpers::delete('supplier/' . $supplier['image']);
        $supplier->delete();

        Toastr::success(translate('Supplier removed successfully'));
        return back();
    }
}
