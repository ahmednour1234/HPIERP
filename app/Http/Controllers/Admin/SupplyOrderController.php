<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupplyOrder;
use App\Models\SupplyOrderItem;
use App\Models\SupplyItemBatch;
use Illuminate\Http\RedirectResponse;
use App\Models\MaterialBatch;
use App\Models\Material;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;        // ← import Carbon from the right namespace
use Exception;

class SupplyOrderController extends Controller
{
    /**
     * عرض كل أوامر التوريد
     */
    public function index()
    {
        $orders = SupplyOrder::with('admin','factory')->latest()->paginate(20);
        return view('admin-views.supply_orders.index', compact('orders'));
    }

    /**
     * نموذج إنشاء أمر توريد جديد
     */
    public function create()
    {
        $factories = Factory::pluck('name','id');
        $products  = Product::pluck('name','id');
                $materials  = Material::all();

        return view('admin-views.supply_orders.create', compact('factories','products','materials'));
    }

    /**
     * تخزين أمر توريد جديد (مسودة أو صادر)
     */
public function store(Request $request)
{
    $data = $request->validate([
        'factory_id'                     => 'required|exists:factories,id',
        'order_date'                     => 'required|date',
        'note'                           => 'nullable|string',
        'status'                         => 'required|in:draft,issued',
        'products'                       => 'required|array|min:1',
        'products.*.product_id'          => 'required|exists:products,id',
        'products.*.target_qty'          => 'required|numeric|min:0.001',
        'products.*.unit_cost'           => 'required|numeric|min:0',
        'products.*.comps'               => 'required_if:status,issued|array|min:1',
        'products.*.comps.*.material_id' => 'required_if:status,issued|exists:materials,id',
        'products.*.comps.*.req_qty'     => 'required_if:status,issued|numeric|min:0.001',
        'products.*.comps.*.comp_cost'   => 'required_if:status,issued|numeric|min:0',
        'products.*.comps.*.batches'                     => 'required_if:status,issued|array|min:1',
        'products.*.comps.*.batches.*.batch_id'          => 'required_if:status,issued|exists:material_batches,id',
        'products.*.comps.*.batches.*.unit_id'           => 'required_if:status,issued|exists:units,id',
        'products.*.comps.*.batches.*.qty'               => 'required_if:status,issued|numeric|min:0.001',
    ]);

    DB::transaction(function() use ($data) {
        // 1) إنشاء أمر التوريد
        $order = SupplyOrder::create([
            'factory_id'    => $data['factory_id'],
            'admin_id'      => auth('admin')->id(),
            'order_date'    => $data['order_date'],
            'status'        => $data['status'],
            'note'          => $data['note'] ?? null,
            'expected_cost' => collect($data['products'])
                                  ->sum(fn($p) => $p['target_qty'] * $p['unit_cost']),
        ]);

        // مُحوّل الكميات
        $converter = new Unit();

        // 2) لكل منتج أنشئ SupplyOrderItem
        foreach ($data['products'] as $prod) {
            $item = SupplyOrderItem::create([
                'supply_order_id'        => $order->id,
                'product_id'             => $prod['product_id'],
                'product_quantity'       => $prod['target_qty'],
                'expected_cost_per_unit' => $prod['unit_cost'],
            ]);

            // 3) لجميع المكوّنات والدفعات
            foreach ($prod['comps'] as $comp) {
                foreach ($comp['batches'] as $b) {
                    $batch = MaterialBatch::findOrFail($b['batch_id']);

                    // تحميل الوحدتين
                    $fromUnit  = Unit::findOrFail($b['unit_id']);
                    $toUnit    = $batch->unitRelation;

                    // تحويل الكمية
                    $usedQty = $converter->convertQuantity($b['qty'], $fromUnit, $toUnit);

                    // إنقاص المخزون فقط عند التنفيذ الفعلي
                    if ($data['status'] === 'issued') {
                        if ($batch->quantity < $usedQty) {
                            throw new \Exception("الكمية غير كافية في الدفعة {$batch->unique_code}");
                        }
                        $batch->decrement('quantity', $usedQty);
                    }

                    // تسجيل كل دفعة مستخدمة
                    SupplyItemBatch::create([
                        'supply_order_item_id' => $item->id,
                        'material_batch_id'    => $batch->id,
                        'material_id'          => $comp['material_id'],
                        'unit_id'              => $b['unit_id'],
                        'quantity'             => $b['qty'],
                    ]);
                }
            }
        }
    });

    Toastr::success('تم حفظ أمر التوريد بنجاح.');
    return redirect()->route('admin.supply_orders.index');
}
  public function issue(SupplyOrder $order)
    {
        // 1) إذا لم تكن في المسودة، نظهر خطأ
        if ($order->status !== 'draft') {
            Toastr::error('الأمر ليس في حالة مسودة.');
            return back();
        }

        // 2) تنفيذ داخل معاملة
        DB::transaction(function() use ($order) {
            $converter = new Unit();

            foreach ($order->items as $item) {
                foreach ($item->batches as $sib) {
                    $batch    = $sib->materialBatch;      // علاقة MaterialBatch
                    $fromUnit = $sib->unit;               // الوحدة المستخدمة
                    $toUnit   = $batch->unitRelation;     // الوحدة الأساسية في الدفعة

                    // 3) تحويل الكمية إلى الوحدة الأساسية
                    $usedQty = $converter->convertQuantity(
                        $sib->quantity,
                        $fromUnit,
                        $toUnit
                    );

                    // 4) تحقق من المخزون الكافي
                    if ($batch->quantity < $usedQty) {
                        throw new Exception(
                            "الكمية غير كافية في الدفعة {$batch->unique_code}"
                        );
                    }

                    // 5) نقص الكمية من المخزون
                    $batch->decrement('quantity', $usedQty);
                }
            }

            // 6) حدّث حالة الأمر ووقت التنفيذ
            $order->update([
                'status'     => 'completed',
                'updated_at' => Carbon::now(),
            ]);
        });

        // 7) نجاح العملية
        Toastr::success('تم تنفيذ الأمر بنجاح.');
        return back();
    }
    /**
     * نموذج تعديل أمر توريد قائم
     */
    public function edit($id)
    {
        $order      = SupplyOrder::with('items.batches')->findOrFail($id);
        $factories  = Factory::pluck('name','id');
        $products   = Product::pluck('name','id');
        $allBatches = MaterialBatch::with('unitRelation')
                        ->whereIn('material_id', $order->items->pluck('product_id'))
                        ->get()
                        ->groupBy('material_id');

        return view('admin.supply_orders.edit', compact(
            'order','factories','products','allBatches'
        ));
    }

    /**
     * عرض تفاصيل أمر توريد
     */
    public function show($id)
    {
        $order = SupplyOrder::with([
            'items.product',
            'items.batches.materialBatch.unitRelation',
        ])->findOrFail($id);

        $allBatches = MaterialBatch::with('unitRelation')
            ->whereIn('material_id', $order->items->pluck('product_id'))
            ->get()
            ->groupBy('material_id');

        return view('admin-views.supply_orders.show', compact('order','allBatches'));
    }

    /**
     * تحديث أمر توريد (مثلاً من مسودة إلى صادر)
     */
    public function update(Request $request, $id)
    {
        $order = SupplyOrder::findOrFail($id);

        $data = $request->validate([
            'factory_id'               => 'required|exists:factories,id',
            'order_date'               => 'required|date',
            'note'                     => 'nullable|string',
            'status'                   => 'required|in:draft,issued,completed',
            'items'                    => 'required|array|min:1',
            'items.*.product_id'       => 'required|exists:products,id',
            'items.*.product_quantity' => 'required|numeric|min:0.001',
            'items.*.expected_cost_per_unit' => 'required|numeric|min:0',
            'items.*.batches'            => 'required_if:status,issued|array|min:1',
            'items.*.batches.*.batch_id' => 'required_if:status,issued|exists:material_batches,id',
            'items.*.batches.*.unit_id'  => 'required_if:status,issued|exists:units,id',
            'items.*.batches.*.qty'      => 'required_if:status,issued|numeric|min:0.001',
        ]);

        if ($order->status === 'issued') {
            Toastr::info('تم إصدار هذا الأمر مسبقاً.');
            return back();
        }

        DB::transaction(function() use ($order, $data) {
            $order->update([
                'factory_id'    => $data['factory_id'],
                'order_date'    => $data['order_date'],
                'status'        => $data['status'],
                'note'          => $data['note'] ?? null,
                'expected_cost' => collect($data['items'])
                                    ->sum(fn($it)=> $it['product_quantity'] * $it['expected_cost_per_unit']),
            ]);

            // حذف تفاصيل ودفعات قديمة
            $order->items()->delete();

            foreach ($data['items'] as $item) {
                $soi = SupplyOrderItem::create([
                    'supply_order_id'        => $order->id,
                    'product_id'             => $item['product_id'],
                    'product_quantity'       => $item['product_quantity'],
                    'expected_cost_per_unit' => $item['expected_cost_per_unit'],
                ]);

                if ($data['status'] === 'issued') {
                    foreach ($item['batches'] as $b) {
                        $batch = MaterialBatch::findOrFail($b['batch_id']);
                        $usedQty = Unit::convertQuantity(
                            $b['qty'],
                            Unit::findOrFail($b['unit_id']),
                            $batch->unitRelation
                        );
                        if ($batch->quantity < $usedQty) {
                            throw new \Exception("الكمية غير كافية في الدفعة {$batch->unique_code}");
                        }
                        $batch->decrement('quantity', $usedQty);
                        SupplyItemBatch::create([
                            'supply_order_item_id' => $soi->id,
                            'material_batch_id'    => $batch->id,
                            'unit_id'              => $b['unit_id'],
                            'quantity'             => $b['qty'],
                        ]);
                    }
                }
            }
        });

        Toastr::success('تم تحديث أمر التوريد.');
        return redirect()->route('admin.supply_orders.show', $order->id);
    }
    public function getBatches($materialId)
{
    $batches = MaterialBatch::with('unitRelation')
        ->where('material_id', $materialId)
        ->where('quantity', '>', 0)
        ->get()
        ->map(function($b){
            return [
                'id'       => $b->id,
                'code'     => $b->unique_code,
                'qty'      => $b->quantity,
                'unit'     => $b->unitRelation->unit_type,
                'exp_date' => $b->expiration_date,
            ];
        });

    return response()->json($batches);
}
  public function units(int $materialId)
    {
        // 1) القيمة الافتراضية للـ unit_id من جدول materials
        $defaultUnitId = DB::table('material_batches')
            ->where('id', $materialId)
            ->value('unit_id');

        if (! $defaultUnitId) {
            return response()->json(['message' => 'المادة غير موجودة أو بلا وحدة أساسية'], 404);
        }

        // 2) جلب base_unit_id من جدول units
        $baseUnitId = DB::table('units')
            ->where('id', $defaultUnitId)
            ->value('base_unit_id');

        // إذا لم يكن هناك base_unit_id نستخدم defaultUnitId نفسه
        $baseUnitId = $baseUnitId ?: $defaultUnitId;

        // 3) جلب جميع الوحدات التي تنتمي لنفس الأساس
        $units = DB::table('units')
            ->where('base_unit_id', $baseUnitId)
            ->orWhere('id', $baseUnitId)
            ->select('id', 'unit_type as name', 'conversion_rate')
            ->get();

        return response()->json($units);
    }
}
