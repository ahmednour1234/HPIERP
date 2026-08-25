<?php

namespace App\Http\Controllers\Api\V1;

use App\CPU\Helpers;
use App\Models\Account;
use App\Models\Customer;
use App\Models\Transection;
use App\Models\SellerCustomer;
use App\Models\Category;
use App\Models\Region;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function __construct(
        private Account $account,
        private Customer $customer,
        private Transection $transection,
                private Category $category,
                                private Region $region

    ){}

    /**
     * @param Request $request
     * @return JsonResponse
     */

public function getIndex(Request $request): JsonResponse
{
    /*────────────────────────────
     | 1) جلب العملاء وجهز الأعمدة
     ────────────────────────────*/
    $allCustomers = Customer::query()
        ->join('seller_customers', 'customers.id', '=', 'seller_customers.customer_id')
        ->with('regions:id,name')                       // تحميل اسم المنطقة مرة واحدة
        ->select('customers.*')

        /* عدد الطلبات المكتملة (order_amount = transaction_references) */
        ->selectSub(function ($q) {
            $q->from('orders')
              ->selectRaw('COUNT(*)')
              ->whereColumn('orders.user_id', 'customers.id')
              ->where('orders.type', 4)
              ->where('orders.order_amount', 'transaction_references');
        }, 'order_count')

        /* عدد الطلبات غير المكتملة */
        ->selectSub(function ($q) {
            $q->from('orders')
              ->selectRaw('COUNT(*)')
              ->whereColumn('orders.user_id', 'customers.id')
              ->where('orders.type', 4)
              ->where('orders.order_amount', '!=', 'transaction_references');
        }, 'order_count_no_done')

        /* عدد الزيارات المنفذة */
        ->selectSub(function ($q) {
            $q->from('result_visitors')
              ->selectRaw('COUNT(*)')
              ->whereColumn('result_visitors.customer_id', 'customers.id');
        }, 'executed_visits')

        ->orderByDesc('customers.id')
        ->get();

    $formatted = $allCustomers->map(fn ($customer) => [
        'id'                   => $customer->id,
        'name'                 => $customer->name,
        'phone'                => $customer->phone,
        'email'                => $customer->email,
        'balance'              => $customer->balance,
        'credit'               => $customer->credit,
        'specialist'           => $customer->specialist,
        'address'              => $customer->address ?? '',
        'region'               => $customer->regions->name ?? '',
        'order_count'          => (int) $customer->order_count,
        'order_count_no_done'  => (int) $customer->order_count_no_done,
        'executed_visits'      => (int) $customer->executed_visits,
    ]);
    return response()->json([
        'total'         => $formatted->count(),
        'all_customers' => $formatted,
    ], 200);
}
public function indexcategory(Request $request): JsonResponse
{
    $categories = $this->category->where('type', 0)->get(); // Fetch categories with type 0
$regions = Region::whereIn('id', function ($query) {
    $query->select('region_id')
          ->from('seller_regions')
          ->where('seller_id', Auth::id()); // Use Auth::id() for the authenticated user's ID
})->get();
return response()->json([
    'categories' => $categories,
    'regions' => $regions
], 200); // 200 is the HTTP status code for success
}



    /**
     * @param Request $request
     * @param Customer $customer
     * @return JsonResponse
     */
public function postStore(Request $request): JsonResponse
{
    try {
        // Validate request data
        $request->validate([

        ]);

        // Handle image upload if exists
        $image_name = 'def.png'; // Default image
        if ($request->hasFile('image')) {
            $image_name = Helpers::upload('customer/', 'png', $request->file('image'));
        }

        // Create a new customer
        $customer = new Customer();
        $customer->name = $request->name??'r';
        $customer->name_en = $request->name_en??'r';
        $customer->region_id = $request->region_id??'1';
        $customer->specialist = $request->specialist??'1';
        $customer->pharmacy_name = $request->pharmacy_name??'s';
        $customer->mobile = $request->mobile??'0';
        $customer->email = $request->email??'das';
        $customer->state = $request->state??'4';
        $customer->city = $request->city??'2';
        $customer->zip_code = $request->zip_code??'2';
        $customer->address = $request->address??'1';
        $customer->type = $request->type??'1';
        $customer->category_id = $request->category_id??'1';
        $customer->save();

        // Get the authenticated seller's ID
        $seller_id = auth()->user()->id; // Assuming the authenticated user is a seller

        // Attach the customer to the seller
        $sellerCustomer = new SellerCustomer();
        $sellerCustomer->seller_id = $seller_id;
        $sellerCustomer->customer_id = $customer->id;
        $sellerCustomer->save();

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Customer saved successfully',
        ], 200);

    } catch (\Throwable $th) {
        // Return failure response
        return response()->json([
            'success' => false,
            'message' => 'Customer not saved: ' . $th->getMessage(),
        ], 403);
    }
}

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getDetails(Request $request): JsonResponse
    {
        try {
            $customerDetails = $this->customer->findOrFail($request->id);
            return response()->json([
                'message' => 'Customer details',
                'data' => $customerDetails,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Invalid id: customer not found',
            ], 422);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function postUpdate(Request $request): JsonResponse
    {
        $customer = $this->customer->where('id', $request->id)->first();
        $request->validate([
            'name' => 'required',
            'name_en' => 'required',
            'mobile' => 'required|unique:customers,mobile,' . $customer->id,
        ]);
        $customer->name = $request->name;
        $customer->name_en = $request->name_en;
        $customer->mobile = $request->mobile;
        $customer->email = $request->email;
        $customer->image = $request->has('image') ? Helpers::update('customer/', $customer->image, 'png', $request->file('image')) : $customer->image;
        $customer->state = $request->state;
        $customer->city = $request->city;
        $customer->zip_code = $request->zip_code;
        $customer->address = $request->address;
        $customer->balance = $request->balance;
        $customer->longitude = $request->longitude;
        $customer->latitude = $request->latitude;
        $customer->type = $request->type;
        $customer->limit = $request->limit;
        $customer->category_id = $request->category_id;
        $customer->update();
        return response()->json([
            'message' => 'Customer updated successfully',
        ], 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function delete(Request $request): JsonResponse
    {
        try {
            $customer = $this->customer->where('id' ,'!=', '0')->find($request->id);
            Helpers::delete('customer/' . $customer['image']);
            $customer->delete();
            return response()->json([
                'message' => 'Customer deleted',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Customer not deleted',
            ], 403);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
public function getSearch(Request $request): JsonResponse
{
    $offset   = $request->input('offset', 1);
    $search   = $request->input('name', '');
    $sellerId = Auth::id();      // مُعرِّف المندوب الحالي

    /*────────────────────────────
     | 1) الاستعلام مع Sub-Queries
     ────────────────────────────*/
    $baseQuery = Customer::query()
        ->join('seller_customers', 'customers.id', '=', 'seller_customers.customer_id')->where('active',1)
        ->where('seller_customers.seller_id', $sellerId)
        ->when($search, fn ($q) =>
            $q->where(function ($sub) use ($search) {
                $sub->where('customers.name',   'like', "%{$search}%")
                    ->orWhere('customers.mobile','like', "%{$search}%");
            })
        )
        ->select('customers.*')

        /* عدد الطلبات المكتملة */
        ->selectSub(function ($q) {
            $q->from('orders')
              ->selectRaw('COUNT(*)')
              ->whereColumn('orders.user_id', 'customers.id')
              ->where('orders.type', 4)
              ->where('orders.order_amount', 'transaction_references');
        }, 'order_count')

        /* عدد الطلبات غير المكتملة */
        ->selectSub(function ($q) {
            $q->from('orders')
              ->selectRaw('COUNT(*)')
              ->whereColumn('orders.user_id', 'customers.id')
              ->where('orders.type', 4)
              ->where('orders.order_amount', '!=', 'transaction_references');
        }, 'order_count_no_done')

        /* عدد الزيارات المنفذة */
        ->selectSub(function ($q) {
            $q->from('result_visitors')
              ->selectRaw('COUNT(*)')
              ->whereColumn('result_visitors.customer_id', 'customers.id');
        }, 'executed_visits')

        ->latest('customers.id');

    /*────────────────────────────
     | 2) التصفح (Pagination)
     ────────────────────────────*/
    $customers = $baseQuery->paginate(
        perPage: null,          // لا حدّ للعدد
        columns: ['*'],
        pageName: 'page',
        page: $offset
    );

    /*────────────────────────────
     | 3) تهيئة العناصر (مثل الدالة السابقة)
     ────────────────────────────*/
  $customers->getCollection()->transform(fn ($c) => [
    'id'                  => $c->id,
    'name'                => $c->name,
    'phone'               => $c->phone,
    'email'               => $c->email,
    'balance'             => $c->balance,
    'credit'              => $c->credit,
    'specialist'          => $c->specialist,
    'address'             => $c->address ?? '',
    'region'              => $c->regions ? [
        'id'   => $c->regions->id,
        'name' => $c->regions->name,
    ] : null,
    'order_count'         => (int) $c->order_count,
    'order_count_no_done' => (int) $c->order_count_no_done,
    'executed_visits'     => (int) $c->executed_visits,
]);

    /*────────────────────────────
     | 4) نفس بنية الاستجابة
     ────────────────────────────*/
    return response()->json([
        'total'     => $customers->total(),
        'limit'     => null,
        'offset'    => $offset,
        'customers' => $customers->items(),
    ], 200);
}

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function dateWiseFilter(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'from' => 'required',
            'to' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $limit = $request['limit'] ?? 10;
        $offset = $request['offset'] ?? 1;

        if (!empty($request->from && $request->to)) {
            $result = $this->customer->when(($request->from && $request->to), function ($query) use ($request) {
                $query->whereBetween('date', [$request->from . ' 00:00:00', $request->to . ' 23:59:59']);
            })->where('tran_type', '=', 'Expense')->latest()->paginate($limit, ['*'], 'page', $offset);
            $data = [
                'total' => $result->total(),
                'limit' => $limit,
                'offset' => $offset,
                'customer' => $result->items(),
            ];
        } else {
            $data = [
                'total' => 0,
                'limit' => $limit,
                'offset' => $offset,
                'customer' => [],
            ];
        }
        return response()->json($data, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function totalTransaction(Request $request): JsonResponse
    {
        $limit = $request['limit'] ?? 10;
        $offset = $request['offset'] ?? 1;
        $result = $this->transection->where('customer_id', $request->customer_id)->with('account')->orderBy('id','desc')->paginate($limit, ['*'], 'page', $offset);
        $data = [
            'total' => $result->total(),
            'limit' => $limit,
            'offset' => $offset,
            'transfers' => $result->items(),
        ];
        return response()->json($data, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function transactionFilter(Request $request): JsonResponse
    {
        $limit = $request['limit'] ?? 10;
        $offset = $request['offset'] ?? 1;

        if ($request->account_id) {
            $transactions = $this->transection->where('account_id', $request->account_id)->latest()->paginate($request['limit'], ['*'], 'page', $request['offset']);
        } elseif ($request->transaction_type) {
            $transactions = $this->transection->where('tran_type', $request->transaction_type)->latest()->paginate($request['limit'], ['*'], 'page', $request['offset']);
        } elseif ($request->from && $request->to) {
            $transactions = $this->transection->whereBetween('date', [$request->from . ' 00:00:00', $request->to . ' 23:59:59'])->latest()->paginate($request['limit'], ['*'], 'page', $request['offset']);
        } else{
            $transactions = $this->transection->latest()->paginate($request['limit'], ['*'], 'page', $request['offset']);
        }

        $data = [
            'total' => $transactions->total(),
            'limit' => $limit,
            'offset' => $offset,
            'transfers' => $transactions->items()
        ];
        return response()->json($data, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function update_balance(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id'=>'required',
            'amount' => 'required',
            'account_id'=> 'required',
            'date' => 'required',
        ]);
        $customer = $this->customer->find($request->customer_id);

        if($customer->balance >= 0)
        {
            $account = $this->account->find(2);
            $transection = $this->transection;
            $transection->tran_type = 'Payable';
            $transection->account_id = $account->id;
            $transection->amount = $request->amount;
            $transection->description = $request->description;
            $transection->debit = 0;
            $transection->credit = 1;
            $transection->balance = $account->balance + $request->amount;
            $transection->date = $request->date;
            $transection->customer_id = $request->customer_id;
            $transection->save();

            $account->total_in = $account->total_in + $request->amount;
            $account->balance = $account->balance + $request->amount;
            $account->save();

            $receive_account = $this->account->find($request->account_id);
            $receive_transection = $this->transection;
            $receive_transection->tran_type = 'Income';
            $receive_transection->account_id = $receive_account->id;
            $receive_transection->amount = $request->amount;
            $receive_transection->description = $request->description;
            $receive_transection->debit = 0;
            $receive_transection->credit = 1;
            $receive_transection->balance = $receive_account->balance + $request->amount;
            $receive_transection->date = $request->date;
            $receive_transection->customer_id = $request->customer_id;
            $receive_transection->save();

            $receive_account->total_in = $receive_account->total_in + $request->amount;
            $receive_account->balance = $receive_account->balance + $request->amount;
            $receive_account->save();
        }else{
            $remaining_balance = $customer->balance + $request->amount;

            if($remaining_balance >= 0)
            {
                if($remaining_balance!=0)
                {
                    $payable_account = $this->account->find(2);
                    $payable_transection = $this->transection;
                    $payable_transection->tran_type = 'Payable';
                    $payable_transection->account_id = $payable_account->id;
                    $payable_transection->amount = $remaining_balance;
                    $payable_transection->description = $request->description;
                    $payable_transection->debit = 0;
                    $payable_transection->credit = 1;
                    $payable_transection->balance = $payable_account->balance + $remaining_balance;
                    $payable_transection->date = $request->date;
                    $payable_transection->customer_id = $request->customer_id;
                    $payable_transection->save();

                    $payable_account->total_in = $payable_account->total_in + $remaining_balance;
                    $payable_account->balance = $payable_account->balance + $remaining_balance;
                    $payable_account->save();
                }

                $receive_account = $this->account->find($request->account_id);
                $receive_transection = $this->transection;
                $receive_transection->tran_type = 'Income';
                $receive_transection->account_id = $request->account_id;
                $receive_transection->amount = $request->amount;
                $receive_transection->description = $request->description;
                $receive_transection->debit = 0;
                $receive_transection->credit = 1;
                $receive_transection->balance = $receive_account->balance + $request->amount;
                $receive_transection->date = $request->date;
                $receive_transection->customer_id = $request->customer_id;
                $receive_transection->save();

                $receive_account->total_in = $receive_account->total_in + $request->amount;
                $receive_account->balance = $receive_account->balance + $request->amount;
                $receive_account->save();


                $receivable_account = $this->account->find(3);
                $receivable_transaction = $this->transection;
                $receivable_transaction->tran_type = 'Receivable';
                $receivable_transaction->account_id = $receivable_account->id;
                $receivable_transaction->amount = -$customer->balance;
                $receivable_transaction->description = 'update customer balance';
                $receivable_transaction->debit = 1;
                $receivable_transaction->credit = 0;
                $receivable_transaction->balance = $receivable_account->balance + $customer->balance;
                $receivable_transaction->date = $request->date;
                $receivable_transaction->customer_id = $request->customer_id;
                $receivable_transaction->save();

                $receivable_account->total_out = $receivable_account->total_out - $customer->balance;
                $receivable_account->balance = $receivable_account->balance + $customer->balance;
                $receivable_account->save();

            }else{

                $receive_account = $this->account->find($request->account_id);
                $receive_transection = $this->transection;
                $receive_transection->tran_type = 'Income';
                $receive_transection->account_id = $receive_account->id;
                $receive_transection->amount = $request->amount;
                $receive_transection->description = $request->description;
                $receive_transection->debit = 0;
                $receive_transection->credit = 1;
                $receive_transection->balance = $receive_account->balance + $request->amount;
                $receive_transection->date = $request->date;
                $receive_transection->customer_id = $request->customer_id;
                $receive_transection->save();

                $receive_account->total_in = $receive_account->total_in + $request->amount;
                $receive_account->balance = $receive_account->balance + $request->amount;
                $receive_account->save();

                $receivable_account = $this->account->find(3);
                $receivable_transaction = new $this->transection;
                $receivable_transaction->tran_type = 'Receivable';
                $receivable_transaction->account_id = $receivable_account->id;
                $receivable_transaction->amount = $request->amount;
                $receivable_transaction->description = 'update customer balance';
                $receivable_transaction->debit = 1;
                $receivable_transaction->credit =0;
                $receivable_transaction->balance = $receivable_account->balance - $request->amount;
                $receivable_transaction->date = $request->date;
                $receivable_transaction->customer_id = $request->customer_id;
                $receivable_transaction->save();

                $receivable_account->total_out = $receivable_account->total_out + $request->amount;
                $receivable_account->balance = $receivable_account->balance - $request->amount;
                $receivable_account->save();
            }

        }
        $customer->balance = $customer->balance + $request->amount;
        $customer->save();
        return response()->json([
            'message' => 'Customer balance updated successfully',
        ], 200);
    }

}
