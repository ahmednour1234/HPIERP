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

        return [
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

            'recent_orders' => Order::where('type', 4)
                ->whereIn('owner_id', $sellerIds)
                ->with(['customer:id,name', 'seller:id,f_name,l_name'])
                ->latest('id')
                ->limit(6)
                ->get(['id', 'user_id', 'owner_id', 'order_amount', 'collected_cash', 'created_at']),
        ];
    }
}
