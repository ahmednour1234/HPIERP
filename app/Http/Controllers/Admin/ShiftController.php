<?php

namespace App\Http\Controllers\Admin;

use App\CPU\Helpers;
use App\Models\Shift;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use function App\CPU\translate;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class ShiftController extends Controller
{
    public function __construct(
        private Shift $branch
    ){}

    /**
     * @param Request $request
     * @return Application|Factory|View
     */
        public function add(Request $request)
    {
          $adminId = Auth::guard('admin')->id();
    $admin = DB::table('admins')->where('id', $adminId)->first();

        $categories = $this->branch;
        $query_param = [];
        $search = $request['search'];

        if($request->has('search')) {
            $key = explode(' ', $request['search']);
            $categories=$categories->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%");
                }
            });
            $query_param = ['search' => $request['search']];
        }

        $categories = $categories->latest()->paginate(Helpers::pagination_limit())->appends($query_param);
        return view('admin-views.shift.add',compact('categories', 'search'));
    }
   
    public function index(Request $request)
    {
          $adminId = Auth::guard('admin')->id();
    $admin = DB::table('admins')->where('id', $adminId)->first();

    
        $categories = $this->branch;
        $query_param = [];
        $search = $request['search'];

        if($request->has('search')) {
            $key = explode(' ', $request['search']);
            $categories=$categories->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%");
                }
            });
            $query_param = ['search' => $request['search']];
        }

        $categories = $categories->latest()->paginate(Helpers::pagination_limit())->appends($query_param);
        return view('admin-views.shift.index',compact('categories', 'search'));
    }
   
   

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
          $adminId = Auth::guard('admin')->id();
    $admin = DB::table('admins')->where('id', $adminId)->first();

        $request->validate([
            'name' => 'required|unique:shifts,name',
            'kilometer' => 'required',
            'start' => 'required',
            'end' => 'required',
        'breake' => 'required',
        'max'=>'required',
        'number_shifts'=>'nullable',
        'hours_of_each_shift'=>'nullable'

            

        ]);
        //uniqueness check
        $all_category = $this->branch;

        $category = $this->branch;
        $category->name = $request->name;
        $category->kilometer = $request->kilometer;
        $category->start = $request->start;
        $category->end = $request->end;
        $category->breake = $request->breake;
        $category->max = $request->max;
        $category->number_shifts = $request->number_shifts??0;
        $category->hours_of_each_shift = $request->hours_of_each_shift??0;
        $category->save();

        Toastr::success(translate('تم انشاء الشيفت بنجاح'));
        return back();
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function status(Request $request): RedirectResponse
    {
          $adminId = Auth::guard('admin')->id();
    $admin = DB::table('admins')->where('id', $adminId)->first();

   
    
        $category = $this->branch->find($request->id);
$category->active = $category->active ? 0 : 1;
        $category->save();
        Toastr::success(translate('تم تغيير حالة الشيفت'));
        return back();
    }

    /**
     * @param $id
     * @return Application|Factory|View
     */
    public function edit($id)
    {
          $adminId = Auth::guard('admin')->id();
    $admin = DB::table('admins')->where('id', $adminId)->first();

 
        $shift = $this->branch->find($id);
        return view('admin-views.shift.edit', compact('shift'));
    }

    /**
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function update(Request $request, $id): RedirectResponse
    {
        
        $request->validate([
            'name' =>'required|unique:shifts,name,'.$request->id
        ], [
            'name.required' => translate('Name is required'),
        ]);
        $category = $this->branch->find($id);
        $category->name = $request->name;
        $category->kilometer = $request->kilometer;
        $category->start = $request->start;
        $category->end = $request->end;
        $category->breake = $request->breake;
        $category->max = $request->max;
        $category->number_shifts = $request->number_shifts;
        $category->hours_of_each_shift = $request->hours_of_each_shift;
        $category->save();

        Toastr::success(translate('تم تحديث بيانات الشيفت بنجاح'));
        return redirect()->back();
    }



}
