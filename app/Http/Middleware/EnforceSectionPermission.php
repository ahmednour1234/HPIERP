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
        'pos/installments'   => 'installments',
        'pos/refunds'        => 'invoices',
        'pos/orders'         => 'invoices',
        'pos'                => 'invoices',
        'account'            => 'accounts',
        'reports'            => 'reports',
        'product'            => 'products',
        'category'           => 'categories',
        'unit'               => 'units',
        'coupon'             => 'coupons',
        'customer'           => 'customers',
        'supplier'           => 'suppliers',
        'seller'             => 'sellers',
        'stores'             => 'stores',
        'storage'            => 'storages',
        'stock'              => 'stock',
        'documents'          => 'documents',
        'visitors'           => 'visits',
        'attendance'         => 'attendance',
        'salaries'           => 'salaries',
        'salary'             => 'salaries',
        'roles'              => 'roles',
        'admin/admin'        => 'admins',
        'region'             => 'regions',
        'taxes'              => 'settings',
        'business-settings'  => 'settings',
        'coursesellers'      => 'hr',
        'developsellers'     => 'hr',
    ];

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

    /** المجموعة التي يقع تحتها هذا الرابط، أو null إن لم تُصنَّف. */
    private function groupFor(Request $request): ?string
    {
        $path = ltrim((string) $request->path(), '/');

        if (!str_starts_with($path, 'admin/')) {
            return null;
        }

        $rest = substr($path, strlen('admin/'));

        // الأطول أولًا حتى لا تبتلع بادئة قصيرة مسارًا أخص منها.
        $prefixes = self::PREFIX_MAP;
        uksort($prefixes, fn ($a, $b) => strlen($b) <=> strlen($a));

        foreach ($prefixes as $prefix => $group) {
            if ($rest === $prefix || str_starts_with($rest, $prefix . '/')) {
                return $group;
            }
        }

        return null;
    }
}
