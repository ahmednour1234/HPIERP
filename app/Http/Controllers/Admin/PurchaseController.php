<?php

namespace App\Http\Controllers\Admin;

use App\CPU\Helpers;  
use Brian2694\Toastr\Facades\Toastr;
use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialBatch;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use App\Models\Account;
use App\Models\Transection;


class PurchaseController extends Controller
{
    /**
     * عرض جميع فواتير الشراء
     */
public function index(Request $request)
{
    $query = Purchase::with(['details','supplier','admin'])->latest();

    if ($request->filled('supplier_id')) {
        $query->where('supplier_id', $request->supplier_id);
    }
    if ($request->filled('admin_id')) {
        $query->where('admin_id', $request->admin_id);
    }
    if ($request->filled('date_from')) {
        $query->whereDate('created_at','>=',$request->date_from);
    }
    if ($request->filled('date_to')) {
        $query->whereDate('created_at','<=',$request->date_to);
    }

    $purchases = $query->paginate(20)->appends($request->all());

    // Pass all suppliers and admins for filter selects
    $suppliers = Supplier::pluck('name','id');
    $admins    = Admin::pluck('f_name','id');

    return view('admin-views.purchases.index', compact('purchases','suppliers','admins'));
}

    /**
     * عرض فاتورة شراء واحدة
     */
    public function show(Purchase $purchase)
    {
        $purchase->load('details.material', 'supplier', 'admin');
                $accounts=Account::all();
        return view('admin-views.purchases.show', compact('purchase','accounts'));
    }

    /**
     * نموذج إنشاء فاتورة شراء جديدة
     */
    public function create()
    {
        $materials = Material::with('unit')->get();  
        $suppliers = Supplier::all();
        $accounts=Account::all();
        return view('admin-views.purchases.create', compact('materials', 'suppliers','accounts'));
    }
     public function units($materialId): JsonResponse
    {
        // 1) جلب unit_id من جدول materials
        $defaultUnitId = DB::table('materials')
            ->where('id', $materialId)
            ->value('unit_id');

        if (! $defaultUnitId) {
            return response()->json([], 404);
        }

        // 2) جلب الـ base_unit_id من جدول units
        $baseUnitId = DB::table('units')
            ->where('id', $defaultUnitId)
            ->value('base_unit_id');

        // إذا لم يكن هناك base_unit_id (أي الوحدة نفسها هي الأساس)
        // نستخدم الـ defaultUnitId كـ baseUnitId
        $baseUnitId = $baseUnitId ?: $defaultUnitId;

        // 3) جلب كل الوحدات التي تنتمي لنفس الأساس
        $units = DB::table('units')
            ->where('base_unit_id', $baseUnitId)
            ->orWhere('id', $baseUnitId)
            ->select('id', 'unit_type')
            ->get();

        return response()->json($units);
    }
    /**
     * تخزين أو تنفيذ فاتورة الشراء
     */
  public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id'            => 'required|exists:suppliers,id',
            'account_id'             => 'required_if:status,executed|exists:accounts,id',
            'status'                 => 'required|in:draft,executed',
            'payment_type'           => 'required|in:cash,credit',
            'paid_amount'            => 'required_if:payment_type,cash|numeric|min:0',
            'description'            => 'nullable|string',
            'date'                   => 'nullable|date',
            'image'                  => 'nullable|image|max:2048',
            'items'                  => 'required|array|min:1',
            'items.*.material'       => 'required|exists:materials,id',
            'items.*.unit'           => 'required|exists:units,id',
            'items.*.quantity'       => 'required|numeric|min:0.001',
            'items.*.unit_price'     => 'required|numeric|min:0',
            'items.*.discount'       => 'nullable|numeric|min:0',
            'items.*.tax'            => 'nullable|numeric|min:0',
            'items.*.expiration_date'=> 'nullable|date',
        ]);

        DB::transaction(function() use ($data, $request) {
            // handle image
            if ($request->hasFile('image')) {
                $data['image_path'] = Helpers::uploadImage($request->file('image'), 'purchases');
            }

            // 1) create purchase header
            $purchase = Purchase::create([
                'supplier_id'    => $data['supplier_id'],
                'admin_id'       =>  auth('admin')->user()->id,
                'status'         => $data['status'],
                'payment_type'   => $data['payment_type'],
                'paid_amount'    => $data['paid_amount'] ?? 0,
                'image_path'     => $data['image_path'] ?? null,
                'sub_total'      => 0,
                'total_discount' => 0,
                'tax_amount'     => 0,
            ]);

            $subTotal = $totalDiscount = $totalTax = 0;

            // 2) details & optional batches
            foreach ($data['items'] as $item) {
                $material = Material::findOrFail($item['material']);
                $unit     = Unit::findOrFail($item['unit']);

                // verify unit via pivot (assuming material_unit table)
                $validUnit = DB::table('materials')
                    ->where('id', $material->id)
                    ->where('unit_id', $unit->id)
                    ->exists();
                if (! $validUnit) {
                    throw ValidationException::withMessages([
                        'items' => ["الوحدة المختارة غير صالحة للمادة {$material->name}"]
                    ]);
                }

                // compute line totals
                $lineSub   = $item['unit_price'] * $item['quantity'];
                $lineDisc  = $item['discount'] ?? 0;
                $lineTax   = $item['tax'] ?? 0;
                $lineTotal = $lineSub - $lineDisc + $lineTax;

                // create detail
                $detail = PurchaseDetail::create([
                    'purchase_id'     => $purchase->id,
                    'material_id'     => $material->id,
                    'quantity'        => $item['quantity'],
                    'unit'            => $unit->id,
                    'unit_price'      => $item['unit_price'],
                    'discount'        => $lineDisc,
                    'tax_amount'      => $lineTax,
                    'total'           => $lineTotal,
                    'expiration_date' => $item['expiration_date'] ?? null,
                    'unique_code'     => Str::upper(Str::random(10)),
                ]);

                // batches only if executed
                if ($data['status'] === 'executed') {
                    MaterialBatch::create([
                        'material_id'     => $material->id,
                        'quantity'        => $item['quantity'],
                        'unit_id'        => $unit->id,
                        'expiration_date' => $item['expiration_date'] ?? null,
                        'price'           => $item['unit_price'],
                        'tax_amount'      => $lineTax,
                        'total'           => $lineTotal,
                        'unique_code'     => $detail->unique_code,
                    ]);
                }

                $subTotal      += $lineSub;
                $totalDiscount += $lineDisc;
                $totalTax      += $lineTax;
            }

            // 3) If executed, record a financial transaction
            if ($data['status'] === 'executed') {
                $account   = Account::findOrFail($data['account_id']);
                $supplier  = Supplier::findOrFail($data['supplier_id']);
                $amount    = $data['paid_amount'] ?? 0;
                $seller_id = Auth::id();
                $img       = $data['image_path'] ?? null;

                $tran = new Transection();
                $tran->tran_type    = 13;                    // purchase type
                $tran->account_id   = $account->id;
                $tran->amount       = $amount;
                $tran->description  = $data['description'] ?? 'فاتورة شراء';
                $tran->debit        = $amount;
                $tran->credit       = 0;
                $tran->balance      = $account->balance - $amount;
                $tran->date         = $data['date'] ?? now();
                $tran->supplier_id  = $supplier->id;
                $tran->seller_id    = $seller_id;
                $tran->img          = $img;
                $tran->save();

                // update account and supplier balances
                $account->decrement('balance', $amount);
                $account->increment('total_out', $amount);

                $supplier->increment('due_amount', $subTotal+$totalTax-$totalDiscount-$amount);
                $supplier->save();
            }

            // 4) update purchase header totals
            $purchase->update([
                'sub_total'      => $subTotal,
                'total_discount' => $totalDiscount,
                'tax_amount'     => $totalTax,
            ]);
        });

        Toastr::success('تم حفظ فاتورة الشراء كـ ' . $data['status'], 'نجاح');
        return redirect()->route('admin.purchases.index');
    }

    /**
     * Convert a draft purchase to executed: set status, then create batches.
     */
    public function executeDraft(Request $request, $purchaseId)
    {
        $request->validate([
            'account_id'  => 'required|exists:accounts,id',
            'paid_amount' => 'required|numeric|min:0',
        ]);

        $purchase = Purchase::with('details', 'supplier')->findOrFail($purchaseId);

        if ($purchase->status !== 'draft') {
            Toastr::info('الفاتورة ليست في الحالة مسودة.');
            return redirect()->back();
        }

        DB::transaction(function() use ($request, $purchase) {
            // 1) Update header: status, paid amount, chosen account
            $purchase->update([
                'status'      => 'executed',
                'paid_amount' => $request->paid_amount,
                'account_id'  => $request->account_id,
            ]);

            // 2) Create batches for each detail
            foreach ($purchase->details as $detail) {
                MaterialBatch::create([
                    'material_id'     => $detail->material_id,
                    'quantity'        => $detail->quantity,
                    'expiration_date' => $detail->expiration_date,
                            'unit_id'        => $detail->unit,

                    'price'           => $detail->unit_price,
                    'tax_amount'      => $detail->tax_amount,
                    'total'           => $detail->total,
                    'unique_code'     => $detail->unique_code,
                ]);
            }

            // 3) Record financial transaction
            $account  = Account::findOrFail($request->account_id);
            $supplier = $purchase->supplier;
            $amount   = $request->paid_amount;
            $sellerId = auth('admin')->id();

            $tran = new Transection();
            $tran->tran_type    = 13; // رمز نوع المعاملة: فاتورة شراء
            $tran->account_id   = $account->id;
            $tran->amount       = $amount;
            $tran->description  = 'تنفيذ فاتورة شراء #' . $purchase->id;
            $tran->debit        = $amount;
            $tran->credit       = 0;
            $tran->balance      = $account->balance - $amount;
            $tran->date         = now();
            $tran->supplier_id  = $supplier->id;
            $tran->seller_id    = $sellerId;
            $tran->img          = $purchase->image_path;
            $tran->save();

            // 4) Update account & supplier balances
            $account->decrement('balance', $amount);
            $account->increment('total_out', $amount);

                $supplier->increment('due_amount', $purchase->sub_total+$purchase->tax_amount-$purchase->total_discount-$amount);
            $supplier->save();
        });

        Toastr::success('تم تنفيذ الفاتورة وإنشاء الدفعات والحركة المالية بنجاح.', 'نجاح');
        return redirect()->route('admin.purchases.show', $purchase->id);
    }

}
