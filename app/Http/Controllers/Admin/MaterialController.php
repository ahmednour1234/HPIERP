<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialBatch;
use App\Models\Unit;
use App\Models\Taxe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Brian2694\Toastr\Facades\Toastr;

class MaterialController extends Controller
{
public function index(Request $request)
{
    // 1) Extract filters
    $type   = $request->route('type');           // from URL segment, if any
    $search = $request->input('search');         // from ?search=...

    // 2) Build base query
    $query = Material::with('unit', 'tax')
                     ->latest();

    // 3) Apply type filter, if valid
    if ($type && in_array($type, ['raw', 'primary_packaging', 'secondary_packaging'])) {
        $query->where('material_type', $type);
    }

    // 4) Apply search filter on `name`, if provided
    if ($search) {
        $query->where('name', 'like', "%{$search}%");
    }

    // 5) Paginate (and preserve query string)
    $materials = $query->paginate(20)
                       ->appends([
                           'type'   => $type,
                           'search' => $search,
                       ]);

    // 6) Return view with current filters
    return view('admin-views.materials.index', compact('materials', 'type', 'search'));
}

    public function create(Request $request)
    {
        $material_type = $request->get('type');
        if (!in_array($material_type, ['raw', 'primary_packaging', 'secondary_packaging'])) {
            abort(404);
        }

        $units = Unit::all();
        $taxes = Taxe::all();
        return view('admin-views.materials.create', compact('units', 'taxes', 'material_type'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'unit_id' => 'required|exists:units,id',
            'tax_id' => 'nullable|exists:taxes,id',
            'pdf_file' => 'nullable|file|mimes:pdf',
            'material_type' => 'required|in:raw,primary_packaging,secondary_packaging',
        ]);

        $pdfName = null;
        if ($request->hasFile('pdf_file')) {
            $pdf = $request->file('pdf_file');
            $pdfName = \App\CPU\Helpers::upload('materials/pdf/', $pdf->getClientOriginalExtension(), $pdf);
        }

        $material = new Material();
        $material->name = $request->name;
        $material->description = $request->description;
        $material->unit_id = $request->unit_id;
        $material->tax_id = $request->tax_id;
        $material->material_type = $request->material_type;
        $material->pdf_file = $pdfName;
        $material->save();

        Toastr::success('تم إنشاء المادة بنجاح');
        return redirect()->back();
    }

  public function show(Request $request, $id)
{
    // جلب المادة مع الوحدة والضريبة
    $material = Material::with('unit', 'tax')->findOrFail($id);

    // بناء استعلام دفعات مرتبط بالمادة
    $batchesQuery = MaterialBatch::where('material_id', $id);

    // فلتر بحث بالكود الفريد
    if ($request->filled('batch_search')) {
        $batchesQuery->where('unique_code', 'like', '%'.$request->batch_search.'%');
    }

    // ترتيب
    switch ($request->get('sort')) {
        case 'quantity_asc':
            $batchesQuery->orderBy('quantity', 'asc');
            break;
        case 'quantity_desc':
            $batchesQuery->orderBy('quantity', 'desc');
            break;
        case 'expiry_asc':
            $batchesQuery->orderBy('expiration_date', 'asc');
            break;
        case 'expiry_desc':
            $batchesQuery->orderBy('expiration_date', 'desc');
            break;
        default:
            $batchesQuery->orderBy('created_at', 'desc');
    }

    // جلب النتائج
    $batches = $batchesQuery->get();

    return view('admin-views.materials.show', compact('material', 'batches'));
}
    public function edit($id)
    {
        $material = Material::findOrFail($id);
        $units = Unit::all();
        $taxes = Taxe::all();
        return view('admin-views.materials.edit', compact('material', 'units', 'taxes'));
    }

    public function update(Request $request, $id)
    {
        $material = Material::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'unit_id' => 'required|exists:units,id',
            'tax_id' => 'nullable|exists:taxes,id',
            'pdf_file' => 'nullable|file|mimes:pdf'
        ]);

        if ($request->hasFile('pdf_file')) {
            $pdf = $request->file('pdf_file');
            $pdfName = \App\CPU\Helpers::upload('materials/pdf/', $pdf->getClientOriginalExtension(), $pdf);
            $material->pdf_file = $pdfName;
        }

        $material->name = $request->name;
        $material->description = $request->description;
        $material->unit_id = $request->unit_id;
        $material->tax_id = $request->tax_id;
        $material->save();

        Toastr::success('تم تحديث بيانات المادة بنجاح');
        return redirect()->route('admin.materials.index');
    }
}
