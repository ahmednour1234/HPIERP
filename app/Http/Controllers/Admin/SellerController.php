<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Seller;
use App\Models\Order;
use Brian2694\Toastr\Facades\Toastr;
use App\CPU\Helpers;
use App\Models\Account;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Region;
use App\Models\SellerCategory;
use App\Models\SellerCustomer;
use App\Models\SellerRegion;
use App\Models\StorageSeller;
use App\Models\AdminSeller;
use App\Models\Admin;
use App\Models\Storage;
use App\Models\Shift;
use App\Models\SellerPrice;
use App\Models\Store;
use App\Models\Installment;
use App\Models\HistoryInstallment;
use App\Models\Transection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;


use function App\CPU\translate;

class SellerController extends Controller
{
    public function __construct(
        private Seller $seller,
        private SellerCategory $cat,
        private SellerCustomer $cus,
    private StorageSeller $storages,
        private SellerRegion $region,
        private SellerPrice $price,
        private Store $vehicle,
        private Category $category,
        private Customer $customer,
                private Account $account,
                private Transection $transection,
     private Installment $installment,
          private HistoryInstallment $history_installment
    ){}

    public function index()
    {
        $regions = Region::all();
        $categories = $this->category->where(['position' => 0])->where('type',1)->where('status',1)->get();
        $customers = $this->customer->get();
        $storages = Storage::all();
        $vehicles = $this->vehicle->whereNull('seller_id')->get();
                $shifts =  Shift::where('active',1)->get();

        return view('admin-views.seller.index', compact('regions', 'categories', 'vehicles','customers','storages','shifts'));
    }


public function store(Request $request): RedirectResponse
{
    $request->validate([
        'f_name' => 'required',
        'l_name' => 'required',
        'email' => 'required|email|unique:admins,email',
        'password' => 'required|min:8',
        'mandob_code' => 'required',
        'vehicle_code' => 'required',
        'type' => 'required',
        'salary' => 'required|numeric',
        'precent_of_sales' => 'required|numeric',
        'latitude' => 'nullable',
        'longitude' => 'nullable',
        'store_id' => 'required',
        'shift_id' => 'nullable|array',
        'admins' => 'nullable|array',
        'cats' => 'required_if:type,seller|array',
        'reg' => 'required_if:type,seller|array',
        'customers' => 'nullable|array',
    ]);

    DB::beginTransaction();

    try {
        $seller = new Seller();
        $seller->f_name = $request->f_name;
        $seller->l_name = $request->l_name;
        $seller->email = $request->email;
        $seller->password = Hash::make($request->password);
        $seller->mandob_code = $request->mandob_code;
        $seller->vehicle_code = $request->vehicle_code;
        $seller->type = $request->type;
        $seller->salary = $request->salary;
        $seller->precent_of_sales = $request->precent_of_sales;
        $seller->latitude = $request->latitude ?? 0;
        $seller->longitude = $request->longitude ?? 0;
        $seller->holidays = $request->holidays;
        $seller->visitors = $request->visitors;
        $seller->shift_id = json_encode($request->shift_id ?? []);
        $seller->role = 'seller';

        $permissions = [
            'dashboard', 'stock', 'store', 'admin', 'regions',
            'supplier', 'pos', 'cat', 'unit', 'product', 'stock_limit',
            'coupons', 'customer', 'seller', 'storage', 'setting',
            'requests', 'notification', 'tracking', 'reports', 'vehicle_stock',
            'visit', 'rating', 'sectionsalary', 'accounts', 'sales','hr','attendance','production','install'
        ];

        foreach ($permissions as $permission) {
            $seller->$permission = $request->has($permission) ? 1 : 0;
        }

        $seller->save();

        $this->vehicle->where('store_id', $request->vehicle_code)->update(['seller_id' => $seller->id]);

        if ($request->type === 'seller') {
            foreach ($request->cats as $catId) {
                $cat = new SellerCategory();
                $cat->seller_id = $seller->id;
                $cat->cat_id = $catId;
                $cat->save();
            }

            if (!empty($request->customers)) {
                foreach ($request->customers as $customerId) {
                    $cus = new SellerCustomer();
                    $cus->seller_id = $seller->id;
                    $cus->customer_id = $customerId;
                    $cus->save();
                }
            }

            foreach ($request->reg as $regionId) {
                $reg = new SellerRegion();
                $reg->seller_id = $seller->id;
                $reg->region_id = $regionId;
                $reg->save();
            }
        } elseif (in_array($request->type, ['manager', 'bigmanager']) && !empty($request->admins)) {
            $adminSellers = AdminSeller::whereIn('seller_id', $request->admins)->pluck('seller_id');

            $categories = SellerCategory::whereIn('seller_id', $adminSellers)->pluck('cat_id')->unique();
            foreach ($categories as $catId) {
                $cat = new SellerCategory();
                $cat->seller_id = $seller->id;
                $cat->cat_id = $catId;
                $cat->save();
            }

            $customers = SellerCustomer::whereIn('seller_id', $adminSellers)->pluck('customer_id')->unique();
                        $validCustomers = \App\Models\Customer::whereIn('id', $request->customers)->pluck('id')->toArray();

            foreach ($validCustomers as $customerId) {
                $cus = new SellerCustomer();
                $cus->seller_id = $seller->id;
                $cus->customer_id = $customerId;
                $cus->save();
            }

            $regions = SellerRegion::whereIn('seller_id', $adminSellers)->pluck('region_id')->unique();
            foreach ($regions as $regionId) {
                $reg = new SellerRegion();
                $reg->seller_id = $seller->id;
                $reg->region_id = $regionId;
                $reg->save();
            }
        }

        $storage = new StorageSeller();
        $storage->seller_id = $seller->id;
        $storage->storage_id = $request->store_id;
        $storage->save();

        $adminSeller = new AdminSeller();
        $adminSeller->admin_id = auth()->guard('admin')->id();
        $adminSeller->seller_id = $seller->id;
        $adminSeller->save();

        if (in_array($request->type, ['manager', 'bigmanager']) && !empty($request->admins)) {
            foreach ($request->admins as $adminSellerId) {
                $reverse = new AdminSeller();
                $reverse->seller_id = $adminSellerId;
                $reverse->admin_id = $seller->id;
                $reverse->save();
            }
        }

        DB::commit();

        Toastr::success(translate('Seller Added successfully'));
        return back();

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Error adding seller: ' . $e->getMessage());
        return back()->withErrors(['error' => 'حدث خطأ أثناء إضافة المندوب. حاول مرة أخرى. ' . $e->getMessage()]);
    }
}

    /**
     * كل من يتبع هذا المستخدم في شجرة الإدارة: مرؤوسوه المباشرون، ومرؤوسو
     * مديريه الفرعيين، وهكذا نزولًا. تُحسب بالتكرار لا باستعلام متداخل حتى
     * تعمل على MySQL دون الحاجة إلى Recursive CTE.
     */
    private function descendantSellerIds($adminId): array
    {
        $all      = [];
        $frontier = [(int) $adminId];

        // حارس ضد الحلقات: البيانات قد تحوي دورة (أ يدير ب وب يدير أ).
        $guard = 0;

        while (!empty($frontier) && $guard++ < 20) {
            $next = AdminSeller::whereIn('admin_id', $frontier)
                ->pluck('seller_id')
                ->map(fn ($v) => (int) $v)
                ->all();

            $next = array_values(array_diff(array_unique($next), $all));

            if (empty($next)) {
                break;
            }

            $all      = array_merge($all, $next);
            $frontier = $next;
        }

        return array_values(array_unique($all));
    }

    public function list(Request $request)
{
    $query_param = [];
    $adminId = Auth::guard('admin')->id(); // Get the authenticated admin's ID
            $accounts = $this->account->orderBy('id')->get();

    // المناديب التابعون للمستخدم الحالي، بما فيهم التابعون لمديرين يتبعونه.
    //
    // كان الاستعلام يربط admin_sellers على admin_id مباشرةً فقط، فيرى المدير
    // مرؤوسيه المباشرين دون مرؤوسي مديريه الفرعيين، ولا يستطيع تعديلهم —
    // وهو سبب أن التعديل لا يعمل إلا من Super Admin.
    $scopedIds = $this->descendantSellerIds($adminId);

    // العرض يعتمد على $seller->seller_id (كان يأتي من الـ join القديم)، فنُبقيه
    // كاسم بديل لـ admins.id بدل ربط جدول لم نعد نحتاجه.
    $sellers = $this->seller
                    ->select('admins.*', 'admins.id as seller_id')
                    ->whereIn('admins.id', $scopedIds)
                    ->where('admins.role', 'seller'); // Ensure that only sellers are retrieved

    // Search functionality
    $search = $request['search'];
    if ($request->has('search')) {
        $key = $request['search'];
        $sellers = $sellers->where(function ($q) use ($key) {
            $q->orWhere('f_name', 'like', "%{$key}%")
              ->orWhere('l_name', 'like', "%{$key}%")
              ->orWhere('email', 'like', "%{$key}%"); // Add more columns if needed
        });
        $query_param = ['search' => $request['search']];
    }

    // Paginate the sellers
    $sellers = $sellers->paginate(Helpers::pagination_limit())->appends($query_param);

    return view('admin-views.seller.list', compact('sellers', 'search','accounts'));
}


public function prices(Request $request, $id): View|Factory|Application|RedirectResponse
{
    $price = $this->price;

    $products = [];

    foreach (Seller::find($id)->cats as $item) {
        $products = array_merge($item->cat->products->toArray(), $products);
    }

    if ($request->has('price')) {
        $request->validate([
            'price' => 'required|numeric',
            'product_id' => 'required',
            'seller_id' => 'required',
        ]);

        // تحقق مما إذا كان السعر موجودًا بالفعل
        $existingPrice = $price->where('product_id', $request->product_id)
            ->where('seller_id', $request->seller_id)
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
            $price->seller_id = $request->seller_id;
            $price->save();
            Toastr::success(translate('product_price_added_successfully'));
        }

        return back();
    }

    $prices = $price->where('seller_id', $id)->latest()->paginate(Helpers::pagination_limit());
    return view('admin-views.seller.prices', compact('prices'), ['seller_id' => $id, 'products' => $products]);
}

    
    public function edit_price(Request $request, $seller_id, $price_id): View|Factory|Application|RedirectResponse
    {
        $price = $this->price->find($price_id);

        if($request->has('price')) {
            $request->validate([
                'price' => 'required|numeric',
                'product_id' => 'required',
            ]);

            $price->price = $request->price;
            $price->product_id = $request->product_id;
            $price->seller_id = $seller_id;
            $price->update();

            Toastr::success(translate('product_price_add_successfully'));
            return back();
        }

        return view('admin-views.seller.edit_price', compact('seller_id', 'price'));
    }

    public function delete_price($id): View|Factory|Application|RedirectResponse
    {
        $price = $this->price->find($id);
        $price->delete();
        Toastr::success(translate('product_price_removed_successfully'));
        return back();
    }

    /**
     * تعديل بيانات المندوب مقصور على Super Admin.
     *
     * كان أي حساب لديه صلاحية المناديب يستطيع تغيير بياناتهم. العلامة على
     * الحساب نفسه (is_super) لا على role، لأن role = 'admin' يشمل أمين
     * المخزن والمحاسب وغيرهم.
     */
    private function denyUnlessSuperAdmin()
    {
        $admin = Auth::guard('admin')->user();

        if ($admin && (int) ($admin->is_super ?? 0) === 1) {
            return null;
        }

        Toastr::error(\App\CPU\translate('تعديل بيانات المندوب متاح لمدير النظام فقط'));

        return redirect()->route('admin.seller.list');
    }

    public function edit(Request $request)
    {
        if ($deny = $this->denyUnlessSuperAdmin()) {
            return $deny;
        }

        $regions = Region::all();
        $categories = $this->category->where(['position' => 0])->where('type',1)->where('status',1)->get();
        $customers = $this->customer->get();
$seller = $this->seller->with('regions')->find($request->id);
                $storages = Storage::all();
        $vehicles = $this->vehicle->whereNull('seller_id')->orWhere('seller_id', $request->id)->get();
                        $shifts =  Shift::where('active',1)->get();

        // dd($vehicles);
        return view('admin-views.seller.edit',compact('seller', 'regions', 'categories','customers','vehicles','storages','shifts'));
    }
public function update(Request $request, $id): \Illuminate\Http\RedirectResponse
{
    // الحماية على الحفظ أيضًا؛ منع الشاشة وحدها يترك الطلب مفتوحًا.
    if ($deny = $this->denyUnlessSuperAdmin()) {
        return $deny;
    }

    /** @var \App\Models\Admin $seller */
    $seller = $this->seller->findOrFail($id);

    // ========= Helpers =========
    $toArray = function ($key) use ($request): array {
        $v = $request->input($key, []);
        if ($v instanceof \Illuminate\Support\Collection) $v = $v->all();
        if (is_null($v) || $v === '') return [];
        if (!is_array($v)) $v = [$v];
        // فقط أرقام صحيحة + فريد
        $v = array_map(fn($x) => (int)$x, array_filter($v, fn($x) => $x !== null && $x !== ''));
        return array_values(array_unique(array_filter($v, fn($x) => $x > 0)));
    };

    $validIds = function (string $table, array $ids): array {
        if (empty($ids)) return [];
        return \DB::table($table)->whereIn('id', $ids)->pluck('id')->map(fn($x) => (int)$x)->unique()->values()->all();
    };

    $insertChunked = function ($model, array $rows, int $size = 1000): void {
        foreach (array_chunk($rows, $size) as $chunk) {
            if (!empty($chunk)) $model->insert($chunk);
        }
    };

    // ========= Inputs =========
    $type       = (string) $request->input('type');
    $customers  = $toArray('customers');   // mandob/seller فقط
    $regions    = $toArray('reg');
    $cats       = $toArray('cats');        // mandob/seller فقط
    $admins     = $toArray('admins');      // مرؤوسو المدير
    $shiftIds   = $toArray('shift_id');

    // ========= Validation =========
    $rules = [
        'f_name'           => 'required',
        'l_name'           => 'required',
        'email'            => 'required|email|unique:admins,email,' . $seller->id,
        'mandob_code'      => 'required',
        'vehicle_code'     => 'required',
        'type'             => 'required|in:mandob,seller,manager,bigmanager',
        'salary'           => 'required|numeric',
        'precent_of_sales' => 'required|numeric',
        'latitude'         => 'nullable|numeric',
        'longitude'        => 'nullable|numeric',
        'store_id'         => 'required|integer',
        'shift_id'         => 'nullable|array',
        'shift_id.*'       => 'integer',
        'customers'        => 'nullable|array',
        'customers.*'      => 'integer',
        'reg'              => 'nullable|array',
        'reg.*'            => 'integer',
        'cats'             => 'nullable|array',
        'cats.*'           => 'integer',
        'admins'           => 'nullable|array',
        'admins.*'         => 'integer',
        'password'         => 'nullable|min:6',
    ];

    if (in_array($type, ['mandob', 'seller'], true)) {
        $rules['customers'] = 'required|array';
        $rules['reg']       = 'required|array';
        $rules['cats']      = 'required|array';
    }
    if (in_array($type, ['manager', 'bigmanager'], true)) {
        $rules['admins']    = 'required|array';
    }

    $request->validate($rules);

    \DB::beginTransaction();

    try {
        // 1) فك أي ربط سيارة سابق
        $this->vehicle->where('seller_id', $id)->update(['seller_id' => null]);

        // 2) تحديث بيانات أساسية
        $seller->f_name            = $request->f_name;
        $seller->l_name            = $request->l_name;
        $seller->email             = $request->email;
        $seller->mandob_code       = $request->mandob_code;
        $seller->vehicle_code      = $request->vehicle_code;
        $seller->type              = $type;
        $seller->salary            = $request->salary;
        $seller->precent_of_sales  = $request->precent_of_sales;
        $seller->latitude          = $request->latitude ?? 0;
        $seller->longitude         = $request->longitude ?? 0;
        $seller->holidays          = $request->holidays;
        $seller->visitors          = $request->visitors;
        $seller->shift_id          = json_encode($shiftIds, JSON_UNESCAPED_UNICODE);

        if ($request->filled('password')) {
            $seller->password = \Illuminate\Support\Facades\Hash::make($request->password);
        }

        // 3) صلاحيات
        $permissions = [
            'dashboard','stock','store','admin','regions',
            'supplier','pos','cat','unit','product','stock_limit',
            'coupons','customer','seller','storage','setting',
            'requests','notification','tracking','reports','vehicle_stock',
            'visit','rating','sectionsalary','accounts','sales','hr','attendance','production','install'
        ];
        foreach ($permissions as $permission) {
            $seller->{$permission} = $request->has($permission) ? 1 : 0;
        }

        $seller->save();

        // 4) ربط السيارة المختارة
        $this->vehicle
            ->where('store_id', $request->vehicle_code) // عدّل لو عمود مختلف
            ->update(['seller_id' => $id]);

        // 5) تنظيف ربطات قديمة
        $this->cus->where('seller_id', $id)->delete();       // seller_customers
        $this->region->where('seller_id', $id)->delete();    // seller_regions
        $this->storages->where('seller_id', $id)->delete();  // seller_storages
        $this->cat->where('seller_id', $id)->delete();       // seller_categories
        // تخصيص المناديب لا يُمسح إلا إذا كان الطلب يحمل قائمة جديدة.
        //
        // كان المسح غير مشروط بينما إعادة البناء مشروطة بـ type = manager،
        // فأي حساب type فيه NULL (وهي حالة خمسة حسابات هنا) كان يفقد كل
        // مناديبه بمجرد الحفظ.
        if ($request->has('admins')) {
            \App\Models\AdminSeller::where('admin_id', $id)->delete();
        }

        // 6) إعادة البناء حسب النوع
        if (in_array($type, ['mandob', 'seller'], true)) {
            // تحقق من وجود IDs في الجداول الأساسية (لتفادي FK errors)
            $validCustomerIds = $validIds('customers', $customers);
            $validRegionIds   = $validIds('regions',   $regions);
            $validCatIds      = $validIds('categories',$cats);

            // عمل الإدخالات chunked
            if (!empty($validCustomerIds)) {
                $rows = array_map(fn ($cid) => ['seller_id' => $id, 'customer_id' => $cid], $validCustomerIds);
                $insertChunked($this->cus, $rows);
            }

            if (!empty($validRegionIds)) {
                $rows = array_map(fn ($rid) => ['seller_id' => $id, 'region_id' => $rid], $validRegionIds);
                $insertChunked($this->region, $rows);
            }

            if (!empty($validCatIds)) {
                $rows = array_map(fn ($catId) => ['seller_id' => $id, 'cat_id' => $catId], $validCatIds);
                $insertChunked($this->cat, $rows);
            }

        } elseif (in_array($type, ['manager', 'bigmanager'], true) || $request->has('admins')) {
            // يُعاد البناء أيضًا حين تصل قائمة admins[] بصرف النظر عن type،
            // وإلا بقيت حسابات الإدارة (type = NULL) بلا تخصيص بعد الحفظ.
            $subordinateSellerIds = $validIds('admins', $admins);

            // اربط المدير بالمرؤوسين في جدول admin_sellers
            if (!empty($subordinateSellerIds)) {
                $rows = array_map(fn ($sellerId) => [
                    'seller_id' => $sellerId, // المرؤوس
                    'admin_id'  => $id,       // هذا المدير
                ], $subordinateSellerIds);
                $insertChunked(new \App\Models\AdminSeller, $rows);
            }

            // استيراد العملاء من seller_customers للمرؤوسين (ثم نفلتر على customers)
            $subCustomers = [];
            if (!empty($subordinateSellerIds)) {
                $subCustomers = \App\Models\SellerCustomer::query()
                    ->whereIn('seller_id', $subordinateSellerIds)
                    ->pluck('customer_id')
                    ->map(fn($x) => (int)$x)
                    ->unique()
                    ->values()
                    ->all();
            }
            $validCustomerIds = $validIds('customers', $subCustomers);

            if (!empty($validCustomerIds)) {
                $rows = array_map(fn ($cid) => ['seller_id' => $id, 'customer_id' => $cid], $validCustomerIds);
                $insertChunked($this->cus, $rows);
            }

            // استيراد الأقسام من seller_categories (ثم نفلتر على categories لو فيه FK)
            $subCats = [];
            if (!empty($subordinateSellerIds)) {
                $subCats = \App\Models\SellerCategory::query()
                    ->whereIn('seller_id', $subordinateSellerIds)
                    ->pluck('cat_id')
                    ->map(fn($x) => (int)$x)
                    ->unique()
                    ->values()
                    ->all();
            }
            $validCatIds = $validIds('categories', $subCats);

            if (!empty($validCatIds)) {
                $rows = array_map(fn ($catId) => ['seller_id' => $id, 'cat_id' => $catId], $validCatIds);
                $insertChunked($this->cat, $rows);
            }

            // (اختياري) توريث المناطق من المرؤوسين (ثم نفلتر على regions)
            $subRegions = [];
            if (!empty($subordinateSellerIds)) {
                $subRegions = \App\Models\SellerRegion::query()
                    ->whereIn('seller_id', $subordinateSellerIds)
                    ->pluck('region_id')
                    ->map(fn($x) => (int)$x)
                    ->unique()
                    ->values()
                    ->all();
            }
            $validRegionIds = $validIds('regions', $subRegions);

            if (!empty($validRegionIds)) {
                $rows = array_map(fn ($rid) => ['seller_id' => $id, 'region_id' => $rid], $validRegionIds);
                $insertChunked($this->region, $rows);
            }
        }

        // 7) المخزن (Pivot واحد)
        $storage = new $this->storages;
        $storage->seller_id  = $id;
        $storage->storage_id = (int) $request->store_id;
        $storage->save();

        \DB::commit();

        \Brian2694\Toastr\Facades\Toastr::success(translate('Seller updated successfully'));
        return redirect()->route('admin.seller.list');

    } catch (\Throwable $e) {
        \DB::rollBack();
        \Log::error('Error updating seller', [
            'seller_id' => $id,
            'error'     => $e->getMessage(),
            'trace'     => $e->getTraceAsString(),
        ]);

        return back()->withErrors([
            'error' => 'حدث خطأ أثناء تحديث المندوب. حاول مرة أخرى. ' . $e->getMessage()
        ])->withInput();
    }
}



    public function delete(Request $request): RedirectResponse
    {
        $seller = $this->seller->find($request->id);
        $seller->delete();

        Toastr::success(translate('Seller removed successfully'));
        return back();
    }
    public function update_balance(Request $request): RedirectResponse
{
    // Validate the incoming request data
    $request->validate([
        'seller_id' => 'required',
        'amount' => 'required|numeric|min:0',
        'account_id' => 'required',
        'date' => 'required|date',
        'description' => 'nullable|string', // Description is optional
        'img' => 'required', // Ensure image is required and valid
    ]);

    // Image upload logic
    $img = null;
    if ($request->hasFile('img')) {
        // dd('ahmed');
        $img = Helpers::update('shop/', null, 'png', $request->file('img'));
    }

    // Retrieve customer and account information
    $seller = $this->seller->find($request->seller_id);
    $amount = $request->amount;
    $account = Account::find($request->account_id);

    if ($account && $seller) {
        // Check if the account balance is sufficient
        if ($account->balance >= $amount) {
            // Process the transaction
            $transaction = new Transection();
            $transaction->tran_type = 13;
            $transaction->account_id = $account->id;
            $transaction->amount = $amount;
            $transaction->description = $request->description;
            $transaction->debit = $seller->balance;
            $transaction->credit = $seller->credit;
            $transaction->balance = $account->balance - $amount; // Update balance after deduction
            $transaction->date = $request->date;
            $transaction->seller_id = $request->seller_id;
            $transaction->img = $img;
            $transaction->save();

            // Update account and customer balances
            $account->total_out += $amount;
            $account->balance -= $amount;
            $account->save();

            $seller->balance -= $amount;
            $seller->save();

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
        'seller_id' => 'required',
        'amount' => 'required|numeric|min:0',
        'account_id' => 'required',
        'date' => 'required|date',
        'description' => 'nullable|string',
    ]);

    $seller = $this->seller->find($request->seller_id);
    $amount = $request->amount;
    $account = Account::find($request->account_id);
 $img = null;
    if ($request->hasFile('img')) {
        $img = Helpers::update('shop/', null, 'png', $request->file('img'));
    }
    DB::beginTransaction(); // Start the transaction

    try {
        if ($account->balance >= $amount) {
          

            // Process the transaction
            $transaction = new Transection;
            $transaction->tran_type = 26;
            $transaction->account_id = $account->id;
            $transaction->amount = $amount;
            $transaction->description = $request->description;
            $transaction->debit = $seller->balance;
            $transaction->credit = $seller->credit;
            $transaction->balance = $account->balance + $amount;
            $transaction->date = $request->date;
            $transaction->seller_id = $request->seller_id;
            $transaction->img = $img;
            $transaction->save();

            // Update account balance
            $account->total_in += $amount;
            $account->balance += $amount;
            $account->save();

            // Update customer credit
            $seller->credit -= $amount;
            $seller->save();

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
public function getAvailableAdmins(Request $request)
{
    $type = $request->type;

    // القائمة هنا هي المناديب/المديرون الذين سيتبعون المدير الجديد.
    //
    // كان فلتر 'manager' يستبعد كل من يظهر في admin_sellers.admin_id، أي كل
    // من يدير أحدًا بالفعل، فتخرج القائمة فارغة أو ناقصة ولا يمكن ضم مندوب
    // إلى مدير قائم — والبيانات تُظهر مديرين يتبعهم 13 و7 و6 مناديب، فالإدارة
    // المتعددة هي القاعدة لا الاستثناء. المطلوب استبعاد من له مدير بالفعل
    // (seller_id) لا من يَملك مرؤوسين (admin_id).
    if ($type === 'manager') {
        $alreadyManaged = AdminSeller::pluck('seller_id')->toArray();

        $admins = Admin::where('role', 'seller')
            ->whereNotIn('id', $alreadyManaged)
            ->orderBy('f_name')
            ->get();
    } elseif ($type === 'bigmanager') {
        // المدير الأعلى يضم المديرين. لا نستبعد من يدير مناديب بالفعل — فهذا
        // هو تعريف المدير — بل نعرضهم جميعًا ليختار منهم.
        $admins = Admin::where('type', 'manager')
            ->orderBy('f_name')
            ->get();
    } else {
        return response()->json([]);
    }

    return response()->json($admins);
}
}
