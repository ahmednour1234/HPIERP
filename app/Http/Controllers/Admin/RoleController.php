<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Permission;
use App\Models\Role;
use App\Support\Permissions;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * إدارة الأدوار والصلاحيات.
 *
 * حلّت محل 33 عمودًا منطقيًا على جدول admins، فإضافة صلاحية كانت تعني
 * هجرة جديدة ولا يمكن تجميعها في دور يُعاد استعماله.
 */
class RoleController extends Controller
{
    public function index(): View|Factory|Application
    {
        $roles = Role::withCount(['permissions', 'admins'])
            ->with('permissions:id,group')
            ->orderBy('id')
            ->get();

        $groups = Permissions::groups();

        // عدد الصلاحيات وحده لا يقول شيئًا: 23 صلاحية قد تكون قسمًا
        // واحدًا أو عشرة. الأقسام المغطّاة هي ما يُقرأ من نظرة.
        $roles->each(function (Role $role) use ($groups) {
            $covered = $role->permissions->pluck('group')->unique();

            $role->section_labels = $covered
                ->map(fn ($g) => $groups[$g]['label'] ?? $g)
                ->sort()
                ->values();
        });

        return view('admin-views.roles.index', [
            'roles'       => $roles,
            'total_perms' => count(Permissions::all()),
            'total_groups' => count($groups),
        ]);
    }

    public function create(): View|Factory|Application
    {
        return view('admin-views.roles.form', [
            'role'        => new Role(),
            'groups'      => Permissions::groups(),
            'selected'    => [],
            'total_perms' => $this->assignablePermissionCount(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $role = Role::create([
            'name'        => $data['name'],
            'label'       => $data['label'],
            'description' => $data['description'] ?? null,
        ]);

        $this->syncPermissions($role, $request);

        Toastr::success('تم إنشاء الدور.');

        return redirect()->route('admin.roles.index');
    }

    public function edit(Role $role): View|Factory|Application
    {
        return view('admin-views.roles.form', [
            'role'        => $role,
            'groups'      => Permissions::groups(),
            'selected'    => $role->permissions()->pluck('name')->all(),
            'total_perms' => $this->assignablePermissionCount(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $this->validated($request, $role);

        // الاسم مفتاح الدور في الكود (super-admin)، فتغييره على دور
        // مقفل يقطع ما يعتمد عليه.
        $role->update([
            'name'        => $role->is_locked ? $role->name : $data['name'],
            'label'       => $data['label'],
            'description' => $data['description'] ?? null,
        ]);

        $this->syncPermissions($role, $request);

        Toastr::success('تم تحديث الدور.');

        return redirect()->route('admin.roles.index');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_locked) {
            Toastr::error('لا يمكن حذف دور نظام.');
            return redirect()->route('admin.roles.index');
        }

        // الحذف مع وجود مستخدمين يتركهم بلا صلاحيات فجأة.
        if ($role->admins()->exists()) {
            Toastr::error('الدور مسند إلى مستخدمين؛ انقلهم إلى دور آخر أولًا.');
            return redirect()->route('admin.roles.index');
        }

        $role->permissions()->detach();
        $role->delete();

        Toastr::success('تم حذف الدور.');

        return redirect()->route('admin.roles.index');
    }

    /**
     * عدد الصلاحيات التي تظهر فعلًا في شبكة النموذج.
     *
     * system.super ليست مربّعًا فيها — يمنحها دور النظام وحده — فعدّها
     * يجعل العدّاد يقيس إلى سقف لا يُبلَغ أبدًا.
     */
    private function assignablePermissionCount(): int
    {
        return array_sum(array_map(
            fn (array $meta) => count($meta['actions']),
            Permissions::groups()
        ));
    }

    /** شاشة إسناد الأدوار للمستخدمين. */
    public function assign(): View|Factory|Application
    {
        $admins = Admin::where('role', '!=', 'seller')
            ->with('roles:id,label')
            ->orderBy('f_name')
            ->get(['id', 'f_name', 'l_name', 'email', 'is_super']);

        return view('admin-views.roles.assign', [
            'admins' => $admins,
            'roles'  => Role::orderBy('label')->get(),
        ]);
    }

    public function storeAssignment(Request $request, Admin $admin): RedirectResponse
    {
        $request->validate([
            'roles'   => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $admin->roles()->sync((array) $request->input('roles', []));

        Toastr::success('تم تحديث أدوار المستخدم.');

        return redirect()->route('admin.roles.assign');
    }

    private function validated(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:80', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('roles', 'name')->ignore($role?->id),
            ],
            'label'         => 'required|string|max:160',
            'description'   => 'nullable|string|max:1000',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string',
        ], [
            'name.regex'  => 'المعرّف بحروف إنجليزية صغيرة وأرقام وشرطات فقط.',
            'name.unique' => 'هذا المعرّف مستخدم بالفعل.',
        ]);
    }

    private function syncPermissions(Role $role, Request $request): void
    {
        // دور النظام يحتفظ بصلاحيته الكاملة مهما أُرسل من الشاشة.
        if ($role->is_locked) {
            return;
        }

        $names = (array) $request->input('permissions', []);

        // التحقق من السجل لا من الجدول: اسم غير معروف يُهمَل بدل أن
        // يُحفظ صفًّا لا معنى له.
        $known = array_keys(Permissions::all());
        $names = array_values(array_intersect($names, $known));

        $role->permissions()->sync(Permission::whereIn('name', $names)->pluck('id'));
    }
}
