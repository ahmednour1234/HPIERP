<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use App\Models\Transection;
use App\Models\ProductionOrderProduct;
use App\Models\ProductionOrderComponent;
use App\Models\ProductionOrderAdditionalCost;
use App\Models\SupplyOrder;
use App\Models\Product;
use App\Models\ProductExpire;
use App\Models\Account;
use App\Models\Unit;
use App\Models\Factory;
use App\Models\SupplyItemBatch;
use App\Models\MaterialBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Brian2694\Toastr\Facades\Toastr;


class ProductionOrderController extends Controller
{
    // قائمة أوامر الإنتاج
 public function index(Request $request)
{
    $query = ProductionOrder::with(['admin', 'supplyOrder', 'factory'])
        ->orderBy('created_at', 'desc');

    // فلتر حسب التاريخ من وإلى
    if ($request->filled('date_from')) {
        $query->whereDate('created_at', '>=', $request->date_from);
    }

    if ($request->filled('date_to')) {
        $query->whereDate('created_at', '<=', $request->date_to);
    }

    // فلتر حسب المصنع
    if ($request->filled('factory_id')) {
        $query->where('factory_id', $request->factory_id);
    }

    // فلتر حسب رقم أمر التوريد
    if ($request->filled('supply_order_id')) {
        $query->where('supply_order_id', $request->supply_order_id);
    }

    
$orders = $query->paginate(20);

// جمع الإجمالي والمدفوع
$totals = [
    'total_cash' => $query->sum('total_cash'),
    'paid'       => $query->sum('paid'),
];

$factories = Factory::all();
return view('admin-views.production_orders.index', compact('orders', 'factories', 'totals'));
}


    // نموذج إنشاء أمر إنتاج جديد
public function create(Request $request)
{
    $request->validate([
        'supply_order_id' => 'required|exists:supply_orders,id',
    ]);

    $supplyOrderId = $request->input('supply_order_id');

    $order = SupplyOrder::with([
        'items.product',
        'items.batches.materialBatch.unitRelation',
    ])->findOrFail($supplyOrderId);

    $allBatches = SupplyItemBatch::with('unitRelation')
        ->whereIn('material_id', $order->items->pluck('product_id'))
        ->get()
        ->groupBy('material_id');
        $accounts=Account::all();
    return view('admin-views.production_orders.create', compact('order', 'allBatches','accounts'));
}

    // جلب بيانات أمر التوريد مع التفاصيل
public function show($id)
{$order = ProductionOrder::with([
    'admin',
    'factory',
    'supplyOrder',
    'products.product', // جلب بيانات المنتج المرتبط
    'products.components.materialBatch.unitRelation', // مكونات كل منتج
    'additionalCosts', // التكاليف الإضافية
])->findOrFail($id);

    $producedProducts = Product::
        whereIn('product_code', $order->products->pluck('batch_number')->toArray())
        ->get();

    return view('admin-views.production_orders.show', compact('order', 'producedProducts'));
}


    // حفظ أمر الإنتاج
public function store(Request $request)
{
    $request->validate([
        'supply_order_id' => 'required|exists:supply_orders,id',
        'factory_id'      => 'required|exists:factories,id',
        'total_cash'      => 'required|numeric',
        'paid'            => 'required|numeric',
        'status'          => 'required|in:cash,agel',
        'account_id'      => 'required|exists:accounts,id',
        'products'        => 'required|array',
        'components'      => 'array',
    ]);

    $supplyOrder = SupplyOrder::with('items')->findOrFail($request->supply_order_id);

    if (!in_array($supplyOrder->status, ['completed', 'issued'])) {
        Toastr::error('لا يمكن إنهاء أمر الإنتاج لأن أمر التوريد لم يكتمل بعد.');
        return redirect()->back();
    }

    DB::beginTransaction();

    try {
        $prodOrder = new ProductionOrder();
        $prodOrder->admin_id        = Auth::guard('admin')->id();
        $prodOrder->supply_order_id = $supplyOrder->id;
        $prodOrder->factory_id      = $request->factory_id;
        $prodOrder->total_cash      = $request->total_cash;
        $prodOrder->paid            = $request->paid;
        $prodOrder->status          = $request->status;
        $prodOrder->save();

        foreach ($request->products as $p) {
            $originalProduct = Product::findOrFail($p['product_id']);

            $batchNumber = $p['batch_number'];
            $producedQty = $p['produced_quantity'] ?? 0;
            $costPrice   = $p['cost_price'];
            $additional  = $p['additional_cost_price'] ?? 0;
            $finalCost   = round($costPrice + $additional, 2);

            $prodProduct = new ProductionOrderProduct();
            $prodProduct->production_order_id   = $prodOrder->id;
            $prodProduct->product_id            = $p['product_id'];
            $prodProduct->target_quantity       = $p['target_quantity'];
            $prodProduct->produced_quantity     = $producedQty;
            $prodProduct->cost_price            = $costPrice;
            $prodProduct->additional_cost_price = $additional;
            $prodProduct->production_date       = $p['production_date'];
            $prodProduct->end_date              = $p['end_date'];
            $prodProduct->batch_number          = $batchNumber;
            $prodProduct->code                  = $batchNumber;
            $prodProduct->save();

            $producedProduct = new Product();
            $producedProduct->name           = $originalProduct->name . ' - تشغيلة ' . $batchNumber;
            $producedProduct->name_en        = $originalProduct->name_en;
            $producedProduct->product_code   = $batchNumber;
            $producedProduct->category_id    = $originalProduct->category_id;
                        $producedProduct->selling_price    = $originalProduct->selling_price;
                                                $producedProduct->type    = $originalProduct->type;

            $producedProduct->unit_type      = $originalProduct->unit_type;
            $producedProduct->unit_value     = $originalProduct->unit_value;
            $producedProduct->quantity       = $producedQty;
            $producedProduct->discount_type  = $originalProduct->discount_type;
            $producedProduct->discount       = $originalProduct->discount ?? 0;
            $producedProduct->tax            = $originalProduct->tax ?? 0;
            $producedProduct->purchase_price = $finalCost;
            $producedProduct->expiry_date    = $p['end_date'];
            $producedProduct->save();
        }

        $converter = new Unit();

        if ($request->filled('components')) {
            foreach ($request->components as $componentGroup) {
                foreach ($componentGroup as $c) {
                    if (!isset($c['supply_order_item_id']) || !isset($c['material_batch_id'])) continue;

                    $component = new ProductionOrderComponent();
                    $component->production_order_product_id  = $prodProduct->id;
                    $component->supply_order_item_id = $c['supply_order_item_id'];
                    $component->material_batch_id    = $c['material_batch_id'];
                    $component->details              = $c['details'];
                    $component->save();

                    $batch = MaterialBatch::find($c['material_batch_id']);

                    if (!empty($c['details']['returned']['qty']) && !empty($c['details']['returned']['unit_id'])) {
                        $fromUnit = Unit::find($c['details']['returned']['unit_id']);
                        $toUnit   = $batch->unitRelation;

                        $returnedQty = $converter->convertQuantity(
                            $c['details']['returned']['qty'],
                            $fromUnit,
                            $toUnit
                        );

                        $batch->increment('quantity', $returnedQty);
                    }

                    if (!empty($c['details']['wasted']['qty']) && !empty($c['details']['wasted']['unit_id'])) {
                        $expire = new ProductExpire();
                        $expire->material_id = $batch->id;
                        $expire->quantity          = $c['details']['wasted']['qty'];
                        $expire->unit_id           = $c['details']['wasted']['unit_id'];
                        $expire->save();
                    }
                }
            }
        }
   if ($request->filled('additional_costs')) {
            foreach ($request->additional_costs as $cost) {
                $addCost = new ProductionOrderAdditionalCost();
                $addCost->production_order_id = $prodOrder->id;
                $addCost->description         = $cost['description'];
                $addCost->amount              = $cost['amount'];
                $addCost->cost_date           = $cost['cost_date'];
                $addCost->save();
            }
        }

        $supplyOrder->status = 'ended';
        $supplyOrder->save();

        $account = Account::findOrFail($request->account_id);
        $factory = Factory::findOrFail($request->factory_id);

        $tran = new Transection();
        $tran->tran_type   = 999;
        $tran->account_id  = $account->id;
        $tran->amount      = $request->paid;
        $tran->description = 'إيصال انهاء امر انتاج واستلام منجات من المصنع';
        $tran->debit       = $factory->daen + $request->total_cash - $request->paid;
        $tran->credit      = $factory->maden;
        $tran->balance     = $account->balance - $request->paid;
        $tran->factory_id  = $factory->id;
                $tran->production_order_id  = $prodOrder->id;
        $tran->seller_id   = Auth::guard('admin')->id();
        $tran->save();

        $account->total_out += $request->paid;
        $account->balance   -= $request->paid;
        $account->save();

        $factory->daen += $request->total_cash - $request->paid;
        $factory->save();

        DB::commit();

        Toastr::success('تم إنهاء أمر الإنتاج بنجاح.');
        return redirect()->route('admin.production_orders.index')
                         ->with('success', 'تم إنشاء أمر الإنتاج وتم إنهاء أمر التوريد بنجاح.');
    } catch (\Throwable $e) {
        DB::rollback();
        report($e);
        Toastr::error('حدث خطأ أثناء إنهاء أمر الإنتاج: ' . $e->getMessage());
        return redirect()->back()->withInput();
    }
}


}
