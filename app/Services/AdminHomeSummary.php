<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\AdminSeller;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * أرقام الصفحة الرئيسية للوحة.
 *
 * كانت الصفحة شعارًا في منتصف فراغ، فلا تخبر الداخل بشيء. كل رقم هنا
 * مقصور على مناديب المستخدم الحالي، ومخزَّن مؤقتًا: الصفحة تُفتح مع كل
 * دخول ولا تحتمل ست استعلامات ثقيلة في كل مرة.
 */
class AdminHomeSummary
{
    /** ثوانٍ. قصيرة بما يكفي لتبقى الأرقام ذات معنى. */
    private const TTL = 120;

    /**
     * أسماء الشهور عربية.
     *
     * translatedFormat يتبع لغة التطبيق وهي en، فيكتب "September" في
     * صفحة عربية.
     */
    private const MONTHS = [
        1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
        5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
        9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
    ];

    public function for(Admin $admin): array
    {
        return Cache::remember(
            'admin.home.' . $admin->id,
            self::TTL,
            fn () => $this->build($admin)
        );
    }

    /**
     * نسبة التغيّر عن الشهر الماضي.
     *
     * null حين لا يوجد ما يُقاس عليه: القسمة على صفر ليست زيادة بلا حدّ،
     * وعرض «+100%» لشهر بدأ من لا شيء يضلّل.
     */
    private function change(float $now, float $before): ?array
    {
        if ($before <= 0.0) {
            return null;
        }

        $pct = round((($now - $before) / $before) * 100, 1);

        return [
            'pct'  => $pct,
            'dir'  => $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'flat'),
            'icon' => $pct > 0 ? '↗' : ($pct < 0 ? '↘' : '→'),
            'text' => ($pct > 0 ? '+' : '')
                . rtrim(rtrim(number_format($pct, 1), '0'), '.') . '%',
        ];
    }

    private function build(Admin $admin): array
    {
        $sellerIds = AdminSeller::where('admin_id', $admin->id)->pluck('seller_id')->all();
        $sellerIds[] = $admin->id;
        $sellerIds = array_values(array_unique($sellerIds));

        $start = Carbon::now()->startOfMonth();
        $end   = Carbon::now()->endOfMonth();

        // استعلام واحد لأرقام الشهر بدل ثلاثة.
        $month = Order::where('type', 4)
            ->whereIn('owner_id', $sellerIds)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('COUNT(*) as orders,
                         COALESCE(SUM(order_amount), 0)   as total,
                         COALESCE(SUM(collected_cash), 0) as collected')
            ->first();

        $sales     = (float) ($month->total ?? 0);
        $collected = (float) ($month->collected ?? 0);

        // الشهر الماضي، لقياس الفرق. بلا مقارنة يبقى الرقم مجرّدًا: 66 ألفًا
        // قد تكون أفضل شهر أو أسوأه.
        $prevStart = $start->copy()->subMonthNoOverflow()->startOfMonth();
        $prevEnd   = $prevStart->copy()->endOfMonth();

        $prev = Order::where('type', 4)
            ->whereIn('owner_id', $sellerIds)
            ->whereBetween('created_at', [$prevStart, $prevEnd])
            ->selectRaw('COUNT(*) as orders,
                         COALESCE(SUM(order_amount), 0)   as total,
                         COALESCE(SUM(collected_cash), 0) as collected')
            ->first();

        $prevSales     = (float) ($prev->total ?? 0);
        $prevCollected = (float) ($prev->collected ?? 0);

        return [
            'trend' => [
                'sales'     => $this->change($sales, $prevSales),
                'collected' => $this->change($collected, $prevCollected),
                'remaining' => $this->change(
                    max($sales - $collected, 0),
                    max($prevSales - $prevCollected, 0)
                ),
                'orders'    => $this->change((int) ($month->orders ?? 0), (int) ($prev->orders ?? 0)),
            ],

            // أسماء الشهور عربية صراحةً: translatedFormat يتبع لغة التطبيق
            // وهي en هنا، فيخرج "September" في صفحة عربية.
            'month_label' => self::MONTHS[(int) $start->format('n')] . ' ' . $start->format('Y'),

            'orders'    => (int) ($month->orders ?? 0),
            'sales'     => $sales,
            'collected' => $collected,
            // المتبقّي لا ينزل تحت الصفر عند التحصيل الزائد.
            'remaining' => round(max($sales - $collected, 0), 2),

            'customers' => DB::table('seller_customers')
                ->whereIn('seller_id', $sellerIds)
                ->distinct()
                ->count('customer_id'),

            // المندوب الحالي لا يُعد مندوبًا لنفسه.
            'sellers' => max(count($sellerIds) - 1, 0),

            // limit_stock = 0 يعني بلا حد، فلا يُحتسب ناقصًا.
            'low_stock' => Product::where('limit_stock', '>', 0)
                ->whereColumn('quantity', '<=', 'limit_stock')
                ->count(),

            // المرتجع يُقرأ كما تقرؤه شاشة الفواتير: صفوف type=7 معلّقة
            // بـparent_id. payment_status فارغ هنا فلا يُعتمد عليه.
            'recent_orders' => Order::where('type', 4)
                ->whereIn('owner_id', $sellerIds)
                ->with(['customer:id,name', 'seller:id,f_name,l_name'])
                ->addSelect(['returned_amount' => Order::selectRaw('COALESCE(SUM(returns.order_amount), 0)')
                    ->from('orders as returns')
                    ->whereColumn('returns.parent_id', 'orders.id')
                    ->where('returns.type', 7)])
                ->latest('id')
                ->limit(6)
                ->get(['id', 'user_id', 'owner_id', 'order_amount', 'collected_cash', 'created_at']),
        ];
    }
}
