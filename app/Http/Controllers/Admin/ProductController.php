<?php

namespace App\Http\Controllers\Admin;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use PDF;
use App\CPU\Helpers;
use App\Models\Unit;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\ProductExpire;
use App\Models\OrderDetail;
use App\Models\Customer;
use App\Models\Stock;
use App\Models\Store;
use App\Models\Order;
use App\Models\Region;
use App\Models\Taxe;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use function App\CPU\translate;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use Symfony\Component\Console\Input\Input;
use Illuminate\Support\Facades\Auth;
use App\Models\AdminSeller;
use App\Models\Seller;
class ProductController extends Controller
{
    use \App\Traits\ExportsCsv;

    public function __construct(
        private Unit $unit,
        private Brand $brand,
        private Product $product,
        private ProductExpire $productexpire,
        private Category $category,
        private Supplier $supplier,
        private Taxe $taxe,
        private Store $store
    ){}

    /**
     * @param Request $request
     * @return Application|Factory|View
     */
/**
 * سطور كشف المنتجات المباعة مطابقة لفلاتر الشاشة، بدون ترقيم صفحات.
 * تُستخدم في التصدير حتى يصف الملف نفس مجموعة السطور التي تصفها الشاشة.
 */
/**
 * قيم فلتر قد تصل مفردة (رابط قديم) أو مصفوفة (اختيار متعدد).
 * توحيدها هنا يمنع تكرار الفحص في كل موضع.
 */
private function filterValues($value): array
{
    return array_values(array_filter(
        (array) $value,
        fn ($v) => $v !== '' && $v !== null
    ));
}

private function soldProductsRows(Request $request): array
{
    $validated = $request->validate([
        'start_date'       => 'nullable|date',
        'end_date'         => 'nullable|date|after_or_equal:start_date',
        'product_name'     => 'nullable|string',
        // صارت متعددة الاختيار. الروابط القديمة تحمل قيمة مفردة، فتُقبل
        // الحالتان ثم تُوحَّد إلى مصفوفة في filterValues().
        'product_code'     => 'nullable',
        'product_code.*'   => 'string',
        'seller_id'        => 'nullable',
        'seller_id.*'      => 'exists:admins,id',
        'order_type'       => 'nullable',
        'order_type.*'     => 'integer',
        'payment_status'   => 'nullable',
        'payment_status.*' => 'in:paid,unpaid',
        'invoice_status'   => 'nullable|array',
        'invoice_status.*' => 'in:paid,unpaid,returned_fully,partial_paid,partial_returned,partial_both',
        'region_ids'       => 'nullable|array',
        'region_ids.*'     => 'exists:regions,id',
        'region_id'        => 'nullable|exists:regions,id',
    ]);

    $start_date = !empty($validated['start_date']) ? Carbon::parse($validated['start_date'])->startOfDay() : null;
    $end_date   = !empty($validated['end_date'])   ? Carbon::parse($validated['end_date'])->endOfDay()   : null;

    $regionIds = collect($validated['region_ids'] ?? [])
        ->when(!empty($validated['region_id']), fn($c) => $c->push((int)$validated['region_id']))
        ->unique()->values()->all();

    $productCodes = $this->filterValues($validated['product_code'] ?? []);
    $sellerIds = $this->filterValues($validated['seller_id'] ?? []);
    $orderTypes = $this->filterValues($validated['order_type'] ?? []);
    $selectedStatuses = $this->filterValues($validated['invoice_status'] ?? []);

    $query = OrderDetail::query()
        ->select('id', 'order_id', 'product_id', 'product_details', 'quantity', 'price', 'updated_at')
        ->with([
            'product:id,name,product_code,selling_price',
            'order:id,owner_id,user_id,type,order_amount,transaction_reference,updated_at,img',
            'order.seller:id,email,f_name,l_name',
            'order.customer:id,name,region_id',
            'order.customer.regions:id,name',
        ]);

    if (!empty($validated['product_name'])) {
        $query->whereJsonContains('product_details->name', $validated['product_name']);
    }
    if ($codes = $productCodes) {
        // whereJsonContains لا يقبل قائمة، فنبني OR لكل كود مختار.
        $query->where(function ($q) use ($codes) {
            foreach ($codes as $code) {
                $q->orWhereJsonContains('product_details->product_code', $code);
            }
        });
    }
    if ($start_date && $end_date) {
        $query->whereBetween('updated_at', [$start_date, $end_date]);
    }
    if ($sellerIds) {
        $query->whereHas('order', fn($q) => $q->whereIn('owner_id', $sellerIds));
    }
    if ($orderTypes) {
        $query->whereHas('order', fn($q) => $q->whereIn('type', $orderTypes));
    }
    if (!empty($regionIds)) {
        $query->whereHas('order.customer', function ($cq) use ($regionIds) {
            $cq->whereIn('region_id', $regionIds)
               ->orWhereHas('regions', fn($cqq) => $cqq->whereIn('regions.id', $regionIds));
        });
    }
    if ($statuses = $this->filterValues($validated['payment_status'] ?? [])) {
        // اختيار الحالتين معًا يساوي عدم الفلترة، فنتخطاه بدل بناء شرط
        // يستبعد كل شيء.
        if (count(array_unique($statuses)) === 1) {
            $paid = $statuses[0] === 'paid';
            $query->whereHas('order', function ($q) use ($paid) {
                $paid
                    ? $q->whereRaw('FLOOR(order_amount) = FLOOR(transaction_reference)')
                    : $q->whereRaw('FLOOR(order_amount) > FLOOR(transaction_reference)');
            });
        }
    }

    $details = $query->get();

    $parentIds = $details->pluck('order_id')->unique()->filter()->values();
    $returnsByParent = Order::whereIn('parent_id', $parentIds)->with('details')->get()->groupBy('parent_id');

    $selectedStatuses = (array) ($validated['invoice_status'] ?? []);

    $rows = [];
    $serial = 0;

    foreach ($details as $detail) {
        $order = $detail->order;
        if (!$order) {
            continue;
        }

        $orderAmount    = (float) $order->order_amount;
        $transactionRef = (float) $order->transaction_reference;
        $originalQty    = (float) $order->details->sum('quantity');
        $returnedQty    = (float) $returnsByParent->get($order->id, collect())->flatMap->details->sum('quantity');

        if ($orderAmount == $transactionRef || $transactionRef > $orderAmount) {
            $status = 'paid';
        } elseif ($transactionRef == 0 && $returnedQty >= $originalQty && $originalQty > 0) {
            $status = 'returned_fully';
        } elseif (($transactionRef > 0) && ($orderAmount - $transactionRef > 0) && $returnedQty == 0) {
            $status = 'partial_paid';
        } elseif ($transactionRef == 0 && $returnedQty > 0 && $returnedQty < $originalQty) {
            $status = 'partial_returned';
        } elseif ($transactionRef > 0 && $returnedQty > 0) {
            $status = 'partial_both';
        } else {
            $status = 'unpaid';
        }

        if (!empty($selectedStatuses) && !in_array($status, $selectedStatuses, true)) {
            continue;
        }

        $labels = [
            'paid'             => 'محصلة',
            'unpaid'           => 'غير محصلة',
            'returned_fully'   => 'إرجاع كامل',
            'partial_paid'     => 'تحصيل جزئي',
            'partial_returned' => 'إرجاع جزئي',
            'partial_both'     => 'تحصيل وإرجاع جزئي',
        ];

        $productDetails = json_decode($detail->product_details);

        $rows[] = [
            'م'                => ++$serial,
            'اسم المنتج'       => app()->getLocale() === 'ar'
                                    ? (optional($detail->product)->name ?? '')
                                    : (optional($detail->product)->name ?? ''),
            'كود المنتج'       => optional($detail->product)->product_code ?? '',
            'الوحدة'           => $productDetails->unit_value ?? '',
            'سعر البيع'        => optional($detail->product)->selling_price ?? '',
            'الكمية'           => $detail->quantity ?? 0,
            'إجمالي البيع'     => round(($detail->price ?? 0) * ($detail->quantity ?? 0), 2),
            'المندوب'          => optional($order->seller)->email ?? '',
            'العميل'           => optional($order->customer)->name ?? '',
            'المنطقة'          => optional(optional($order->customer)->regions)->name ?? '',
            'المبلغ المحصل'    => round($transactionRef, 2),
            'رقم الفاتورة'     => $order->id,
            'تاريخ البيع'      => optional($order->updated_at)->format('Y-m-d H:i'),
            'الحالة'           => $labels[$status] ?? $status,
        ];
    }

    return [$rows];
}

/**
 * كشف المنتجات المباعة كملف اكسيل، بنفس فلاتر الشاشة تمامًا وعلى كامل
 * النتيجة لا على الصفحة المعروضة فقط. maatwebsite/excel غير مثبّت في
 * المشروع، وExcel يفتح CSV مباشرة؛ الـ BOM يبقي العربية مقروءة.
 */
public function exportReportProducts(Request $request)
{
    [$rows] = $this->soldProductsRows($request);

    $filename = 'sold-products-' . now()->format('Y-m-d') . '.csv';

    return response()->streamDownload(function () use ($rows) {
        $out = fopen('php://output', 'w');
        fwrite($out, "ï»¿");

        if (!empty($rows)) {
            fputcsv($out, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($out, array_values($row));
            }
        } else {
            fputcsv($out, ['لا توجد بيانات']);
        }

        fclose($out);
    }, $filename, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ]);
}

public function getreportProducts(Request $request)
{
    // 1) التحقق
    $validated = $request->validate([
        'start_date'       => 'nullable|date',
        'end_date'         => 'nullable|date|after_or_equal:start_date',
        'product_name'     => 'nullable|string',
        // صارت متعددة الاختيار. الروابط القديمة تحمل قيمة مفردة، فتُقبل
        // الحالتان ثم تُوحَّد إلى مصفوفة في filterValues().
        'product_code'     => 'nullable',
        'product_code.*'   => 'string',
        'seller_id'        => 'nullable',
        'seller_id.*'      => 'exists:admins,id',
        'order_type'       => 'nullable',
        'order_type.*'     => 'integer',
        'payment_status'   => 'nullable',
        'payment_status.*' => 'in:paid,unpaid',
        // ✅ تعدد حالات الفاتورة
        'invoice_status'   => 'nullable|array',
        'invoice_status.*' => 'in:paid,unpaid,returned_fully,partial_paid,partial_returned,partial_both',
        // ✅ تعدد المناطق (جديد)
        'region_ids'       => 'nullable|array',
        'region_ids.*'     => 'exists:regions,id',
        // دعم قديم لمعامل مفرد
        'region_id'        => 'nullable|exists:regions,id',
    ]);

    $adminId = Auth::guard('admin')->id();
    $productsall = Product::select('id', 'name', 'product_code')
        ->orderBy('name')
        ->get();
    // بيانات المساعدين للفلاتر
    $sellers = Seller::join('admin_sellers', 'admins.id', '=', 'admin_sellers.seller_id')
        ->where('admin_sellers.admin_id', $adminId)
        ->select('admins.id', 'admins.email', 'admins.f_name', 'admins.l_name')
        ->get();

    $regions = Region::select('id', 'name')->orderBy('name')->get();

    $start_date = !empty($validated['start_date']) ? Carbon::parse($validated['start_date'])->startOfDay() : null;
    $end_date   = !empty($validated['end_date'])   ? Carbon::parse($validated['end_date'])->endOfDay()   : null;

    // ✅ دمج region_id المفرد مع region_ids[] لو موجود
    $regionIds = collect($validated['region_ids'] ?? [])
        ->when(!empty($validated['region_id']), fn($c) => $c->push((int)$validated['region_id']))
        ->unique()->values()->all();

    // 2) الاستعلام الأساسي على تفاصيل الطلبات
    return $this->renderFastProductReport(
        $validated,
        $productsall,
        $sellers,
        $regions,
        $start_date,
        $end_date,
        $regionIds
    );

    $query = OrderDetail::with(['order.seller', 'order.customer.regions', 'product', 'order.details']);

    if (!empty($validated['product_name'])) {
        $query->whereJsonContains('product_details->name', $validated['product_name']);
    }

    if ($codes = $this->filterValues($validated['product_code'] ?? [])) {
        // whereJsonContains لا يقبل قائمة، فنبني OR لكل كود مختار.
        $query->where(function ($q) use ($codes) {
            foreach ($codes as $code) {
                $q->orWhereJsonContains('product_details->product_code', $code);
            }
        });
    }

    if ($start_date && $end_date) {
        $query->whereBetween('updated_at', [$start_date, $end_date]);
    }

    if ($sellerIds = $this->filterValues($validated['seller_id'] ?? [])) {
        $query->whereHas('order', fn($q) => $q->whereIn('owner_id', $sellerIds));
    }

    if ($orderTypes = $this->filterValues($validated['order_type'] ?? [])) {
        $query->whereHas('order', fn($q) => $q->whereIn('type', $orderTypes));
    }

    // ✅ فلترة متعددة المناطق: عبر Customer فقط
    if (!empty($regionIds)) {
        $query->whereHas('order.customer', function ($cq) use ($regionIds) {
            $cq->whereIn('region_id', $regionIds) // حالة العمود المباشر على customers
               ->orWhereHas('regions', function ($cqq) use ($regionIds) { // اختيارية لو Customer عنده علاقة regions()
                   $cqq->whereIn('regions.id', $regionIds);
               });
        });
    }

    if ($statuses = $this->filterValues($validated['payment_status'] ?? [])) {
        // اختيار الحالتين معًا يساوي عدم الفلترة، فنتخطاه بدل بناء شرط
        // يستبعد كل شيء.
        if (count(array_unique($statuses)) === 1) {
            $paid = $statuses[0] === 'paid';
            $query->whereHas('order', function ($q) use ($paid) {
                $paid
                    ? $q->whereRaw('FLOOR(order_amount) = FLOOR(transaction_reference)')
                    : $q->whereRaw('FLOOR(order_amount) > FLOOR(transaction_reference)');
            });
        }
    }

    // 3) انسخ الـ Builder قبل get() لعمل إحصائيات شاملة
    $ordersAll    = (clone $query)->get();      // كل النتائج المطابقة (بدون باجينيشن)
    $orderDetails = $query->paginate(40);       // النتائج المعروضة في الجدول

    // 4) تجهيز خريطة الإرجاعات لكل طلب لتجنّب استعلام داخل اللوب
    $parentIds = $ordersAll->pluck('order_id')->unique()->filter()->values();
    $returnsByParent = Order::whereIn('parent_id', $parentIds)
        ->with('details')
        ->get()
        ->groupBy('parent_id'); // [parent_id => Collection<Order>]

    // 5) الحالات المختارة من الفلتر (إن وُجدت)
    $selectedStatuses = (array) ($validated['invoice_status'] ?? []);

    // 6) عدّادات الحالات.
    // كانت تُحسب داخل map() على سطور تفاصيل الطلب وعلى الصفحة الحالية فقط،
    // فالفاتورة ذات ٣ أصناف كانت تُعدّ ٣ مرات ويظهر عدد الإيصالات غير المحصلة
    // أكبر من الواقع. العدّ الآن لكل فاتورة مرة واحدة وعلى كامل نتيجة الفلتر.
    $invoiceStatusCounts = [
        'paid'              => 0,
        'unpaid'            => 0,
        'returned_fully'    => 0,
        'partial_paid'      => 0,
        'partial_returned'  => 0,
        'partial_both'      => 0,
    ];

    // حالة كل فاتورة (order_id => status)، تُحسب مرة واحدة ويُعاد استخدامها
    // في عدّادات البطاقات وفي فلترة سطور الجدول.
    $statusByOrder = [];

    foreach ($ordersAll->pluck('order')->filter()->unique('id') as $o) {
        $orderAmount    = (float) $o->order_amount;
        $transactionRef = (float) $o->transaction_reference;
        $originalQty    = (float) $o->details->sum('quantity');

        $returnOrders = $returnsByParent->get($o->id, collect());
        $returnedQty  = (float) $returnOrders->flatMap->details->sum('quantity');

        if ($orderAmount == $transactionRef || $transactionRef > $orderAmount) {
            $st = 'paid';
        } elseif ($transactionRef == 0 && $returnedQty >= $originalQty && $originalQty > 0) {
            $st = 'returned_fully';
        } elseif (($transactionRef > 0) && ($orderAmount - $transactionRef > 0) && $returnedQty == 0) {
            $st = 'partial_paid';
        } elseif ($transactionRef == 0 && $returnedQty > 0 && $returnedQty < $originalQty) {
            $st = 'partial_returned';
        } elseif ($transactionRef > 0 && $returnedQty > 0) {
            $st = 'partial_both';
        } else {
            $st = 'unpaid';
        }

        $statusByOrder[$o->id] = $st;
        $invoiceStatusCounts[$st]++;
    }

    // 7) بناء البيانات للعرض وتحديد حالة كل سطر
    $products = $orderDetails->getCollection()
        ->groupBy('product_details->id')
        ->map(function ($details) use ($selectedStatuses, $returnsByParent) {
            return $details->map(function ($detail) use ($selectedStatuses, $returnsByParent) {
                $order = $detail->order;
                $productDetails = json_decode($detail->product_details);
                $orderAmount    = (float) $order->order_amount;
                $transactionRef = (float) $order->transaction_reference;

                // مجموع الكميات الأصلية للطلب
                $originalQty = (float) $order->details->sum('quantity');

                // بيانات الإرجاع من الكاش المُجهّز
                $returnOrders = $returnsByParent->get($order->id, collect());
                $returnedQty  = (float) $returnOrders->flatMap->details->sum('quantity');

                // تحديد الحالة
                if ($orderAmount == $transactionRef || $transactionRef > $orderAmount) {
                    $status = 'paid';
                } elseif ($transactionRef == 0 && $returnedQty >= $originalQty && $originalQty > 0) {
                    $status = 'returned_fully';
                } elseif (($transactionRef > 0) && ($orderAmount - $transactionRef > 0) && $returnedQty == 0) {
                    $status = 'partial_paid';
                } elseif ($transactionRef == 0 && $returnedQty > 0 && $returnedQty < $originalQty) {
                    $status = 'partial_returned';
                } elseif ($transactionRef > 0 && $returnedQty > 0) {
                    $status = 'partial_both';
                } else {
                    $status = 'unpaid';
                }

                // فلترة بحسب الحالات المختارة إن وُجدت
                if (!empty($selectedStatuses) && !in_array($status, $selectedStatuses, true)) {
                    return null;
                }

                return [
                    'product_id'            => optional($detail->product)->id ?? '',
                    'product_name'          => optional($detail->product)->name ?? '',
                    'product_code'          => optional($detail->product)->product_code ?? '',
                    'unit_value'            => $productDetails->unit_value ?? '',
                    'selling_price'         => optional($detail->product)->selling_price ?? '',
                    'quantity'              => $detail->quantity ?? '',
                    'order_type'            => $order->type,
                    'order_id'              => $order->id ?? '',
                    'img'                   => $order->img ?? '',
                    'transaction_reference' => $transactionRef,
                    'created_at'            => $order->updated_at ?? '',
                    'total_selling_price'   => ($detail->price ?? 0) * ($detail->quantity ?? 0),
                    'seller'                => optional($order->seller)->email ?? '',
                    'customer'              => optional($order->customer)->name ?? '',
                    'region'                => optional(optional($order->customer)->regions)->name ?? '',
                    'invoice_status'        => $status,
                    
                ];
            })->filter();
        });

    // 8) فلتر أوامر المبالغ/الإحصائيات (مصَحَّح ليعمل عبر Customer)
    $orderFilter = function ($q) use ($validated, $start_date, $end_date, $regionIds) {
        if ($ids = $this->filterValues($validated['seller_id'] ?? [])) {
            $q->whereIn('owner_id', $ids);
        }
        if ($start_date && $end_date) {
            $q->whereBetween('updated_at', [$start_date, $end_date]);
        }
        if (!empty($regionIds)) {
            $q->whereHas('customer', function ($cq) use ($regionIds) {
                $cq->whereIn('region_id', $regionIds)
                   ->orWhereHas('regions', function ($cqq) use ($regionIds) {
                       $cqq->whereIn('regions.id', $regionIds);
                   });
            });
        }
    };

    // 9) مبالغ إجمالية
    $orderAmountType4    = Order::where('type', 4)->where($orderFilter)->sum('order_amount');
    $orderAmountType7    = Order::where('type', 7)->where($orderFilter)->sum('order_amount');
    $transactionRefType4 = Order::where('type', 4)->where($orderFilter)->sum('transaction_reference');

    $quantityType4       = Order::where('type', 4)
        ->where($orderFilter)
        ->with('details')
        ->get()
        ->sum(fn($o) => $o->details->sum('quantity'));

    $amountDue    = $orderAmountType4 - $orderAmountType7 - $transactionRefType4;
    $pricePerUnit = $quantityType4 > 0 ? $orderAmountType4 / $quantityType4 : 0;

    // 10) حساب الوحدات المُحصّلة
    $ordersType4 = Order::where('type', 4)
        ->where($orderFilter)
        ->with('details')
        ->get();

    $type4Ids = $ordersType4->pluck('id')->unique();
    $returnsForType4 = Order::whereIn('parent_id', $type4Ids)
        ->with('details')
        ->get()
        ->groupBy('parent_id');

    $collectedUnits = 0;

    foreach ($ordersType4 as $o) {
        $originalQty    = (int) $o->details->sum('quantity');
        $originalAmount = (float) $o->details->sum(fn($d) => ($d->price ?? 0) * ($d->quantity ?? 0));
        $paidAmount     = (float) $o->transaction_reference;
        $orderamount    = (float) $o->order_amount;

        $orderReturns   = $returnsForType4->get($o->id, collect());
        $returnedQty    = (int) $orderReturns->flatMap->details->sum('quantity');
        $returnedAmount = (float) $orderReturns->flatMap->details->sum(fn($d) => ($d->price ?? 0) * ($d->quantity ?? 0));

        $status = 'unpaid';
        if ($orderamount == $paidAmount || $paidAmount > $orderamount) {
            $status = 'paid';
        } elseif ($paidAmount == 0 && $returnedQty >= $originalQty && $originalQty > 0) {
            $status = 'returned_fully';
        } elseif (($paidAmount > 0) && ($orderamount - $paidAmount > 0) && $returnedQty == 0) {
            $status = 'partial_paid';
        } elseif ($paidAmount == 0 && $returnedQty > 0 && $returnedQty < $originalQty) {
            $status = 'partial_returned';
        } elseif ($paidAmount > 0 && $returnedQty > 0) {
            $status = 'partial_both';
        }

        if ($status === 'paid') {
            $collectedUnits += $originalQty;

        } elseif ($status === 'partial_paid') {
            if ($orderamount > 0) {
                $fraction = min(1, $paidAmount / $orderamount);
                $collectedUnits += (int) ceil($fraction * $originalQty);
            }

        } elseif ($status === 'partial_both') {
            $netQty    = max(0, $originalQty - $returnedQty);
            $netAmount = max(0.0, $orderamount - $returnedAmount);
            if ($netAmount > 0) {
                $fraction = min(1, $paidAmount / $netAmount);
                $collectedUnits += (int) ceil($fraction * $netQty);
            }
        }
    }

    // 11) إحصائيات إضافية عامة
    $productCount = $ordersAll->groupBy('product_details->id')->count();
    $quantitySum  = $ordersAll->sum('quantity');
    $priceSum     = $ordersAll->sum(fn($item) => ($item->price ?? 0) * ($item->quantity ?? 0));

    // 12) عرض النتيجة
    return view('admin-views.product.indexreport', compact(
        'products',
        'sellers',
        'regions',
        'productCount',
        'quantitySum',
        'priceSum',
        'orderDetails',
        'orderAmountType4',
        'orderAmountType7',
        'transactionRefType4',
        'amountDue',
        'invoiceStatusCounts',
        'pricePerUnit',
        'collectedUnits',
        'productsall'
    ));
}


private function renderFastProductReport(
    array $validated,
    $productsall,
    $sellers,
    $regions,
    ?Carbon $start_date,
    ?Carbon $end_date,
    array $regionIds
) {
    $productCodes = $this->filterValues($validated['product_code'] ?? []);
    $sellerIds = $this->filterValues($validated['seller_id'] ?? []);
    $orderTypes = $this->filterValues($validated['order_type'] ?? []);
    $selectedStatuses = $this->filterValues($validated['invoice_status'] ?? []);

    $query = OrderDetail::query()
        ->select('id', 'order_id', 'product_id', 'product_details', 'quantity', 'price', 'updated_at')
        ->with([
            'product:id,name,product_code,selling_price',
            'order:id,owner_id,user_id,type,order_amount,transaction_reference,updated_at,img',
            'order.seller:id,email,f_name,l_name',
            'order.customer:id,name,region_id',
            'order.customer.regions:id,name',
        ]);

    if (!empty($validated['product_name'])) {
        $query->whereJsonContains('product_details->name', $validated['product_name']);
    }

    if ($productCodes) {
        $query->where(function ($q) use ($productCodes) {
            foreach ($productCodes as $code) {
                $q->orWhereJsonContains('product_details->product_code', $code);
            }
        });
    }

    if ($start_date && $end_date) {
        $query->whereBetween('updated_at', [$start_date, $end_date]);
    }

    if ($sellerIds) {
        $query->whereHas('order', fn($q) => $q->whereIn('owner_id', $sellerIds));
    }

    if ($orderTypes) {
        $query->whereHas('order', fn($q) => $q->whereIn('type', $orderTypes));
    }

    if (!empty($regionIds)) {
        $query->whereHas('order.customer', function ($cq) use ($regionIds) {
            $cq->whereIn('region_id', $regionIds)
               ->orWhereHas('regions', fn($cqq) => $cqq->whereIn('regions.id', $regionIds));
        });
    }

    if ($statuses = $this->filterValues($validated['payment_status'] ?? [])) {
        if (count(array_unique($statuses)) === 1) {
            $paid = $statuses[0] === 'paid';
            $query->whereHas('order', function ($q) use ($paid) {
                $paid
                    ? $q->whereRaw('FLOOR(order_amount) = FLOOR(transaction_reference)')
                    : $q->whereRaw('FLOOR(order_amount) > FLOOR(transaction_reference)');
            });
        }
    }

    $summaryQuery = (clone $query)->setEagerLoads([]);
    $orderIds = (clone $summaryQuery)->distinct()->pluck('order_id')->filter()->values();

    $invoiceStatusCounts = [
        'paid' => 0,
        'unpaid' => 0,
        'returned_fully' => 0,
        'partial_paid' => 0,
        'partial_returned' => 0,
        'partial_both' => 0,
    ];
    $statusByOrder = [];

    if ($orderIds->isNotEmpty()) {
        $originalQtyByOrder = DB::table('order_details')
            ->whereIn('order_id', $orderIds)
            ->groupBy('order_id')
            ->selectRaw('order_id, COALESCE(SUM(quantity), 0) as original_qty')
            ->get()
            ->pluck('original_qty', 'order_id');

        $returnsByParent = DB::table('orders')
            ->join('order_details', 'orders.id', '=', 'order_details.order_id')
            ->whereIn('orders.parent_id', $orderIds)
            ->groupBy('orders.parent_id')
            ->selectRaw('orders.parent_id, COALESCE(SUM(order_details.quantity), 0) as returned_qty, COALESCE(SUM(order_details.price * order_details.quantity), 0) as returned_amount')
            ->get()
            ->keyBy('parent_id');

        Order::whereIn('id', $orderIds)
            ->select('id', 'order_amount', 'transaction_reference')
            ->chunkById(500, function ($orders) use (&$statusByOrder, &$invoiceStatusCounts, $originalQtyByOrder, $returnsByParent) {
                foreach ($orders as $order) {
                    $orderAmount = (float) $order->order_amount;
                    $transactionRef = (float) $order->transaction_reference;
                    $originalQty = (float) ($originalQtyByOrder[$order->id] ?? 0);
                    $returnedQty = (float) optional($returnsByParent->get($order->id))->returned_qty;

                    if ($orderAmount == $transactionRef || $transactionRef > $orderAmount) {
                        $status = 'paid';
                    } elseif ($transactionRef == 0 && $returnedQty >= $originalQty && $originalQty > 0) {
                        $status = 'returned_fully';
                    } elseif (($transactionRef > 0) && ($orderAmount - $transactionRef > 0) && $returnedQty == 0) {
                        $status = 'partial_paid';
                    } elseif ($transactionRef == 0 && $returnedQty > 0 && $returnedQty < $originalQty) {
                        $status = 'partial_returned';
                    } elseif ($transactionRef > 0 && $returnedQty > 0) {
                        $status = 'partial_both';
                    } else {
                        $status = 'unpaid';
                    }

                    $statusByOrder[$order->id] = $status;
                    $invoiceStatusCounts[$status]++;
                }
            });
    }

    if ($selectedStatuses) {
        $allowedOrderIds = collect($statusByOrder)
            ->filter(fn ($status) => in_array($status, $selectedStatuses, true))
            ->keys()
            ->values();

        $allowedOrderIds->isEmpty()
            ? $query->whereRaw('1 = 0')
            : $query->whereIn('order_id', $allowedOrderIds);
    }

    $summaryQuery = (clone $query)->setEagerLoads([]);
    $productCount = (clone $summaryQuery)->whereNotNull('product_id')->distinct()->count('product_id');
    $quantitySum = (float) (clone $summaryQuery)->sum('quantity');
    $priceSum = (float) (clone $summaryQuery)
        ->selectRaw('COALESCE(SUM(price * quantity), 0) as total')
        ->value('total');

    $orderDetails = $query->latest('updated_at')->paginate(25)->withQueryString();

    $products = $orderDetails->getCollection()
        ->map(function ($detail) use ($statusByOrder) {
            $order = $detail->order;
            if (!$order) {
                return null;
            }

            $productDetails = json_decode($detail->product_details ?: '{}') ?: (object) [];

            return [
                'product_id' => optional($detail->product)->id ?? '',
                'product_name' => optional($detail->product)->name ?? '',
                'product_code' => optional($detail->product)->product_code ?? '',
                'unit_value' => $productDetails->unit_value ?? '',
                'selling_price' => optional($detail->product)->selling_price ?? '',
                'quantity' => $detail->quantity ?? '',
                'order_type' => $order->type,
                'order_id' => $order->id ?? '',
                'img' => $order->img ?? '',
                'transaction_reference' => (float) $order->transaction_reference,
                'created_at' => $order->updated_at ?? '',
                'total_selling_price' => ($detail->price ?? 0) * ($detail->quantity ?? 0),
                'seller' => optional($order->seller)->email ?? '',
                'customer' => optional($order->customer)->name ?? '',
                'region' => optional(optional($order->customer)->regions)->name ?? '',
                'invoice_status' => $statusByOrder[$order->id] ?? 'unpaid',
            ];
        })
        ->filter()
        ->values();

    $orderFilter = function ($q) use ($validated, $start_date, $end_date, $regionIds) {
        if ($ids = $this->filterValues($validated['seller_id'] ?? [])) {
            $q->whereIn('owner_id', $ids);
        }
        if ($start_date && $end_date) {
            $q->whereBetween('updated_at', [$start_date, $end_date]);
        }
        if (!empty($regionIds)) {
            $q->whereHas('customer', function ($cq) use ($regionIds) {
                $cq->whereIn('region_id', $regionIds)
                   ->orWhereHas('regions', function ($cqq) use ($regionIds) {
                       $cqq->whereIn('regions.id', $regionIds);
                   });
            });
        }
    };

    $orderAmountType4 = Order::where('type', 4)->where($orderFilter)->sum('order_amount');
    $orderAmountType7 = Order::where('type', 7)->where($orderFilter)->sum('order_amount');
    $transactionRefType4 = Order::where('type', 4)->where($orderFilter)->sum('transaction_reference');
    $amountDue = $orderAmountType4 - $orderAmountType7 - $transactionRefType4;

    $ordersType4 = Order::where('type', 4)
        ->where($orderFilter)
        ->select('id', 'order_amount', 'transaction_reference')
        ->get();

    $type4Ids = $ordersType4->pluck('id')->unique()->values();
    $type4Quantities = DB::table('order_details')
        ->whereIn('order_id', $type4Ids)
        ->groupBy('order_id')
        ->selectRaw('order_id, COALESCE(SUM(quantity), 0) as quantity')
        ->get()
        ->pluck('quantity', 'order_id');

    $returnsForType4 = DB::table('orders')
        ->join('order_details', 'orders.id', '=', 'order_details.order_id')
        ->whereIn('orders.parent_id', $type4Ids)
        ->groupBy('orders.parent_id')
        ->selectRaw('orders.parent_id, COALESCE(SUM(order_details.quantity), 0) as returned_qty, COALESCE(SUM(order_details.price * order_details.quantity), 0) as returned_amount')
        ->get()
        ->keyBy('parent_id');

    $quantityType4 = (float) $type4Quantities->sum();
    $pricePerUnit = $quantityType4 > 0 ? $orderAmountType4 / $quantityType4 : 0;
    $collectedUnits = 0;

    foreach ($ordersType4 as $o) {
        $originalQty = (int) ($type4Quantities[$o->id] ?? 0);
        $paidAmount = (float) $o->transaction_reference;
        $orderamount = (float) $o->order_amount;
        $orderReturns = $returnsForType4->get($o->id);
        $returnedQty = (int) ($orderReturns->returned_qty ?? 0);
        $returnedAmount = (float) ($orderReturns->returned_amount ?? 0);

        $status = 'unpaid';
        if ($orderamount == $paidAmount || $paidAmount > $orderamount) {
            $status = 'paid';
        } elseif ($paidAmount == 0 && $returnedQty >= $originalQty && $originalQty > 0) {
            $status = 'returned_fully';
        } elseif (($paidAmount > 0) && ($orderamount - $paidAmount > 0) && $returnedQty == 0) {
            $status = 'partial_paid';
        } elseif ($paidAmount == 0 && $returnedQty > 0 && $returnedQty < $originalQty) {
            $status = 'partial_returned';
        } elseif ($paidAmount > 0 && $returnedQty > 0) {
            $status = 'partial_both';
        }

        if ($status === 'paid') {
            $collectedUnits += $originalQty;
        } elseif ($status === 'partial_paid' && $orderamount > 0) {
            $collectedUnits += (int) ceil(min(1, $paidAmount / $orderamount) * $originalQty);
        } elseif ($status === 'partial_both') {
            $netQty = max(0, $originalQty - $returnedQty);
            $netAmount = max(0.0, $orderamount - $returnedAmount);
            if ($netAmount > 0) {
                $collectedUnits += (int) ceil(min(1, $paidAmount / $netAmount) * $netQty);
            }
        }
    }

    return view('admin-views.product.indexreport', compact(
        'products',
        'sellers',
        'regions',
        'productCount',
        'quantitySum',
        'priceSum',
        'orderDetails',
        'orderAmountType4',
        'orderAmountType7',
        'transactionRefType4',
        'amountDue',
        'invoiceStatusCounts',
        'pricePerUnit',
        'collectedUnits',
        'productsall'
    ));
}




public function list(Request $request): View|Factory|Application
{
    $query_param = [];
    $search = $request['search'];
    $sort_orderQty = $request['sort_orderQty'];
    $search_quantity = $request['search_quantity'];

    $query = $this->product->with('productexpire')
        ->when($request->has('search'), function ($q) use ($search) {
            $key = explode(' ', $search);
            $q->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%")
                        ->orWhere('product_code', 'like', "%{$value}%");
                }
            });
        })
        ->when($search_quantity, function ($q) use ($search_quantity) {
            $q->whereHas('productexpire', function ($subQuery) use ($search_quantity) {
                $subQuery->where('quantity', '=', $search_quantity);
            });
        })
        ->when($sort_orderQty == 'quantity_expire_asc', function ($q) {
            return $q->join('product_expires', 'products.id', '=', 'product_expires.product_id')
                ->select('products.*')
                ->groupBy('products.id')
                ->orderByRaw('SUM(product_expires.quantity) ASC');
        })
        ->when($sort_orderQty == 'quantity_expire_desc', function ($q) {
            return $q->join('product_expires', 'products.id', '=', 'product_expires.product_id')
                ->select('products.*')
                ->groupBy('products.id')
                ->orderByRaw('SUM(product_expires.quantity) DESC');
        })
        ->when($sort_orderQty == 'quantity_asc', function ($q) {
            return $q->orderBy('quantity', 'asc');
        })
        ->when($sort_orderQty == 'quantity_desc', function ($q) {
            return $q->orderBy('quantity', 'desc');
        })
        ->when($sort_orderQty == 'order_asc', function ($q) {
            return $q->orderBy('order_count', 'asc');
        })
        ->when($sort_orderQty == 'order_desc', function ($q) {
            return $q->orderBy('order_count', 'desc');
        })
        ->when($sort_orderQty == 'default', function ($q) {
            return $q->orderBy('id');
        });

    $products = $query->latest()->paginate(Helpers::pagination_limit())
                    ->appends([
                        'search' => $search,
                        'sort_orderQty' => $sort_orderQty,
                        'search_quantity' => $search_quantity,
                    ]);

    return view('admin-views.product.list', compact('products', 'search', 'sort_orderQty', 'search_quantity'));
}
public function listProductsByOrderType(Request $request)
{
    // Initialize the query with the necessary relationships
     $query = OrderDetail::with(['product', 'order.customer'])
        ->when($request->filled('product_id'), function($q) use ($request) {
            // Filter by product_id if provided
            $q->where('product_id', $request->product_id);
        })
        ->when($request->filled('customer_id'), function($q) use ($request) {
            // Filter by customer_id if provided
            $q->whereHas('order.customer', function ($subQuery) use ($request) {
                $subQuery->where('id', $request->customer_id);
            });
        })
        ->when($request->filled('order_type'), function($q) use ($request) {
            // Filter by order_type if provided
            $q->whereHas('order', function ($subQuery) use ($request) {
                $subQuery->where('type', $request->order_type);
            });
        })
        ->when($request->filled('date_from') && $request->filled('date_to'), function ($q) use ($request) {
            // Filter by date range if both date_from and date_to are provided
            $q->whereBetween('created_at', [$request->date_from, $request->date_to]);
        });

    // Fetch paginated products
    $products = $query->paginate(10)->appends($request->all());

    // Clone query to use for additional calculations
    $queryForCalculations = clone $query;

    // Grouped totals for each order type
    $sales = $queryForCalculations->whereHas('order', function ($q) {
        $q->where('type', 4); // مبيعات
    })->get();
    

    $purchaseReturns = $queryForCalculations->whereHas('order', function ($q) {
        $q->where('type', 7); // مرتجع مبيعات
    })->get();

    $purchases = $queryForCalculations->whereHas('order', function ($q) {
        $q->where('type', 12); // مشتريات
    })->get();

    $salesReturns = $queryForCalculations->whereHas('order', function ($q) {
        $q->where('type', 24); // مرتجع مشتريات
    })->get();

    // Additional calculations
    $last_sale_price = optional($sales->last())->price;
    $last_purchase_price = optional($purchases->last())->price;

    $max_purchase_price = $purchases->max('price');
    $min_purchase_price = $purchases->min('price');

    $min_sale_quantity = $sales->min('quantity');
    $min_purchase_quantity = $purchases->min('quantity');
    $total_stock_quantity = Product::where('id', $request->product_id)->sum('quantity'); // Adjust the field name as needed

    $customers = Customer::all();

    return view('admin-views.product.trackproduct', compact(
        'products', 'customers', 'sales', 'purchaseReturns', 'purchases', 'salesReturns',
        'last_sale_price', 'last_purchase_price', 'max_purchase_price', 'min_purchase_price',
        'min_sale_quantity', 'min_purchase_quantity','total_stock_quantity'
    ));
}




/**
 * كشف الصلاحية: استعلام واحد يشترك فيه العرض والتصدير، حتى لا يصف الملف
 * صفوفًا غير التي تصفها الشاشة.
 */
private function expiryReportQuery(Request $request)
{
    $search = $request['search'];
    $sort_orderQty = $request['sort_orderQty'];
    $search_quantity = $request['search_quantity'];

    return $this->product->with('productexpire')
        ->when($request->has('search'), function ($q) use ($search) {
            $key = explode(' ', $search);
            $q->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%")
                        ->orWhere('product_code', 'like', "%{$value}%");
                }
            });
        })
        ->when($search_quantity, function ($q) use ($search_quantity) {
            $q->whereHas('productexpire', function ($subQuery) use ($search_quantity) {
                $subQuery->where('quantity', '=', $search_quantity);
            });
        })
        // Sorting by product expire quantity in ascending order
        ->when($sort_orderQty == 'quantity_expire_asc', function ($q) {
            return $q->join('product_expires', 'products.id', '=', 'product_expires.product_id')
                ->select('products.*')
                ->groupBy('products.id')
                ->orderByRaw('SUM(product_expires.quantity) ASC');
        })
        // Sorting by product expire quantity in descending order
        ->when($sort_orderQty == 'quantity_expire_desc', function ($q) {
            return $q->join('product_expires', 'products.id', '=', 'product_expires.product_id')
                ->select('products.*')
                ->groupBy('products.id')
                ->orderByRaw('SUM(product_expires.quantity) DESC');
        })
        // Sorting by total quantity
        ->when($sort_orderQty == 'quantity_asc', function ($q) {
            return $q->orderBy('quantity', 'asc');
        })
        ->when($sort_orderQty == 'quantity_desc', function ($q) {
            return $q->orderBy('quantity', 'desc');
        })
        // Sorting by order count
        ->when($sort_orderQty == 'order_asc', function ($q) {
            return $q->orderBy('order_count', 'asc');
        })
        ->when($sort_orderQty == 'order_desc', function ($q) {
            return $q->orderBy('order_count', 'desc');
        })
        // Sorting by name (alphabetically)
        ->when($sort_orderQty == 'name_asc', function ($q) {
            return $q->orderBy('name', 'asc');
        })
        ->when($sort_orderQty == 'name_desc', function ($q) {
            return $q->orderBy('name', 'desc');
        })
        // Sorting by selling price
        ->when($sort_orderQty == 'price_asc', function ($q) {
            return $q->orderBy('selling_price', 'asc');
        })
        ->when($sort_orderQty == 'price_desc', function ($q) {
            return $q->orderBy('selling_price', 'desc');
        })
        // Sorting by expire date
        ->when($sort_orderQty == 'expire_date_asc', function ($q) {
            return $q->orderBy('expiry_date', 'asc');
        })
        ->when($sort_orderQty == 'expire_date_desc', function ($q) {
            return $q->orderBy('expiry_date', 'desc');
        })
        // Default sorting
        ->when($sort_orderQty == 'default', function ($q) {
            return $q->orderBy('id');
        });

}

public function listreportexpire(Request $request): View|Factory|Application
{
    $search          = $request['search'];
    $sort_orderQty   = $request['sort_orderQty'];
    $search_quantity = $request['search_quantity'];

    $products = $this->expiryReportQuery($request)
                    ->latest()
                    ->paginate(Helpers::pagination_limit())
                    ->appends($request->query());

    return view('admin-views.product.listreportexpire', compact('products', 'search', 'sort_orderQty', 'search_quantity'));
}

/** كشف الصلاحية كملف اكسيل، بنفس الفلاتر وعلى كامل النتيجة. */
public function exportReportExpire(Request $request)
{
    $rows = $this->expiryReportQuery($request)
        ->latest()
        ->get()
        ->map(function ($product) {
            // المنتج قد يحمل أكثر من دفعة صلاحية، فنجمع الكميات ونعرض أقرب
            // تاريخ انتهاء لأنه هو الحرج في هذا الكشف.
            $batches = $product->productexpire ?? collect();

            // تاريخ الانتهاء على المنتج نفسه (products.expiry_date)؛ جدول
            // product_expires يحمل الكميات فقط ولا يحمل تاريخًا.
            $expiry = $product->expiry_date
                ? \Carbon\Carbon::parse($product->expiry_date)->format('Y-m-d')
                : '';

            return [
                'الكود'             => $product->id,
                'كود المنتج'        => $product->product_code,
                'اسم المنتج'        => $product->name,
                'تاريخ الانتهاء'    => $expiry,
                'الكمية'            => $product->quantity,
                'كمية قاربت الانتهاء' => (float) $batches->sum('quantity'),
                'عدد الدفعات'       => $batches->count(),
                'السعر'             => $product->selling_price,
            ];
        });

    return $this->streamCsvRows($rows, $this->exportFilename('expiry-report'));
}



    /**
     * @return Application|Factory|View
     */
    public function index(): View|Factory|Application
    {
        $categories = $this->category->where(['position' => 0])->where('type',1)->where('status',1)->get();
        $brands = $this->brand->get();
        $suppliers = $this->supplier->get();
        $stores = $this->store->get();
        $units = $this->unit->get();
        $taxes = $this->taxe->get();

        return view('admin-views.product.add', compact('categories','taxes','brands','suppliers','units','stores'));
    }
     public function indexexpire(): View|Factory|Application
    {
        $categories = $this->category->where(['position' => 0])->where('status',1)->get();
        $brands = $this->brand->get();
        $suppliers = $this->supplier->get();
        $units = $this->unit->get();
        $products = $this->product->get();
        return view('admin-views.product.addexpire', compact('categories','brands','suppliers','units','products'));
    }
    public function listexpire(Request $request): View|Factory|Application
{

    $productsexpire = $this->productexpire->get();

    return view('admin-views.product.listexpire', compact('productsexpires'));
}


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function get_categories(Request $request): JsonResponse
    {
        $cat = $this->category->where('type',1)->where(['parent_id' => $request->parent_id])->get();
        $res = '<option value="' . 0 . '" disabled selected>---'.translate('Select').'---</option>';
        foreach ($cat as $row) {
            if ($row->id == $request->sub_category) {
                $res .= '<option value="' . $row->id . '" selected >' . $row->name . '</option>';
            } else {
                $res .= '<option value="' . $row->id . '">' . $row->name . '</option>';
            }
        }
        return response()->json([
            'options' => $res,
        ]);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
public function store(Request $request): RedirectResponse
{
    $request->validate([
        'name' => 'required|unique:products',
        'product_code' => 'required|unique:products',
        'category_id' => 'required',
        'unit_type' => 'required',
        'unit_value' => 'required|numeric|min:0',
        'quantity' => 'required|numeric|min:1',
        'selling_price' => 'required|numeric|min:1',
        // 'selling_price1' => 'nullable|numeric|min:0',
        // 'selling_price2' => 'nullable|numeric|min:0',
        // 'selling_price3' => 'nullable|numeric|min:0',
        // 'selling_price4' => 'nullable|numeric|min:0',
        'purchase_price' => 'required|numeric|min:1',
        // 'purchase_price1' => 'nullable|numeric|min:0',
        // 'purchase_price2' => 'nullable|numeric|min:0',
        // 'purchase_price3' => 'nullable|numeric|min:0',
        // 'purchase_price4' => 'nullable|numeric|min:0',
        // 'limit_stock' => 'nullable|numeric|min:0',
        // 'limit_web' => 'nullable|numeric|min:0',
        'expiry_date' => 'nullable|date',
        'type' => 'required|string',
        // 'store_id' => 'required', // Validate the store_id field
    ], [
        'name.required' => translate('Product arabic name is required'),
        'category_id.required' => translate('Category is required'),
    ]);

    // Handle discount logic
    if ($request['discount_type'] == 'percent') {
        $dis = ($request['selling_price'] / 100) * $request['discount'];
    } else {
        $dis = $request['discount'];
    }

    if ($request['selling_price'] <= $dis) {
        Toastr::warning(translate('Discount cannot be more than Selling price'));
        return back();
    }

    // Create new Product instance
    $products = new Product();

    // Set product attributes
    $products->name = $request->name;
    $products->name_en = $request->name_en;
    $products->product_code = $request->product_code;
    $products->category_id = $request->category_id;
    $products->unit_type = $request->unit_type;
    $products->unit_value = $request->unit_value;
    $products->quantity = $request->quantity;
    $products->brand = $request->brand_id;
    $products->discount_type = $request->discount_type;
    $products->discount = $request->discount ?? 0;
    $products->tax = $request->tax ?? 0;
    $products->order_count = 0;

    // Set pricing details
    $products->selling_price = $request->selling_price;
    // $products->selling_price1 = $request->selling_price1;
    // $products->selling_price2 = $request->selling_price2;
    // $products->selling_price3 = $request->selling_price3;
    // $products->selling_price4 = $request->selling_price4;

    $products->purchase_price = $request->purchase_price;
    // $products->purchase_price1 = $request->purchase_price1;
    // $products->purchase_price2 = $request->purchase_price2;
    // $products->purchase_price3 = $request->purchase_price3;
    // $products->purchase_price4 = $request->purchase_price4;

    // Set additional product details
    // $products->limit_stock = $request->limit_stock ?? 0;
    // $products->limit_web = $request->limit_web ?? 0;
    $products->expiry_date = $request->expiry_date;
    $products->type = $request->type;
            // $product->tax_id = $request->tax_id ?? 0;


    // Upload the product image
    $products->image = Helpers::upload('product/', 'png', $request->file('image'));

    // Set supplier ID
    $products->supplier_id = $request->supplier_id;

    // Save the product
    $products->save();

    // Handle stock creation
    // $nstock = new Stock(); // Assuming you have a Stock model
    // $nstock->store_id = $request->store_id;
    // $nstock->product_id = $products->id; // Link the stock to the product
    // $nstock->main_stock = $request->quantity;
    // $nstock->stock = $request->quantity;
    // $nstock->save();

    // Success notification
    Toastr::success(translate('Product Added Successfully'));

    return redirect()->route('admin.product.list');
}


public function storeexpire(Request $request): RedirectResponse
{
    $request->validate([
        'product_id' => 'required',
        'quantity' => 'required|integer|min:1',
    ]);

    $product_id = $request->product_id;
    $quantity = $request->quantity;

    // Check if the product already exists in the productexpire table
    $productexpire = ProductExpire::where('product_id', $product_id)->first();

    if ($productexpire) {
        // If the product exists, increment the quantity
        $productexpire->quantity += $quantity;
        $productexpire->save();
    } else {
        // If the product doesn't exist, create a new record
        $productexpire = new ProductExpire();
        $productexpire->product_id = $product_id;
        $productexpire->quantity = $quantity;
        $productexpire->save();
    }

    // Decrement the quantity from the products table
    $product = Product::findOrFail($product_id);

    if ($product->quantity < $quantity) {
        Toastr::error(translate('Insufficient product quantity in stock.'));
        return redirect()->back();
    }

    $product->quantity -= $quantity;
    $product->save();

    Toastr::success(translate('Product added to expire list and stock updated successfully.'));

    return redirect()->route('admin.product.list');
}


    /**
     * @param $id
     * @return Application|Factory|View
     */
    public function edit($id): Factory|View|Application
    {
        $product = $this->product->find($id);
        $product_category = json_decode($product->category_id);
        $categories = $this->category->where(['position' => 0])->where('type',1)->get();
        $brands = $this->brand->get();
        $suppliers = $this->supplier->get();
        $units = $this->unit->get();
                $taxes = $this->taxe->get();

                $stores = $this->store->get();
        return view('admin-views.product.edit', compact('product','categories','brands','taxes','product_category','suppliers','units','stores'));
    }

    /**
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
public function update(Request $request, $id): RedirectResponse
{
    $product = Product::findOrFail($id); // Ensure the product exists

    // Validate the request inputs
    $request->validate([
        'name' => 'required',
        'product_code'=> 'required',
        'category_id' => 'required',
        'unit_type' => 'nullable',
        'unit_value' => 'required|numeric|min:0',
        'quantity' => 'required|numeric|min:1',
        'selling_price' => 'required|numeric|min:1',
        // 'selling_price1' => 'nullable|numeric|min:0',
        // 'selling_price2' => 'nullable|numeric|min:0',
        // 'selling_price3' => 'nullable|numeric|min:0',
        // 'selling_price4' => 'nullable|numeric|min:0',
        'purchase_price' => 'required|numeric|min:1',
        // 'purchase_price1' => 'nullable|numeric|min:0',
        // 'purchase_price2' => 'nullable|numeric|min:0',
        // 'purchase_price3' => 'nullable|numeric|min:0',
        // 'purchase_price4' => 'nullable|numeric|min:0',
        // 'limit_stock' => 'nullable|numeric|min:0',
        // 'limit_web' => 'nullable|numeric|min:0',
        'expiry_date' => 'nullable|date',
        'type' => 'required|string',
    ]);

    // Handle discount logic
    if ($request['discount_type'] == 'percent') {
        $dis = ($request['selling_price'] / 100) * $request['discount'];
    } else {
        $dis = $request['discount'];
    }

    if ($request['selling_price'] <= $dis) {
        Toastr::warning(translate('Discount cannot be more than Selling price'));
        return back();
    }

    // Update product details
    $product->name = $request->name;
    $product->name_en = $request->name_en;
    $product->product_code = $request->product_code;
    $product->category_id = $request->category_id;
    $product->unit_type = $request->unit_type;
    $product->unit_value = $request->unit_value;
    $product->quantity = $request->quantity;
    $product->brand = $request->brand_id;
    $product->discount_type = $request->discount_type;
    $product->discount = $request->discount ?? 0;
    $product->tax = $request->tax ?? 0;
        // $product->tax_id = $request->tax_id ?? 0;


    // Update pricing details
    $product->selling_price = $request->selling_price;
    // $product->selling_price1 = $request->selling_price1;
    // $product->selling_price2 = $request->selling_price2;
    // $product->selling_price3 = $request->selling_price3;
    // $product->selling_price4 = $request->selling_price4;

    $product->purchase_price = $request->purchase_price;
    // $product->purchase_price1 = $request->purchase_price1;
    // $product->purchase_price2 = $request->purchase_price2;
    // $product->purchase_price3 = $request->purchase_price3;
    // $product->purchase_price4 = $request->purchase_price4;

    // Additional product data
    // $product->limit_stock = $request->limit_stock ?? 0;
    // $product->limit_web = $request->limit_web ?? 0;
    $product->expiry_date = $request->expiry_date;
    $product->type = $request->type;

    // Update the image if a new one is provided
    $product->image = $request->has('image') ? Helpers::update('product/', $product->image, 'png', $request->file('image')) : $product->image;

    // Update supplier ID
    $product->supplier_id = $request->supplier_id;

    // Save the updated product
    $product->save();

    // Update stock information
    // $stock = Stock::where('product_id', $product->id)->first();
    // if ($stock) {
    //     $stock->main_stock = $request->quantity;
    //     $stock->stock = $request->quantity;
    //     $stock->save();
    // } else {
    //     // If no stock exists, create a new stock entry
    //     $nstock = new Stock();
    //     $nstock->store_id = $request->store_id;
    //     $nstock->product_id = $product->id;
    //     $nstock->main_stock = $request->quantity;
    //     $nstock->stock = $request->quantity;
    //     $nstock->save();
    // }

    // Success notification
    Toastr::success(translate('Product Updated Successfully'));

    return redirect()->route('admin.product.list');
}


    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function delete(Request $request): RedirectResponse
    {
        $product = $this->product->find($request->id);
        // if (Storage::disk('public')->exists('product/' . $product->image)) {
        //     Storage::disk('public')->delete('product/' .  $product->image);
        // }

        $product->delete();
        Toastr::success(translate('Product removed'));
        return back();
    }

    /**
     * @param Request $request
     * @param $id
     * @return Application|Factory|View|RedirectResponse
     */
    public function barcode_generate(Request $request, $id): View|Factory|RedirectResponse|Application
    {
        if($request->limit >270)
        {
            Toastr::warning(translate('You can not generate more than 270 barcode'));
            return back();
        }
        $product = $this->product->where('id',$id)->first();
        $limit = $request->limit??4;
        return view('admin-views.product.barcode-generate',compact('product','limit'));
    }

    /**
     * @param $id
     * @return Application|Factory|View
     */
    public function barcode($id): Factory|View|Application
    {
        $product = $this->product->where('id',$id)->first();
        $limit = 28;
        return view('admin-views.product.barcode',compact('product','limit'));
    }

    /**
     * @return Application|Factory|View
     */
    public function bulk_import_index(): Factory|View|Application
    {
        return view('admin-views.product.bulk-import');
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function bulk_import_data(Request $request): RedirectResponse
    {
        try {
            $collections = (new FastExcel)->import($request->file('products_file'));
        } catch (\Exception $exception) {
            Toastr::error(translate('You have uploaded a wrong format file, please upload the right file'));
            return back();
        }

        $col_key = ['name','product_code','unit_type','unit_value','brand','category_id','sub_category_id','purchase_price','selling_price','discount_type','discount','tax','quantity', 'supplier_id'];
        foreach ($collections as $key => $collection) {
            foreach ($collection as $key => $value) {
                if ($key!="" && !in_array($key, $col_key)) {
                    Toastr::error(translate('Please upload the correct format file.'));
                    return back();
                }
            }
        }

        foreach ($collections as $key => $collection) {
            if ($collection['name'] === "") {
                Toastr::error('Please fill row:' . ($key + 2) . ' field: name ');
                return back();
            } elseif ($collection['product_code'] === "") {
                Toastr::error('Please fill row:' . ($key + 2) . ' field: product_code ');
                return back();
            } elseif ($collection['unit_type'] ==="") {
                Toastr::error('Please fill row:' . ($key + 2) . ' field: product_code ');
                return back();
            } elseif ($collection['unit_value'] ==="") {
                Toastr::error('Please fill row:' . ($key + 2) . ' field: unit value ');
                return back();
            } elseif (!is_numeric($collection['unit_value'])) {
                Toastr::error('Unit Value of row ' . ($key + 2) . ' must be number');
                return back();
            } elseif ($collection['brand'] === "") {
                Toastr::error('Please fill row:' . ($key + 2) . ' field: brand ');
                return back();
            } elseif ($collection['category_id'] === "") {
                Toastr::error('Please fill row:' . ($key + 2) . ' field: category_id ');
                return back();
            }  elseif ($collection['purchase_price'] ==="") {
                Toastr::error('Please fill row:' . ($key + 2) . ' field: purchase price ');
                return back();
            } elseif (!is_numeric($collection['purchase_price'])) {
                Toastr::error('Purchase Price of row ' . ($key + 2) . ' must be number');
                return back();
            } elseif ($collection['selling_price'] === "") {
                Toastr::error('Please fill row:' . ($key + 2) . ' field: selling_price ');
                return back();
            } elseif (!is_numeric($collection['selling_price'])) {
                Toastr::error('Please fill row:' . ($key + 2) . ' field: number ');
                return back();
            }  elseif ($collection['discount_type'] === "") {
                Toastr::error('Please fill row:' . ($key + 2) . ' field: discount type');
                return back();
            } elseif ($collection['discount'] ==="") {
                Toastr::error('Please fill row:' . ($key + 2) . ' field: discount ');
                return back();
            } elseif (!is_numeric($collection['discount'])) {
                Toastr::error('Discount of row ' . ($key + 2) . ' must be number');
                return back();
            } elseif ($collection['tax'] === "") {
                Toastr::error('Please fill row:' . ($key + 2) . ' field: tax ');
                return back();
            } elseif (!is_numeric($collection['tax'])) {
                Toastr::error('Tax of row ' . ($key + 2) . ' must be number');
                return back();
            } elseif ($collection['quantity'] === "") {
                Toastr::error('Please fill row:' . ($key + 2) . ' field: quantity ');
                return back();
            } elseif (!is_numeric($collection['quantity'])) {
                Toastr::error('Quantity of row ' . ($key + 2) . ' must be number');
                return back();
            } 

            $product = [
                'discount_type' => $collection['discount_type'],
                'discount' => $collection['discount'],
            ];
            if ($collection['selling_price'] <= Helpers::discount_calculate($product, $collection['selling_price'])) {
                Toastr::error(translate('Discount can not be more or equal to the price in row '). ($key + 2));
                return back();
            }
            $product =  $this->product->where('product_code',$collection['product_code'])->first();
            if($product)
            {
                Toastr::warning(translate('product code row').' : ' . ($key + 2) .' '.translate('already exist'));
                return back();
            }
        }
        $data = [];
        foreach ($collections as $collection) {
          $product =  $this->product->where('product_code',$collection['product_code'])->first();
          if($product)
          {
              Toastr::success(translate('product code already exist'));
              return back();
          }
            $data[] = [
                'name' => $collection['name'],
                'product_code' => $collection['product_code'],
                'image' => json_encode(['def.png']),
                'unit_type' => $collection['unit_type'],
                'unit_value' => $collection['unit_value'],
                'brand' => $collection['brand'],
                'category_id' => $collection['category_id'],
                'purchase_price' => $collection['purchase_price'],
                'selling_price' => $collection['selling_price'],
                'discount_type' => $collection['discount_type'],
                'discount' => $collection['discount'],
                'tax' => $collection['tax'],
                'quantity' => $collection['quantity'],
                'supplier_id' => $collection['supplier_id'],
                'created_at' => now(),
                'updated_at' => now()
            ];
        }
        DB::table('products')->insert($data);
        Toastr::success(count($data) . ' - '.translate('Products imported successfully'));
        return back();
    }

    /**
     * @return string|StreamedResponse
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function bulk_export_data(): StreamedResponse|string
    {
        $products = $this->product->all();
        $storage = [];
        foreach($products as $item){
            $category_id = 0;
            $sub_category_id = 0;

            // foreach(json_decode($item->category_ids, true) as $category)
            // {
            //     if($category['position']==1)
            //     {
            //         $category_id = $category['id'];
            //     }
            //     else if($category['position']==2)
            //     {
            //         $sub_category_id = $category['id'];
            //     }
            // }

            $storage[] = [
                'name' => $item['name'],
                'product_code' => $item['product_code'],
                'unit_type' => $item['unit_type'],
                'unit_value' => $item['unit_value'],
                'category_id' => $item['category_id'],
                'brand' => $item['brand'],
                'purchase_price' => $item['purchase_price'],
                'selling_price' => $item['selling_price'],
                'discount_type' => $item['discount_type'],
                'discount' => $item['discount'],
                'tax' => $item['tax'],
                'quantity' => $item['quantity'],
                'supplier_id' => $item['supplier_id'],
            ];
        }
        return (new FastExcel($storage))->download('products.xlsx');
    }

}
