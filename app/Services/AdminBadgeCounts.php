<?php

namespace App\Services;

use App\Models\BusinessSetting;
use App\Models\HistoryInstallment;
use App\Models\Order;
use App\Models\ReserveProduct;
use App\Models\StockOrder;
use App\Models\TransactionSeller;
use Illuminate\Support\Facades\Cache;

/**
 * أعداد الشارات وبيانات الترويسة والقائمة الجانبية.
 *
 * كانت الترويسة والقائمة تُنفّذان عشرات الاستعلامات في كل صفحة، وكثير منها
 * بصيغة ->get()->count() التي تُحمّل كل الصفوف إلى الذاكرة لتعدّها (أبطأ
 * بـ 25 مرة من count() حتى على قاعدة الاختبار الصغيرة). كما كانت نفس
 * الأعداد تُحسب ست مرات داخل تعبير واحد.
 *
 * الحل: استعلام count() واحد لكل عدد، والنتيجة مخزَّنة مؤقتًا لفترة قصيرة.
 * الفترة قصيرة عمدًا: الشارات مؤشر تقريبي، وتأخّر ثوانٍ فيها مقبول مقابل
 * توفير أكثر من ثانية على كل طلب.
 */
class AdminBadgeCounts
{
    /** ثوانٍ. قصيرة بما يكفي لتبقى الشارات محدَّثة عمليًا. */
    private const TTL = 30;

    /** عدد الإشعارات المعروضة في القائمة المنسدلة. */
    private const NOTIFICATION_LIMIT = 10;

    public function counts(): array
    {
        return Cache::remember('admin.badge.counts', self::TTL, function () {
            return [
                'orders_notification'       => Order::where('notification', 1)->count(),
                'reservations_notification' => ReserveProduct::where('notification', 1)->count(),
                'installments_notification' => HistoryInstallment::where('notification', 1)->count(),
                'transaction_sellers'       => TransactionSeller::count(),

                'orders_type_4'  => Order::where('type', 4)->count(),
                'orders_type_7'  => Order::where('type', 7)->count(),
                'orders_type_12' => Order::where('type', 12)->count(),
                'orders_type_24' => Order::where('type', 24)->count(),

                'reserve_type_4_active'  => ReserveProduct::where('type', 4)->where('active', 1)->count(),
                'reserve_type_7_active'  => ReserveProduct::where('type', 7)->where('active', 1)->count(),
                'reserve_type_3_active2' => ReserveProduct::where('type', 3)->where('active', 2)->count(),
                'reserve_type_4'         => ReserveProduct::where('type', 4)->count(),
                'reserve_type_7'         => ReserveProduct::where('type', 7)->count(),

                'stock_orders' => StockOrder::count(),
            ];
        });
    }

    public function get(string $key): int
    {
        return (int) ($this->counts()[$key] ?? 0);
    }

    /** مجموع الإشعارات المعروض في جرس الترويسة. */
    public function notificationTotal(): int
    {
        $c = $this->counts();

        return $c['orders_notification']
             + $c['reservations_notification']
             + $c['installments_notification']
             + $c['transaction_sellers'];
    }

    /**
     * صفوف الإشعارات المعروضة في القائمة المنسدلة.
     *
     * كانت تُحمَّل كاملة بلا حد؛ القائمة تعرض بضعة أسطر فقط، فتحميل آلاف
     * الصفوف لعرض عشرة منها هدر خالص.
     */
    public function notifications(): array
    {
        return Cache::remember('admin.badge.notifications', self::TTL, function () {
            return [
                'installments' => HistoryInstallment::where('notification', 1)
                    ->latest('created_at')->limit(self::NOTIFICATION_LIMIT)->get(),
                'orders' => Order::where('notification', 1)
                    ->latest('created_at')->limit(self::NOTIFICATION_LIMIT)->get(),
                'reservations' => ReserveProduct::where('notification', 1)
                    ->latest('created_at')->limit(self::NOTIFICATION_LIMIT)->get(),
            ];
        });
    }

    /**
     * إعدادات المتجر المستخدمة في القوالب.
     *
     * كان كل مفتاح يُقرأ باستعلام مستقل، وتكرر ذلك 22-29 مرة في الصفحة
     * الواحدة. استعلام واحد يجلبها كلها.
     */
    public function settings(): array
    {
        return Cache::remember('admin.badge.settings', self::TTL, function () {
            return BusinessSetting::pluck('value', 'key')->toArray();
        });
    }

    public function setting(string $key, $default = null)
    {
        return $this->settings()[$key] ?? $default;
    }

    /** تُستدعى بعد أي تغيير يجب أن يظهر في الشارات فورًا. */
    public static function flush(): void
    {
        Cache::forget('admin.badge.counts');
        Cache::forget('admin.badge.notifications');
        Cache::forget('admin.badge.settings');
    }
}
