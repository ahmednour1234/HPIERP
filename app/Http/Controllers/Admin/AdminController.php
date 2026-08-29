<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\AdminSeller;
use App\Models\Admin;
use App\Models\Seller;
use Brian2694\Toastr\Facades\Toastr;
use App\CPU\Helpers;
use App\Models\Category;
use App\Models\Region;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use function App\CPU\translate;

class AdminController extends Controller
{
    public function __construct(
        private Admin $admin,
        private Seller $seller,
        private AdminSeller $adminseller,
    ){}

   public function index()
{
    $sellers = Seller::all();

    // Pass data to the view using compact
    return view('admin-views.admin.index', compact('sellers'));
}

  public function showmap()
    {
        // خريطة المناديب. الإحداثيات تُخزَّن نصًا وكثير من الصفوف تحمل '0'
        // وهي نقطة في المحيط قبالة أفريقيا لا موقعًا حقيقيًا، فتُستبعد إلى
        // جانب الفارغة؛ وإلا ظهرت علامات في مكان خاطئ أو أزاحت مركز الخريطة.
        $admins = Admin::select('id', 'f_name', 'l_name', 'mandob_code', 'latitude', 'longitude')
                        ->whereNotNull('latitude')
                        ->whereNotNull('longitude')
                        ->where('latitude', '!=', '')
                        ->where('longitude', '!=', '')
                        ->whereRaw('CAST(latitude AS DECIMAL(12,8)) != 0')
                        ->whereRaw('CAST(longitude AS DECIMAL(12,8)) != 0')
                        ->get();

        return view('admin-views.map.index', compact('admins'));
    }
  public function store(Request $request): RedirectResponse
{
    $request->validate([
        'f_name' => 'required',
        'l_name' => 'required',
        'latitude' => 'nullable',
        'longitude' => 'nullable',
        'email'=> 'required|email|unique:admins',
        'password' => 'required|min:8',
    ]);

    DB::beginTransaction();

    try {
        $admin = $this->admin;
        $admin->f_name = $request->f_name;
        $admin->l_name = $request->l_name;
        $admin->latitude = $request->latitude;
        $admin->longitude = $request->longitude;
        $admin->email = $request->email;
        $admin->password = Hash::make($request->password);

        $permissions = [
            'supplier', 'dashboard', 'pos', 'stock', 'store', 'cat', 'unit', 'product',
            'stock_limit', 'coupons', 'customer', 'seller', 'admin', 'storage',
            'setting', 'requests', 'notification', 'tracking', 'regions', 'reports', 'vehicle_stock','visit','rating','sectionsalary','accounts','sales','hr','attendance','production','install'
        ];

        foreach ($permissions as $permission) {
            $admin->$permission = $request->has($permission) ? 1 : 0;
        }

        $admin->save();

        foreach ($request->sellers as $item) {
            $adminseller = new AdminSeller;
            $adminseller->seller_id = $item;
            $adminseller->admin_id = $admin->id;
            $adminseller->save();
        }

        DB::commit();

        Toastr::success(translate('Admin Added successfully'));
        return back();

    } catch (\Exception $e) {
        DB::rollBack();
        Toastr::error(translate('Failed to add admin. Please try again.'));
        return back()->withErrors(['error' => $e->getMessage()]);
    }
}
public function list(Request $request)
{
    $admins = $this->admin->where('role', 'admin')->paginate(Helpers::pagination_limit());
    return view('admin-views.admin.list', compact('admins'));
}
    public function edit(Request $request)
    {
        $regions = Region::all();
                $sellers = $this->seller->get();
        $categories = Category::all();
        $admin = $this->admin->where('id',$request->id)->first();
        return view('admin-views.admin.edit',compact('admin', 'regions', 'categories','sellers'));
    }

public function update(Request $request): RedirectResponse
{
    $admin = $this->admin->where('id', $request->id)->first();

    $request->validate([
        'f_name' => 'required',
        'l_name' => 'required',
        'latitude' => 'nullable',
        'longitude' => 'nullable',
        'email' => 'required|email|unique:admins,email,' . $admin->id,
    ]);

    DB::beginTransaction();

    try {
        $admin->f_name = $request->f_name;
        $admin->l_name = $request->l_name;
        $admin->latitude = $request->latitude;
        $admin->longitude = $request->longitude;
        $admin->email = $request->email;

        $permissions = [
            'supplier', 'dashboard', 'pos', 'stock', 'store', 'cat', 'unit', 'product',
            'stock_limit', 'coupons', 'customer', 'seller', 'admin', 'storage', 'setting',
            'requests', 'notification', 'tracking', 'regions', 'reports', 'vehicle_stock','visit','rating','sectionsalary','accounts','sales','hr','attendance','production','install'
        ];

        foreach ($permissions as $permission) {
            $admin->$permission = $request->has($permission) ? 1 : 0;
        }

        // Delete existing sellers associated with the admin to avoid duplicates
        AdminSeller::where('admin_id', $admin->id)->delete();

        foreach ($request->sellers as $item) {
            $adminseller = new AdminSeller;
            $adminseller->seller_id = $item;
            $adminseller->admin_id = $admin->id;
            $adminseller->save();
        }

        if ($request->password) {
            $request->validate([
                'password' => 'min:8'
            ]);
            $admin->password = Hash::make($request->password);
        }

        $admin->update();

        DB::commit();

        Toastr::success(translate('Admin updated successfully'));
        return redirect()->route('admin.admin.list');

    } catch (\Exception $e) {
        DB::rollBack();
        Toastr::error(translate('Failed to update admin. Please try again.'));
        return back()->withErrors(['error' => $e->getMessage()]);
    }
}

    public function delete(Request $request): RedirectResponse
    {
        $admin = $this->admin->find($request->id);
        $admin->delete();

        Toastr::success(translate('admin removed successfully'));
        return back();
    }
}
