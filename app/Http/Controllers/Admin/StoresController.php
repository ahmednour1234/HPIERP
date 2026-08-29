<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Store; // Assuming Store model exists
use Illuminate\Support\Facades\Validator;
use Brian2694\Toastr\Facades\Toastr;

class StoresController extends Controller
{
    public function index()
    {
        $stores = Store::all(); // Retrieve all stores
        return view('admin-views.store.index', compact('stores'));
    }

    public function create()
    {
        return view('admin-views.store.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'store_name1' => 'required',
            'store_code' => 'required|unique:stores', // Assuming store_code should be unique in the stores table
        ]);

        $store = new Store();
        $store->store_name1 = $request->store_name1;
        $store->store_code = $request->store_code;
        $store->save();

        // Optionally, you can add a success message here
        // Toastr::success('Store added successfully');

        return redirect()->route('admin.stores.index');
    }

    public function edit($store_id)
    {
        $store = Store::where('store_id', $store_id)->first();

        // بدون هذا الفحص كان القالب يقرأ ->store_id على null فتظهر صفحة
        // خطأ 500 بدل رسالة مفهومة عند فتح رابط قديم لمخزن محذوف.
        if (!$store) {
            Toastr::error(\App\CPU\translate('المخزن غير موجود'));
            return redirect()->route('admin.stores.index');
        }

        return view('admin-views.store.edit', compact('store'));
    }

public function update(Request $request, $store_id)
{
    $validator = Validator::make($request->all(), [
        'store_name1' => 'required',
        'store_code' => 'required|unique:stores,store_code,'.$store_id.',store_id', 
        // Assuming store_code should be unique in the stores table except for the current store being updated
    ]);

    if ($validator->fails()) {
        return redirect()->back()->withErrors($validator)->withInput();
    }

    $store = Store::where('store_id', $store_id)->first();
    $store->update([
        'store_name1' => $request->store_name1,
        'store_code' => $request->store_code,
    ]);

    // Add a success message
    session()->flash('success', 'Store updated successfully');
    return redirect()->route('admin.stores.index');
}


public function destroy($store_id)
{
    $store = Store::findOrFail($store_id);
    $store->delete();

    // Optionally, you can add a success message here
    // Toastr::success('Store deleted successfully');

    return redirect()->route('admin.stores.index');
}

}
