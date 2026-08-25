<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Unit;
use App\CPU\Helpers;
use Brian2694\Toastr\Facades\Toastr;
use function App\CPU\translate;

class UnitController extends Controller
{
    public function __construct(
        private Unit $unit
    ){}

    /**
     * @return Application|Factory|View
     */
    public function index(): Factory|View|Application
    {
        $units = $this->unit->latest()->paginate(Helpers::pagination_limit());
            $base_units = Unit::where('is_base', true)->get(); // فقط الوحدات الأساسية

        return view('admin-views.unit.index',compact('units','base_units'));
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
public function store(Request $request): RedirectResponse
{
    // ✅ التحقق من صحة البيانات
    $request->validate([
        'unit_type'        => 'required|string|max:255|unique:units,unit_type',
        'symbol'           => 'nullable|string|max:50',
        'is_base'          => 'required|boolean',
        'base_unit_id'     => 'nullable|exists:units,id',
        'conversion_rate'  => 'required|numeric|min:0',
    ], [
        'unit_type.required'        => 'اسم الوحدة مطلوب',
        'unit_type.unique'          => 'اسم الوحدة موجود مسبقًا',
        'symbol.max'                => 'رمز الوحدة يجب ألا يتجاوز 50 حرفًا',
        'conversion_rate.required'  => 'معامل التحويل مطلوب',
        'conversion_rate.numeric'   => 'معامل التحويل يجب أن يكون رقمًا',
        'conversion_rate.min'       => 'معامل التحويل يجب أن يكون أكبر من أو يساوي 0',
        'base_unit_id.exists'       => 'الوحدة الأساسية غير موجودة',
    ]);

    // ✅ إنشاء الوحدة
    $unit = new Unit();
    $unit->unit_type       = $request->unit_type;
    $unit->symbol          = $request->symbol;
    $unit->is_base         = $request->is_base;
    $unit->base_unit_id    = $request->is_base ? null : $request->base_unit_id;
    $unit->conversion_rate = $request->conversion_rate ?? 1;

    $unit->save();

    // ✅ إشعار النجاح
    Toastr::success('تمت إضافة وحدة القياس بنجاح', 'تم');

    return redirect()->back();
}

    /**
     * @param $id
     * @return Application|Factory|View
     */
    public function edit($id): Factory|View|Application
    {
        $unit = $this->unit->find($id);
    $base_units = Unit::where('is_base', true)->where('id', '!=', $unit->id)->get(); // استبعاد نفسه كوحدة أساسية

        return view('admin-views.unit.edit',compact('unit','base_units'));
    }

    /**
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
public function update(Request $request, $id): RedirectResponse
{
    $unit = $this->unit->findOrFail($id);

    // ✅ التحقق من صحة البيانات
    $request->validate([
        'unit_type'        => 'required|string|max:255|unique:units,unit_type,' . $unit->id,
        'symbol'           => 'nullable|string|max:50',
        'is_base'          => 'required|boolean',
        'base_unit_id'     => 'nullable|exists:units,id',
        'conversion_rate'  => 'required|numeric|min:0',
    ], [
        'unit_type.required'        => 'اسم الوحدة مطلوب',
        'unit_type.unique'          => 'اسم الوحدة مستخدم مسبقًا',
        'symbol.max'                => 'رمز الوحدة يجب ألا يتجاوز 50 حرفًا',
        'conversion_rate.required'  => 'معامل التحويل مطلوب',
        'conversion_rate.numeric'   => 'معامل التحويل يجب أن يكون رقمًا',
        'conversion_rate.min'       => 'معامل التحويل يجب أن يكون أكبر من أو يساوي 0',
        'base_unit_id.exists'       => 'الوحدة الأساسية المحددة غير موجودة',
    ]);

    // ✅ التحديث
    $unit->unit_type       = $request->unit_type;
    $unit->symbol          = $request->symbol;
    $unit->is_base         = $request->is_base;
    $unit->base_unit_id    = $request->is_base ? null : $request->base_unit_id;
    $unit->conversion_rate = $request->conversion_rate;

    $unit->save();

    // ✅ إشعار النجاح
    Toastr::success('تم تحديث وحدة القياس بنجاح', 'تم');

    return redirect()->route('admin.unit.index');
}

    /**
     * @param $id
     * @return RedirectResponse
     */
    public function delete($id): RedirectResponse
    {
        $unit = $this->unit->find($id);
        $unit->delete();

        Toastr::success(translate('Unit Type removed'));
        return back();
    }
}
