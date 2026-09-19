<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * يحرس كل مسارات اللوحة بصلاحية قسمها، مستنتَجة من الرابط.
 *
 * 26 مسارًا فقط من 327 كان محميًا، فأدمن بلا صلاحيات يفتح الحسابات
 * والتقارير بكتابة الرابط. إسناد صلاحية لكل مسار يدويًا على 327 سطرًا
 * يترك ثغرات، فالربط هنا بالبادئة: أي مسار تحت admin/account محكوم
 * بصلاحية accounts، وهكذا.
 *
 * ما لا بادئة له يمرّ: الغرض سدّ الأقسام المعروفة لا قفل اللوحة كلها
 * وكسر شاشات لم تُصنَّف بعد. تُضاف البادئات هنا كلما صُنِّف قسم جديد.
 */
class EnforceSectionPermission
{
    /**
     * بادئة الرابط (بعد admin/) => المجموعة المطلوبة.
     *
     * الأطول أولًا: admin/pos/installments تخص التحصيلات لا الفواتير،
     * والمطابقة تتوقف عند أول بادئة مطابقة.
     */
    private const PREFIX_MAP = [
        // ---- admin/admin/* : بادئة واحدة تخفي خمسة أقسام مختلفة ----
        'admin/salaries'          => 'salaries',
        'admin/developsellers'    => 'hr',
        'admin/TransactionSeller' => 'deposits',
        'admin/notifications'     => 'notifications',
        'admin/showmap'           => 'tracking',
        'admin/orders'            => 'invoices',
        'admin/list'              => 'admins',
        'admin/add'               => 'admins',
        'admin/store'             => 'admins',
        'admin/edit'              => 'admins',
        'admin/update'            => 'admins',
        'admin/delete'            => 'admins',

        // ---- نقطة البيع: الأخص أولًا ----
        'pos/installments'   => 'installments',
        'pos/refunds'        => 'invoices',
        'pos/orders'         => 'invoices',
        'pos/reservations'   => 'requests',
        'pos/sample'         => 'invoices',
        'pos/donations'      => 'invoices',
        'pos'                => 'invoices',

        'account-status'     => 'dashboard',
        'account'            => 'accounts',
        'dashboard'          => 'dashboard',
        'reports'            => 'reports',
        'productsunlike'     => 'reports',

        'product'            => 'products',
        'category'           => 'categories',
        'unit'               => 'units',
        'brand'              => 'brands',
        'coupon'             => 'coupons',
        'customer'           => 'customers',
        'supplier'           => 'suppliers',
        'seller'             => 'sellers',

        'stores'             => 'stores',
        'storagesseller'     => 'storages',
        'storages'           => 'storages',
        'storage'            => 'storages',
        'stock-returns'      => 'stock_returns',
        'stock'              => 'stock',
        'vehicle-stock'      => 'vehicle_stock',

        'documents'          => 'documents',
        'visitors'           => 'visits',
        'attendance'         => 'attendance',
        'shift'              => 'shifts',
        'salaries'           => 'salaries',
        'salary'             => 'salaries',
        'coursesellers'      => 'hr',
        'developsellers'     => 'hr',

        'roles'              => 'roles',
        'regions'            => 'regions',
        'region'             => 'regions',
        'tax'                => 'taxes',
        'taxes'              => 'taxes',
        'settings-password'  => 'settings',
        'settings'           => 'settings',
        'business-settings'  => 'settings',

        'factories'          => 'factories',
        'materials'          => 'materials',
        'purchases'          => 'purchases',
        'supply_orders'      => 'supply_orders',
        'production_orders'  => 'production',
        'ordernotification'  => 'requests',
    ];

    /**
     * مسارات تمر بلا فحص قسم.
     *
     * تسجيل الدخول والخروج والصفحة الرئيسية: حجبها يمنع الوصول للوحة
     * أصلًا، ولا يقف خلفها قسم يُحمى.
     */
    private const OPEN_PREFIXES = ['auth', ''];

    /**
     * مجموعة لا يملكها أحد، تُسند لأي مسار غير مصنَّف.
     *
     * لا يمرّ إلا السوبر أدمن، فيظهر المسار المنسي في أول اختبار بدل أن
     * يبقى مفتوحًا بصمت كما كان الحال قبل هذا العمل.
     */
    private const UNCLASSIFIED = '__unclassified__';

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::guard('admin')->user();

        if (!$user) {
            return redirect()->route('admin.auth.login');
        }

        // السوبر أدمن يمر دائمًا، وإلا قفل النظام على نفسه عند أول خطأ
        // في التصنيف.
        if ($user->is_super) {
            return $next($request);
        }

        $group = $this->groupFor($request);

        if ($group === null || $user->canAccessGroup($group)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول إلى هذا القسم',
            ], 403);
        }

        abort(403, 'ليس لديك صلاحية للوصول إلى هذا القسم');
    }

    /** المجموعة التي يقع تحتها هذا الرابط، أو null لما لا يُفحص. */
    private function groupFor(Request $request): ?string
    {
        $path = ltrim((string) $request->path(), '/');

        // admin وحدها هي الصفحة الترحيبية.
        if ($path === 'admin') {
            return null;
        }

        if (!str_starts_with($path, 'admin/')) {
            return null;
        }

        $rest = substr($path, strlen('admin/'));

        foreach (self::OPEN_PREFIXES as $open) {
            if ($open !== '' && ($rest === $open || str_starts_with($rest, $open . '/'))) {
                return null;
            }
        }

        // الأطول أولًا حتى لا تبتلع بادئة قصيرة مسارًا أخص منها:
        // admin/pos/installments تخص التحصيلات لا الفواتير.
        $prefixes = self::PREFIX_MAP;
        uksort($prefixes, fn ($a, $b) => strlen($b) <=> strlen($a));

        foreach ($prefixes as $prefix => $group) {
            if ($rest === $prefix || str_starts_with($rest, $prefix . '/')) {
                return $group;
            }
        }

        // مسار غير مصنَّف: يُمنع. كل مسارات اللوحة مصنَّفة الآن، فالوصول
        // إلى هنا يعني مسارًا جديدًا نسي صاحبه تصنيفه — والمنع يجعله
        // يظهر فورًا بدل أن يبقى مفتوحًا للجميع بصمت.
        return self::UNCLASSIFIED;
    }
}
