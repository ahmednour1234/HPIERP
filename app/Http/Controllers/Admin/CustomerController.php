<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Order;
use Brian2694\Toastr\Facades\Toastr;
use App\CPU\Helpers;
use App\Models\Account;
use App\Models\Seller;
use App\Models\Installment;
use App\Models\HistoryInstallment;
use App\Models\CustomerPrice;
use App\Models\Category;
use App\Models\Transection;
use App\Models\Region;
use function App\CPU\translate;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\Auth;
use App\Exports\CustomerupdateExport;
use App\Imports\CustomerImport;
use Maatwebsite\Excel\Facades\Excel;        // ← add this

class CustomerController extends Controller
{
    public function __construct(
        private CustomerPrice $price,
        private Customer $customer,
        private Region $region,
        private Order $order,
        private Account $account,
        private Transection $transection,
      private Category $category,
     private Installment $installment,
          private HistoryInstallment $history_installment
    ){}

    /**
     * @return Application|Factory|View
     */
public function index(): View|Factory|Application
{
    $categories = $this->category->where('type', 0)->get(); // Fetch categories with type 0
    $regions = $this->region->get(); // Fetch categories with type 0

    return view('admin-views.customer.index', compact('categories','regions')); // Pass categories to the view
}

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|unique:customers',
            'name_en' => 'nullable|unique:customers',
            'mobile'=> 'required|unique:customers',
            'longitude'=> 'nullable',
            'latitude'=> 'nullable',
            'type'=> 'required',
            'category_id'=> 'nullable',
            'region_id'=> 'required',
            'specialist' => 'required|in:1,2,3,4', // Assuming these are the only valid values


        ]);

        if (!empty($request->file('image'))) {
            $image_name =  Helpers::upload('customer/', 'png', $request->file('image'));
        } else {
            $image_name = 'def.png';
        }

        $customer = $this->customer;
        $customer->name = $request->name;
        $customer->name_en = $request->name_en;
        $customer->region_id = $request->region_id;
        $customer->specialist = $request->specialist;
        $customer->pharmacy_name = $request->pharmacy_name;
        $customer->mobile = $request->mobile;
        $customer->email = $request->email;
        $customer->image = $image_name;
        $customer->state = $request->state;
        $customer->city = $request->city;
        $customer->zip_code = $request->zip_code;
        $customer->address = $request->address;
        $customer->balance = $request->balance;
        $customer->longitude = $request->longitude;
        $customer->latitude = $request->latitude;
        $customer->type = $request->type;
        $customer->category_id = $request->category_id;
        $customer->save();

        Toastr::success(translate('تمت اضافة العميل بنجاح'));
        return back();
    }

    /**
     * @param Request $request
     * @return Application|Factory|View
     */
public function list(Request $request): View|Factory|Application
{
    // Fetch the accounts data
    $accounts = $this->account->orderBy('id')->get();
    
    // Initialize query parameters for pagination
    $query_param = [];
    
    // Get the search and specialization filter input
    $search = $request->input('search');
    $specialization_id = $request->input('specialist'); // Get specialization filter
    
    // Get the authenticated admin's ID
    $adminId = Auth::guard('admin')->id();

    // Fetch sellers linked to the authenticated admin through the admin_seller table
    $sellers = Seller::join('admin_sellers', 'admin_sellers.seller_id', '=', 'admins.id')
                    ->where('admin_sellers.admin_id', $adminId)
                    ->select('admins.id') // Select only seller IDs
                    ->get()->pluck('id')->toArray(); // Convert to an array of seller IDs
    // Build the base query for customers
    $customersQuery = $this->customer->query();

    // Filter customers by seller_id through the seller_customers table
  $sellerId = request()->get('seller_id'); // Assuming seller_id is passed in the request.
// dd($sellerId);
$customersQuery = Customer::query();

if ($sellerId) {
    $customersQuery->whereIn('id', function ($query) use ($sellerId) {
        $query->select('customer_id')
              ->from('seller_customers')
              ->where('seller_id', $sellerId);
    });
}
    // Apply search filter if provided
    if ($search) {
        $key = explode(' ', $search);
        $customersQuery->where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('name', 'like', "%{$value}%")
                  ->orWhere('mobile', 'like', "%{$value}%");
            }
        });
        $query_param['search'] = $search;
    }

    // Apply specialization filter if provided
if ($specialization_id) {
    if ($specialization_id == 4 || $specialization_id == 0) {
        $customersQuery->whereIn('specialist', [4, 0]);
    } else {
        $customersQuery->where('specialist', $specialization_id);
    }
    $query_param['specialist'] = $specialization_id;
}


    // Get additional data for the view
    $walk_customer = $this->customer->where('id', 0)->first();
    $categories = $this->category->where('type', 0)->get();

    // Paginate the filtered customers with query parameters
    $customers = $customersQuery->paginate(Helpers::pagination_limit())->appends($query_param);

    // Return the view with the necessary data
    return view('admin-views.customer.list', compact(
        'customers', 
        'accounts', 
        'search', 
        'walk_customer', 
        'categories', 
        'sellers',
        'specialization_id'
    ));
}

public function export(Request $request)
{
    // Get the search parameter
    $search = $request->input('search');

    // Query customers with search functionality
    $query = $this->customer->query();
    if ($search) {
        $key = explode(' ', $search);
        $query->where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('name', 'like', "%{$value}%")
                  ->orWhere('mobile', 'like', "%{$value}%");
            }
        });
    }

    // Get the customers data
    $customers = $query->get([
        'name', 'mobile', 'email', 'address', 'pharmacy_name', 'state', 'city', 'zip_code', 'balance'
    ]);

    // Export the data using FastExcel
    return (new FastExcel($customers))->download('customers.xlsx');
}

    public function prices(Request $request, $id): View|Factory|Application|RedirectResponse
{
    $price = $this->price;

    if ($request->has('price')) {
        $request->validate([
            'price' => 'required|numeric',
            'product_id' => 'required',
            'customer_id' => 'required',
        ]);

        // تحقق مما إذا كان السعر موجودًا بالفعل
        $existingPrice = $price->where('product_id', $request->product_id)
            ->where('customer_id', $request->customer_id)
            ->first();

        if ($existingPrice) {
            // إذا كان السعر موجودًا، قم بتحديثه
            $existingPrice->price = $request->price;
            $existingPrice->save();
            Toastr::success(translate('product_price_updated_successfully'));
        } else {
            // إذا لم يكن السعر موجودًا، قم بإنشائه
            $price->price = $request->price;
            $price->product_id = $request->product_id;
            $price->customer_id = $request->customer_id;
            $price->save();
            Toastr::success(translate('product_price_added_successfully'));
        }

        return back();
    }

    $prices = $price->where('customer_id', $id)->latest()->paginate(Helpers::pagination_limit());
    return view('admin-views.customer.prices', compact('prices'), ['customer_id' => $id]);
}

    
    public function edit_price(Request $request, $customer_id, $price_id): View|Factory|Application|RedirectResponse
    {
        $price = $this->price->find($price_id);

        if($request->has('price')) {
            $request->validate([
                'price' => 'required|numeric',
                'product_id' => 'required',
                // 'customer_id' => 'required',
            ]);

            $price->price = $request->price;
            $price->product_id = $request->product_id;
            $price->customer_id = $customer_id;
            $price->update();

            Toastr::success(translate('product_price_add_successfully'));
            return back();
        }

        return view('admin-views.customer.edit_price', compact('customer_id', 'price'));
    }

    public function delete_price($id): View|Factory|Application|RedirectResponse
    {
        $price = $this->price->find($id);
        $price->delete();
        Toastr::success(translate('product_price_removed_successfully'));
        return back();
    }

    /**
     * @param Request $request
     * @param $id
     * @return Application|Factory|View|RedirectResponse
     */
    public function view(Request $request, $id): View|Factory|RedirectResponse|Application
{
    $customer = $this->customer->where('id', $id)->first();
    if (isset($customer)) {
        $query_param = [];
        $search = $request['search'];
        $orderType = $request['order_type'];
        $startDate = $request['start_date'];
        $endDate = $request['end_date'];

        $orders = $this->order->where(['user_id' => $id]);

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

        $orders = $orders->latest()->paginate(Helpers::pagination_limit())->appends($query_param);
        return view('admin-views.customer.view', compact('customer', 'orders', 'search'));
    }

    Toastr::error('Customer not found!');
    return back();
}


    /**
     * @param Request $request
     * @param $id
     * @return Application|Factory|View|RedirectResponse
     */
    public function transaction_list(Request $request, $id): View|Factory|RedirectResponse|Application
    {
        $accounts = $this->account->get();
        $customer = $this->customer->where('id',$id)->first();
        if(isset($customer))
        {
            $acc_id = $request['account_id'];
            $tran_type = $request['tran_type'];
            $orders = $this->order->where(['user_id' => $id])->get();
            $transactions = $this->transection->where(['customer_id' => $id])
                                ->when($acc_id!=null, function($q) use ($request){
                                    return $q->where('account_id',$request['account_id']);
                                })
                                ->when($tran_type!=null, function($q) use ($request){
                                    return $q->where('tran_type',$request['tran_type']);
                                })->latest()->paginate(Helpers::pagination_limit())
                                ->appends(['account_id' => $request['account_id'],'tran_type'=>$request['tran_type']]);
            return view('admin-views.customer.transaction-list',compact('customer', 'transactions','orders','tran_type','accounts','acc_id'));
        }
        Toastr::error(translate('Customer not found'));
        return back();
    }

    /**
     * @param Request $request
     * @return Application|Factory|View
     */
    public function edit(Request $request): Factory|View|Application
    {
        $customer = $this->customer->where('id',$request->id)->first();
         $categories = $this->category->where('type', 0)->get(); // Fetch categories with type 0
    $regions = $this->region->get(); // Fetch categories with type 0
        return view('admin-views.customer.edit',compact('customer','categories','regions'));
    }
    public function editexport(Request $request): Factory|View|Application
    {

        return view('admin-views.customer.editexport');
    }
    /**
     * @param Request $request
     * @return RedirectResponse
     */
public function update(Request $request): RedirectResponse
{
    $customer = $this->customer->where('id', $request->id)->first();
    if (!$customer) {
        Toastr::error(translate('Customer not found'));
        return back();
    }

    $rules = [

    ];

    $request->validate($rules);

    $customer->name = $request->name;
    $customer->name_en = $request->name_en;
    $customer->mobile = $request->mobile;
    $customer->region_id = $request->region_id;
    $customer->specialist = $request->specialist;
    $customer->pharmacy_name = $request->pharmacy_name;
    $customer->email = $request->email;
    $customer->image = $request->has('image') ? Helpers::update('customer/', $customer->image, 'png', $request->file('image')) : $customer->image;
    $customer->state = $request->state;
    $customer->city = $request->city;
    $customer->zip_code = $request->zip_code;
    $customer->address = $request->address;
    $customer->balance = $request->balance;
    $customer->latitude = $request->latitude;
    $customer->longitude = $request->longitude;
    $customer->type = $request->type;
    $customer->category_id = $request->category_id;
    $customer->save();

    Toastr::success(translate('تم تحديث بيانات العميل بنجاح'));
    return redirect()->route('admin.customer.list');
}
public function status(Request $request): RedirectResponse
{
    $customer = $this->customer->find($request->id);
    
    // Toggle the active status (if 0, set 1; if 1, set 0)
    $customer->active = $customer->active ? 0 : 1;
    
    $customer->save();

    Toastr::success(translate('تم تغيير حالة العميل'));

    return back();
}
    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function delete(Request $request): RedirectResponse
    {
        $customer = $this->customer->find($request->id);
        Helpers::delete('customer/' . $customer['image']);
        $customer->delete();

        Toastr::success(translate('Customer removed successfully'));
        return back();
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
     
public function update_balance(Request $request): RedirectResponse
{
    // Validate the incoming request data
    $request->validate([
        'customer_id' => 'required',
        'amount' => 'required|numeric|min:0',
        'account_id' => 'required',
        'date' => 'required|date',
        'description' => 'nullable|string', // Description is optional
        'img' => 'required', // Ensure image is required and valid
    ]);

    // Image upload logic
  $img = null;
   
     if ($request->hasFile('img')) {
        $img = $request->file('img')->store('shop', 'public'); // Store the image
        $fileName = $request->file('img')->getClientOriginalName(); // Get original filename (optional)
    }
    // Retrieve customer and account information
    $customer = $this->customer->find($request->customer_id);
    $amount = $request->amount;
    $account = Account::find($request->account_id);
    $seller_id = auth('admin')->id(); // Get the admin ID from the authenticated admin user

    if ($account && $customer) {
        // Check if the account balance is sufficient
        if ($account->balance >= $amount) {
            // Process the transaction
            $transaction = new Transection();
            $transaction->tran_type = 13;
            $transaction->account_id = $account->id;
            $transaction->amount = $amount;
            $transaction->description = $request->description;
            $transaction->debit = $customer->balance;
            $transaction->credit = $customer->credit;
            $transaction->balance = $account->balance - $amount; // Update balance after deduction
            $transaction->date = $request->date;
            $transaction->customer_id = $request->customer_id;
            $transaction->seller_id = $seller_id;
            $transaction->img = $img;
            $transaction->save();

            // Update account and customer balances
            $account->total_out += $amount;
            $account->balance -= $amount;
            $account->save();

            $customer->balance -= $amount;
            $customer->save();

            Toastr::success(translate('تم استلام النقدية'));
        } else {
            // Handle insufficient balance in the account
            Toastr::error(translate('المبلغ المتواجد في هذا الحساب اقل من المبلغ اللي تريد تسليمه لهذا العميل'));
        }
    } else {
        // Handle missing customer or account
        Toastr::error(translate('الحساب أو العميل غير موجود'));
    }

    return back();
}

public function update_credit(Request $request)
{
    $request->validate([
        'customer_id' => 'required',
        'amount' => 'required|numeric|min:0',
        'account_id' => 'required',
        'date' => 'required|date',
        'description' => 'nullable|string',
    ]);

    $customer = $this->customer->find($request->customer_id);
    $amount = $request->amount;
    $account = Account::find($request->account_id);
    $seller_id = auth('admin')->id(); // Get the admin ID from the authenticated admin user
    $img = null;

    if ($request->hasFile('img')) {
        $img = $request->file('img')->store('shop', 'public'); // Store the image
    }

    DB::beginTransaction(); // Start the transaction

    try {
        $order = Order::findOrFail($request->order_id);

        // Calculate the transaction reference (total paid so far)
        $transaction_reference = Order::where('id', $order->id)
            ->sum('transaction_reference');

        // Add the new payment to the transaction reference
        $new_transaction_reference = $transaction_reference + $request->amount;

        // Check order payment status
        if ($new_transaction_reference == $order->order_amount) {
            $status = 'تم تحصيل كامل المبلغ';
        } elseif ($new_transaction_reference < $order->order_amount) {
            $remaining = $order->order_amount - $new_transaction_reference;
            $status = "جزء من المبلغ تم تحصيله. الباقي: $remaining";
        } else {
            return redirect()->back()->withErrors(['error' => 'هذه الفاتورة تم تحصيلها بالكامل']);
        }

        // Check if the customer's credit is sufficient
        if ($customer->credit < $request->price) {
            return redirect()->back()->withErrors(['error' => 'المبلغ المحصل أكبر من مديونية العميل']);
        }

        $order->transaction_reference += $request->amount;
        $order->save();

        // Create installment record
        $installment = $this->installment;
        $installment->seller_id = $seller_id;
        $installment->customer_id = $request->customer_id;
        $installment->total_price = $request->amount;
        $installment->note = $request->description;
        $installment->order_id = $request->order_id;
        $installment->img = $img;
        $installment->save();

        // Create history installment record
        $history_installment = $this->history_installment;
        $history_installment->seller_id = $seller_id;
        $history_installment->customer_id = $request->customer_id;
        $history_installment->total_price = $request->amount;
        $history_installment->note = $request->description;
        $history_installment->order_id = $request->order_id;
        $history_installment->img = $img;
        $history_installment->save();

        // Process the transaction
        $transaction = new Transection;
        $transaction->tran_type = 26;
        $transaction->account_id = $account->id;
        $transaction->amount = $amount;
        $transaction->description = $request->description;
        $transaction->debit = $customer->balance;
        $transaction->credit = $customer->credit;
        $transaction->balance = $account->balance + $amount;
        $transaction->date = $request->date;
        $transaction->customer_id = $request->customer_id;
        $transaction->seller_id = $seller_id;
        $transaction->img = $img;
        $transaction->save();

        // Update account balance
        $account->total_in += $amount;
        $account->balance += $amount;
        $account->save();

        // Update customer credit
        $customer->credit -= $amount;
        $customer->save();

        DB::commit(); // Commit the transaction

        Toastr::success(translate('تم دفع النقدية'));
    } catch (\Exception $e) {
        DB::rollBack(); // Rollback transaction
        return redirect()->back()->withErrors(['error' => 'لم يتم دفع النقدية: ' . $e->getMessage()]);
    }

    return redirect()->back()->with('success', 'تم تحديث الرصيد بنجاح');
}
public function exportupdate()
{
    $exporter = new CustomerupdateExport();

    return (new FastExcel($exporter->collection()))
        ->download('customers.xlsx');
}
    // استيراد العملاء من ملف Excel وتحديث البيانات
    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls',
        ]);

        try {
            (new FastExcel)->import($request->file('excel_file'), new CustomerImport);

            Toastr::success('تم تحديث العملاء بنجاح من الإكسل', 'نجاح');
        } catch (ValidationException $e) {
            Toastr::error(collect($e->errors())->flatten()->first(), 'خطأ في الاستيراد');
        } catch (\Exception $e) {
            Toastr::error('حدث خطأ أثناء الاستيراد: ' . $e->getMessage(), 'خطأ');
        }

        return back();
    }
}
