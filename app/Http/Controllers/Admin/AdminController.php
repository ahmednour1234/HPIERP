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
use App\Models\Role;
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
    $roles = Role::orderBy('label')->get();

    return view('admin-views.admin.index', compact('sellers', 'roles'));
}

  public function showmap()
    {
        // خريطة المناديب. الإحداثيات تُخزَّن نصًا وكثير من الصفوف تحمل '0'
        // وهي نقطة في المحيط قبالة أفريقيا لا موقعًا حقيقيًا، فتُستبعد إلى
        // جانب الفارغة؛ وإلا ظهرت علامات في مكان خاطئ أو أزاحت مركز الخريطة.
        // الفحص في PHP لا في SQL: CAST(... AS DECIMAL) يختلف سلوكه بين
        // MariaDB وSQLite، فمرّ صفّ إلى الإنتاج ظهر في خليج غينيا بينما
        // كانت نسخة التطوير نظيفة.
        $admins = Admin::select('id', 'f_name', 'l_name', 'mandob_code', 'latitude', 'longitude')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', '')
            ->where('longitude', '!=', '')
            ->get()
            ->filter(function ($admin) {
                if (!is_numeric($admin->latitude) || !is_numeric($admin->longitude)) {
                    return false;
                }

                $lat = (float) $admin->latitude;
                $lng = (float) $admin->longitude;

                // صفر في أيٍّ منهما يعني إحداثيًّا غير مسجَّل، لا نقطة على
                // خطّ الاستواء أو خطّ غرينتش.
                if (abs($lat) < 0.000001 || abs($lng) < 0.000001) {
                    return false;
                }

                return abs($lat) <= 90 && abs($lng) <= 180;
            })
            ->values();

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

        $admin->save();

        // الصلاحيات صارت أدوارًا. الأعمدة المنطقية القديمة لم يعد يقرأها
        // أحد، وكتابتها هنا كانت توهم أنها تفعل شيئًا.
        $admin->roles()->sync($request->input('roles', []));

        foreach ($request->input('sellers', []) as $item) {
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
    $admins = $this->admin->where('role', 'admin')
        ->with('roles:id,label')
        ->paginate(Helpers::pagination_limit());
    return view('admin-views.admin.list', compact('admins'));
}
    public function edit(Request $request)
    {
        $regions = Region::all();
                $sellers = $this->seller->get();
        $categories = Category::all();
        $admin = $this->admin->with('roles:id')->where('id',$request->id)->first();
        $roles = Role::orderBy('label')->get();

        return view('admin-views.admin.edit',compact('admin', 'regions', 'categories','sellers','roles'));
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

        // السوبر أدمن يتجاوز الأدوار أصلًا، فلا تُغيَّر أدواره من هنا
        // ولا يُترك بلا شيء بإرسال نموذج فارغ.
        if (! $admin->is_super) {
            $admin->roles()->sync($request->input('roles', []));
        }

        // Delete existing sellers associated with the admin to avoid duplicates
        AdminSeller::where('admin_id', $admin->id)->delete();

        foreach ($request->input('sellers', []) as $item) {
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
