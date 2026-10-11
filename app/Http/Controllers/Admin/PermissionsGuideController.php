<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Contracts\View\View;

/**
 * دليل الصلاحيات: ما الذي يفتحه كل قسم، ولماذا يُمنع مستخدم.
 *
 * الشكوى المتكرّرة أن حسابًا «له صلاحية» يُمنع من شاشة، والسبب في كل
 * مرة أن القسم الذي تتبعه الشاشة غير الذي يظنّه صاحب الحساب. الصفحة
 * تعرض الأقسام وأدوار النظام وما يملكه كل حساب فعلًا، فيُقرأ السبب
 * بدل تخمينه.
 */
class PermissionsGuideController extends Controller
{
    public function index(): View
    {
        $roles = Role::withCount('permissions')
            ->with('permissions:id,name,group')
            ->orderBy('id')
            ->get();

        // الحسابات وما تملكه: العمود القديم is_super يتجاوز الأدوار،
        // فيُعرض صراحةً كي لا يُبحث عن سبب الوصول في الأدوار وحدها.
        $admins = Admin::where('role', 'admin')
            ->with('roles:id,name,label')
            ->orderBy('id')
            ->get(['id', 'f_name', 'l_name', 'email', 'is_super']);

        return view('admin-views.settings.permissions-guide', [
            'groups' => Permissions::groups(),
            'roles'  => $roles,
            'admins' => $admins,
        ]);
    }
}
