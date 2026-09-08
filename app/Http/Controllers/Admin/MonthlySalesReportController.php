<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSeller;
use App\Models\Installment;
use App\Models\Order;
use App\Models\Product;
use App\Models\Region;
use App\Models\StockHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * تقرير ملخص المبيعات الشهري.
 *
 * مبني على الشكل المطلوب: صفوف مقارنة ثابتة × أعمدة منتجات، يُعرض لكل منطقة
 * على حدة ثم في جداول مجمّعة لكل المناطق. يغطي ثلاثة أقسام:
 *   1) المخزون   — المتبقي مع المناديب، المرسل من المخازن، المُوفَّر، الباقي
 *   2) المبيعات  — الآجل، المحصل من الآجل، المحصل نقدي، المرحّل للشهر القادم
 *   3) التحصيلات — عدد العبوات والمبلغ لكل منطقة مع نسبة التحقيق لكل منتج
 */
class MonthlySalesReportController extends Controller
{
    public function index(Request $request)
    {
        $data = $this->build($request);

        return view('admin-views.reports.monthly-sales', $data + [
            'allProducts' => Product::orderBy('name')->get(['id', 'name', 'name_en']),
            'allRegions'  => Region::orderBy('name')->get(['id', 'name']),
            'selectedProductIds' => array_map('intval', (array) $request->input('product_ids', [])),
            'selectedRegionIds'  => array_map('intval', (array) $request->input('region_ids', [])),
        ]);
    }

    /** كل بيانات التقرير، مشتركة بين الشاشة والتصدير. */
    private function build(Request $request): array
    {
        $request->validate([
            'month'         => 'nullable|date_format:Y-m',
            'product_ids'   => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
            'region_ids'    => 'nullable|array',
            'region_ids.*'  => 'exists:regions,id',
        ]);

        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->input('month'))->startOfMonth()
            : Carbon::now()->startOfMonth();

        $start = $month->copy()->startOfMonth();
        $end   = $month->copy()->endOfMonth();

        $sellerIds = $this->sellerIds();
        $products  = $this->columnProducts((array) $request->input('product_ids', []), $sellerIds, $start, $end);
        $regions   = $this->reportRegions((array) $request->input('region_ids', []));

        // تحميل واحد لكل ما تحتاجه الأقسام، بدل استعلام لكل منطقة.
        $this->cache = [
            'monthOrders' => Order::where('type', 4)->whereIn('owner_id', $sellerIds)
                ->whereBetween('created_at', [$start, $end])
                ->with(['details', 'customer:id,region_id'])->get(),

            'priorOrders' => Order::where('type', 4)->whereIn('owner_id', $sellerIds)
                ->where('created_at', '<', $start)
                ->with(['details', 'customer:id,region_id'])->get(),

            'paidThisMonth' => Installment::whereIn('seller_id', $sellerIds)
                ->whereBetween('created_at', [$start, $end])
                ->select('order_id', DB::raw('SUM(total_price) as paid'))
                ->groupBy('order_id')
                ->pluck('paid', 'order_id'),

            // مناديب كل منطقة، لتفادي استعلام seller_regions لكل منطقة.
            'sellersByRegion' => DB::table('seller_regions')
                ->whereIn('seller_id', $sellerIds)
                ->get()
                ->groupBy('region_id')
                ->map(fn ($rows) => $rows->pluck('seller_id')->map(fn ($v) => (int) $v)->all())
                ->toArray(),
        ];

        $perRegion = $regions->map(fn ($region) => [
            'region' => $region,
            'stock'  => $this->stockRows($products, $sellerIds, $start, $end, $region->id),
            'sales'  => $this->salesRows($products, $sellerIds, $start, $end, $region->id),
        ])->values();

        return [
            'month'       => $month,
            'products'    => $products,
            'regions'     => $regions,
            'perRegion'   => $perRegion,
            'totals'      => [
                'stock' => $this->stockRows($products, $sellerIds, $start, $end, null),
                'sales' => $this->salesRows($products, $sellerIds, $start, $end, null),
            ],
            'collections' => $this->collectionMatrix($products, $regions, $sellerIds, $start, $end),
        ];
    }

    /** بيانات محمَّلة مرة واحدة لكل تقرير، تُرشَّح بعدها في الذاكرة. */
    private array $cache = [];

    /** فواتير منطقة بعينها من مجموعة محمَّلة سلفًا. null تعني كل المناطق. */
    private function ordersInRegion($orders, ?int $regionId)
    {
        if (!$regionId) {
            return $orders;
        }

        return $orders->filter(
            fn ($order) => (int) (optional($order->customer)->region_id ?? 0) === $regionId
        );
    }

    /** المناديب التابعون للإداري الحالي، بالإضافة إليه. */
    private function sellerIds(): array
    {
        $adminId = Auth::guard('admin')->id();

        $ids = AdminSeller::where('admin_id', $adminId)->pluck('seller_id')->all();
        $ids[] = $adminId;

        return array_values(array_unique($ids));
    }

    /**
     * أعمدة المنتجات. عند عدم الاختيار نعرض المنتجات التي تحرّكت خلال الشهر
     * فقط — عرض الكتالوج كاملًا يجعل الجدول غير قابل للقراءة.
     */
    private function columnProducts(array $productIds, array $sellerIds, Carbon $start, Carbon $end)
    {
        $productIds = array_filter($productIds);

        if (!empty($productIds)) {
            return Product::whereIn('id', $productIds)->orderBy('name')->get(['id', 'name', 'name_en']);
        }

        $movedIds = DB::table('order_details')
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->whereIn('orders.type', [4, 7])
            ->whereIn('orders.owner_id', $sellerIds)
            ->whereBetween('orders.created_at', [$start, $end])
            ->distinct()
            ->pluck('order_details.product_id')
            ->filter()
            ->values();

        return Product::whereIn('id', $movedIds)->orderBy('name')->get(['id', 'name', 'name_en']);
    }

    /** المناطق المعروضة. */
    private function reportRegions(array $regionIds)
    {
        $regionIds = array_filter($regionIds);
        $query = Region::orderBy('name');

        if (!empty($regionIds)) {
            $query->whereIn('id', $regionIds);
        } else {
            // المناطق التي لها عملاء فقط؛ جدول regions يحمل محافظات كثيرة
            // غير مستخدمة وعرضها كلها يُغرق التقرير بجداول فارغة.
            $query->whereIn('id', DB::table('customers')->whereNotNull('region_id')->distinct()->pluck('region_id'));
        }

        return $query->get(['id', 'name']);
    }

    /** قيد المنطقة على استعلام الطلبات، عبر العميل. */
    private function scopeRegion($query, ?int $regionId)
    {
        if ($regionId) {
            $query->whereHas('customer', fn ($c) => $c->where('region_id', $regionId));
        }

        return $query;
    }

    /**
     * صفوف قسم المخزون لكل منتج.
     *
     * المخزون يُسجَّل على المندوب لا على العميل، فلا منطقة له مباشرة؛ عند
     * تحديد منطقة نقصر الحساب على مناديب تلك المنطقة عبر seller_regions.
     * تسوية stock_histories تحمل main_stock (ما كان بحوزته) وstock (المباع).
     */
    private function stockRows($products, array $sellerIds, Carbon $start, Carbon $end, ?int $regionId): array
    {
        $rows = [
            'opening'  => [], // المتبقي مع المناديب (من الشهر السابق)
            'sent'     => [], // المرسل من مخازن الشركة
            'supplied' => [], // عدد الوحدات التي تم توفيرها خلال الشهر
            'closing'  => [], // الباقي مع المناديب (بعد انتهاء الشهر)
        ];

        $scopedSellers = $regionId
            ? array_values(array_intersect($sellerIds, $this->cache['sellersByRegion'][$regionId] ?? []))
            : $sellerIds;

        $stockTotals = $this->stockTotals($products->pluck('id')->all(), $scopedSellers, $start, $end);

        foreach ($products as $product) {
            if (empty($scopedSellers)) {
                foreach (array_keys($rows) as $k) {
                    $rows[$k][$product->id] = 0;
                }
                continue;
            }

            // الأعداد مأخوذة من مجاميع محسوبة مسبقًا لكل المنتجات دفعةً
            // واحدة؛ كان هنا استعلامان لكل منتج في كل منطقة (مئات
            // الاستعلامات على تقرير واحد).
            $opening  = (int) ($stockTotals['opening'][$product->id] ?? 0);
            $issued   = (int) ($stockTotals['issued'][$product->id] ?? 0);
            $supplied = (int) ($stockTotals['supplied'][$product->id] ?? 0);

            $rows['opening'][$product->id]  = $opening;
            $rows['sent'][$product->id]     = max(0, $issued - $opening);
            $rows['supplied'][$product->id] = $supplied;
            $rows['closing'][$product->id]  = max(0, $issued - $supplied);
        }

        return $rows;
    }

    /**
     * مجاميع المخزون لكل المنتجات في استعلامين اثنين بدل استعلامين لكل منتج.
     *
     * SUM في قاعدة البيانات بدل تحميل الصفوف وجمعها في PHP.
     */
    private function stockTotals(array $productIds, array $sellerIds, Carbon $start, Carbon $end): array
    {
        $empty = ['opening' => [], 'issued' => [], 'supplied' => []];

        if (empty($productIds) || empty($sellerIds)) {
            return $empty;
        }

        // رصيد أول المدة: غير المباع في التسويات السابقة.
        $opening = StockHistory::query()
            ->whereIn('product_id', $productIds)
            ->whereIn('seller_id', $sellerIds)
            ->where('created_at', '<', $start)
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(CASE WHEN main_stock > stock THEN main_stock - stock ELSE 0 END) as total')
            ->pluck('total', 'product_id')
            ->toArray();

        // تسويات الشهر نفسه: ما كان بحوزته وما بِيع منه.
        $current = StockHistory::query()
            ->whereIn('product_id', $productIds)
            ->whereIn('seller_id', $sellerIds)
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(main_stock) as issued, SUM(stock) as supplied')
            ->get();

        return [
            'opening'  => $opening,
            'issued'   => $current->pluck('issued', 'product_id')->toArray(),
            'supplied' => $current->pluck('supplied', 'product_id')->toArray(),
        ];
    }

    /**
     * صفوف قسم المبيعات لكل منتج: عدد العبوات والمبلغ.
     *
     * التحصيل يقع على الفاتورة ككل لا على صنف بعينه، فيُوزَّع على الأصناف
     * بنسبة قيمة كل صنف من إجمالي الفاتورة.
     */
    private function salesRows($products, array $sellerIds, Carbon $start, Carbon $end, ?int $regionId): array
    {
        $productIds = $products->pluck('id')->all();
        $blank = fn () => array_fill_keys($productIds, ['qty' => 0.0, 'amount' => 0.0]);

        $rows = [
            'carried_credit'  => $blank(), // المرحل آجل (من الأشهر السابقة)
            'collected_prior' => $blank(), // المحصل من الآجل (من الأشهر السابقة)
            'collected_month' => $blank(), // المحصل من الآجل (خلال الشهر)
            'collected_cash'  => $blank(), // المحصل نقدي
            'carried_forward' => $blank(), // المرحل آجل للشهر القادم
        ];

        if (empty($productIds)) {
            return $rows;
        }

        // الفواتير والتحصيلات مُحمَّلة مرة واحدة في build() وتُرشَّح هنا في
        // الذاكرة؛ كان كل نداء يعيد الاستعلام فتتكرر مئات المرات مع المناطق.
        $monthOrders = $this->ordersInRegion($this->cache['monthOrders'], $regionId);
        $priorOrders = $this->ordersInRegion($this->cache['priorOrders'], $regionId);
        $paidThisMonth = $this->cache['paidThisMonth'];

        foreach ($monthOrders as $order) {
            $total = (float) $order->order_amount;
            $paid  = (float) $order->transaction_reference;
            $viaInstallment = (float) ($paidThisMonth[$order->id] ?? 0);
            $cash = max(0.0, $paid - $viaInstallment);
            $remaining = max(0.0, $total - $paid);
            $basis = $this->lineBasis($order);

            foreach ($this->allocate($order, $productIds) as $pid => $line) {
                $share = $basis > 0 ? $line['amount'] / $basis : 0;

                $rows['collected_cash'][$pid]['amount'] += $cash * $share;
                if ($cash > 0) {
                    $rows['collected_cash'][$pid]['qty'] += $line['qty'];
                }

                $rows['collected_month'][$pid]['amount'] += $viaInstallment * $share;

                $rows['carried_forward'][$pid]['amount'] += $remaining * $share;
                if ($remaining > 0) {
                    $rows['carried_forward'][$pid]['qty'] += $line['qty'];
                }
            }
        }

        foreach ($priorOrders as $order) {
            $total   = (float) $order->order_amount;
            $paid    = (float) $order->transaction_reference;
            $paidNow = (float) ($paidThisMonth[$order->id] ?? 0);
            $open    = max(0.0, $total - $paid);
            // ما كان آجلًا عند بداية الشهر = المتبقي الآن + ما حُصِّل خلال الشهر
            $carried = $open + $paidNow;
            $basis   = $this->lineBasis($order);

            foreach ($this->allocate($order, $productIds) as $pid => $line) {
                $share = $basis > 0 ? $line['amount'] / $basis : 0;

                $rows['carried_credit'][$pid]['amount'] += $carried * $share;
                if ($carried > 0) {
                    $rows['carried_credit'][$pid]['qty'] += $line['qty'];
                }

                $rows['collected_prior'][$pid]['amount'] += $paidNow * $share;

                $rows['carried_forward'][$pid]['amount'] += $open * $share;
                if ($open > 0) {
                    $rows['carried_forward'][$pid]['qty'] += $line['qty'];
                }
            }
        }

        return $rows;
    }


    /**
     * إجمالي قيمة سطور الفاتورة كما هي مسجَّلة في التفاصيل.
     *
     * لا يساوي order_amount: الأسعار في order_details أسعار قائمة، والخصم
     * المطبَّق على الفاتورة غير مسجَّل في السطور (فحص البيانات أظهر نسبة
     * ثابتة ~1.35). لذا تُنسَب حصة كل صنف إلى مجموع السطور لا إلى
     * order_amount، وإلا زاد مجموع الحصص عن 1 وتضخّمت المبالغ.
     */
    private function lineBasis(Order $order): float
    {
        $basis = 0.0;

        foreach ($order->details as $detail) {
            $basis += ((float) $detail->price - (float) $detail->discount_on_product)
                * (float) $detail->quantity;
        }

        return $basis;
    }

    /** قيمة وكمية كل منتج داخل فاتورة واحدة. */
    private function allocate(Order $order, array $productIds): array
    {
        $lines = [];

        foreach ($order->details as $detail) {
            $pid = $detail->product_id;

            if (!in_array($pid, $productIds)) {
                continue;
            }

            $lines[$pid]['qty']    = ($lines[$pid]['qty'] ?? 0) + (float) $detail->quantity;
            $lines[$pid]['amount'] = ($lines[$pid]['amount'] ?? 0)
                + ((float) $detail->price - (float) $detail->discount_on_product) * (float) $detail->quantity;
        }

        return $lines;
    }

    /**
     * مصفوفة التحصيلات: لكل منتج، عدد العبوات والمبلغ في كل منطقة، ثم
     * الإجمالي ونسبة كل منطقة منه (تغذّي رسوم نسبة التحقيق).
     */
    private function collectionMatrix($products, $regions, array $sellerIds, Carbon $start, Carbon $end): array
    {
        $matrix = [];

        // ترشيح في الذاكرة من المجموعة المُحمَّلة مرة واحدة.
        $ordersByRegion = [];
        foreach ($regions as $region) {
            $ordersByRegion[$region->id] = $this->ordersInRegion($this->cache['monthOrders'], $region->id);
        }

        foreach ($products as $product) {
            $row = ['product' => $product, 'regions' => [], 'total' => ['qty' => 0.0, 'amount' => 0.0]];

            foreach ($regions as $region) {
                $qty = 0.0;
                $amount = 0.0;

                foreach ($ordersByRegion[$region->id] as $order) {
                    $paid  = (float) $order->transaction_reference;
                    $basis = $this->lineBasis($order);

                    foreach ($order->details as $detail) {
                        if ($detail->product_id != $product->id) {
                            continue;
                        }

                        $lineValue = ((float) $detail->price - (float) $detail->discount_on_product)
                            * (float) $detail->quantity;

                        $qty    += (float) $detail->quantity;
                        $amount += $basis > 0 ? $paid * ($lineValue / $basis) : 0;
                    }
                }

                $row['regions'][$region->id] = ['qty' => $qty, 'amount' => $amount];
                $row['total']['qty']    += $qty;
                $row['total']['amount'] += $amount;
            }

            $row['shares'] = [];
            foreach ($regions as $region) {
                $row['shares'][$region->id] = $row['total']['amount'] > 0
                    ? round($row['regions'][$region->id]['amount'] / $row['total']['amount'] * 100, 1)
                    : 0;
            }

            $matrix[] = $row;
        }

        return $matrix;
    }

    /** الصفوف الثابتة، مشتركة بين الشاشة والتصدير. */
    public static function stockLabels(): array
    {
        return [
            'opening'  => 'المتبقي مع المناديب (من الشهر السابق)',
            'sent'     => 'المرسل من مخازن الشركة',
            'supplied' => 'عدد الوحدات التي تم توفيرها خلال الشهر',
            'closing'  => 'الباقي مع المناديب (بعد انتهاء الشهر)',
        ];
    }

    public static function salesLabels(): array
    {
        return [
            'carried_credit'  => 'المرحل آجل (من الاشهر السابقة)',
            'collected_prior' => 'المحصل من الأجل (من الاشهر السابقة)',
            'collected_month' => 'المحصل من الأجل (خلال الشهر)',
            'collected_cash'  => 'المحصل نقدي',
            'carried_forward' => 'المرحل آجل للشهر القادم',
        ];
    }

    /** نفس التقرير كملف اكسيل. CSV بترميز UTF-8 مع BOM يفتحه Excel مباشرة. */
    public function export(Request $request)
    {
        $data = $this->build($request);
        $filename = 'monthly-sales-' . $data['month']->format('Y-m') . '.csv';

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            $names = $data['products']->map(fn ($p) => $p->name)->all();
            // عمود الإجمالي العام في آخر كل صف، مطابقًا لما تعرضه الشاشة.
            fputcsv($out, array_merge(['القسم', 'المنطقة', 'المقارنة'], $names, ['الإجمالي العام']));

            $emit = function ($section, $regionName, $labels, $source, $isMoney) use ($out, $data) {
                foreach ($labels as $key => $label) {
                    if ($isMoney) {
                        $qty = $data['products']->map(fn ($p) => round($source[$key][$p->id]['qty'] ?? 0, 2))->all();
                        fputcsv($out, array_merge([$section, $regionName, $label . ' - عدد عبوات'],
                            $qty, [round(array_sum($qty), 2)]));

                        $amount = $data['products']->map(fn ($p) => round($source[$key][$p->id]['amount'] ?? 0, 2))->all();
                        fputcsv($out, array_merge([$section, $regionName, $label . ' - المبلغ'],
                            $amount, [round(array_sum($amount), 2)]));
                    } else {
                        $row = $data['products']->map(fn ($p) => $source[$key][$p->id] ?? 0)->all();
                        fputcsv($out, array_merge([$section, $regionName, $label],
                            $row, [array_sum($row)]));
                    }
                }
            };

            foreach ($data['perRegion'] as $block) {
                $emit('المخزون', $block['region']->name, self::stockLabels(), $block['stock'], false);
                $emit('المبيعات', $block['region']->name, self::salesLabels(), $block['sales'], true);
            }

            $emit('إجمالي المخزون', 'كل المناطق', self::stockLabels(), $data['totals']['stock'], false);
            $emit('إجمالي المبيعات', 'كل المناطق', self::salesLabels(), $data['totals']['sales'], true);

            // التحصيلات: منتج × منطقة
            fputcsv($out, []);
            $header = ['التحصيلات', 'المنتج'];
            foreach ($data['regions'] as $r) {
                $header[] = $r->name . ' - عدد العبوات';
                $header[] = $r->name . ' - المبلغ';
            }
            $header[] = 'الإجمالي - عدد العبوات';
            $header[] = 'الإجمالي - المبلغ';
            fputcsv($out, $header);

            foreach ($data['collections'] as $row) {
                $line = ['', $row['product']->name];
                foreach ($data['regions'] as $r) {
                    $line[] = round($row['regions'][$r->id]['qty'], 2);
                    $line[] = round($row['regions'][$r->id]['amount'], 2);
                }
                $line[] = round($row['total']['qty'], 2);
                $line[] = round($row['total']['amount'], 2);
                fputcsv($out, $line);
            }

            fclose($out);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
