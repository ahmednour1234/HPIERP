<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

/**
 * فحص الصلاحية من القوالب.
 *
 * دالة ساكنة لا Blade::if: الأخيرة كانت تسجّل @end… دون أن يطبّقها
 * المُصرِّف فيبقى الوسم نصًّا ويكسر القالب. توجيهات @haspermission
 * و@cangroup تنادي هذه الدوال.
 *
 * كلها تمرّ على حارس admin صراحةً: الحارس الافتراضي web لا أحد مسجّل
 * عليه في اللوحة، فـ @can المدمجة كانت ترجع false دائمًا.
 *
 * ملاحظة عند الاستعمال: ضع @endhaspermission وأخواتها في سطر مستقل.
 * Blade لا يصرّف التوجيه إن تلاه حرف مباشرةً مثل ']'، فيبقى نصًّا ظاهرًا.
 */
class Perm
{
    public static function has(string $permission): bool
    {
        $user = Auth::guard('admin')->user();

        return $user && $user->hasPermission($permission);
    }

    /** @param array<int, string> $permissions */
    public static function hasAny(array $permissions): bool
    {
        $user = Auth::guard('admin')->user();

        return $user && $user->hasAnyPermission($permissions);
    }

    /** أي صلاحية داخل القسم — ما يقرر ظهوره في القائمة الجانبية. */
    public static function group(string $group): bool
    {
        $user = Auth::guard('admin')->user();

        return $user && $user->canAccessGroup($group);
    }
}
