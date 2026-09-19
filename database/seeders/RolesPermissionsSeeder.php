<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * يزرع الصلاحيات ويحوّل الأعمدة القديمة على admins إلى أدوار.
 *
 * يعمل مرارًا دون ضرر: الصلاحيات تُحدَّث بالاسم، والأدوار المولَّدة تُعاد
 * مزامنتها، ولا يُمسّ دور أنشأه المستخدم بنفسه.
 */
class RolesPermissionsSeeder extends Seeder
{
    /**
     * أقسام لم يكن لها عمود على admins، فكان يفتحها كل من يدخل اللوحة.
     *
     * تُمنح للأدوار المرحَّلة حتى لا تنقلب الحماية الجديدة حرمانًا ممن
     * كان يستعملها بالأمس.
     */
    private const PREVIOUSLY_OPEN_GROUPS = [
        'documents', 'brands', 'taxes', 'shifts', 'factories',
        'materials', 'purchases', 'supply_orders', 'deposits',
        'stock_returns',
    ];

    public function run(): void
    {
        $this->syncPermissions();
        $this->createSystemRoles();
        $this->migrateLegacyColumns();
    }

    /** الصلاحيات من السجل، إضافةً وتحديثًا لا حذفًا. */
    private function syncPermissions(): void
    {
        foreach (Permissions::all() as $name => $label) {
            $group = str_contains($name, '.') ? explode('.', $name)[0] : 'system';

            Permission::updateOrCreate(
                ['name' => $name],
                ['group' => $group, 'label' => $label]
            );
        }

        $this->command?->info('Permissions: ' . Permission::count());
    }

    private function createSystemRoles(): void
    {
        $superAdmin = Role::updateOrCreate(
            ['name' => 'super-admin'],
            [
                'label'       => 'مدير عام',
                'description' => 'صلاحية كاملة على كل أقسام النظام.',
                'is_locked'   => true,
            ]
        );

        $superAdmin->permissions()->sync(
            Permission::where('name', Permissions::SUPER)->pluck('id')
        );

        // أدوار جاهزة تغطي الاستعمال الشائع، دون قفلها فيمكن تعديلها.
        $presets = [
            'accountant' => [
                'label'  => 'محاسب',
                'groups' => ['dashboard', 'invoices', 'accounts', 'installments', 'reports', 'customers'],
            ],
            'store-keeper' => [
                'label'  => 'أمين مخزن',
                'groups' => ['dashboard', 'stock', 'stock_limit', 'stores', 'storages', 'products', 'vehicle_stock', 'requests'],
            ],
            'sales-manager' => [
                'label'  => 'مدير مبيعات',
                'groups' => ['dashboard', 'sales', 'invoices', 'customers', 'sellers', 'visits', 'regions', 'tracking', 'reports', 'ratings'],
            ],
            'hr-manager' => [
                'label'  => 'مدير موارد بشرية',
                'groups' => ['dashboard', 'hr', 'attendance', 'salaries', 'documents', 'sellers'],
            ],
        ];

        foreach ($presets as $name => $preset) {
            $role = Role::firstOrCreate(
                ['name' => $name],
                ['label' => $preset['label'], 'is_locked' => false]
            );

            // الأدوار الجاهزة تُملأ عند الإنشاء فقط، فلا يُلغى تعديل
            // أجراه المستخدم عليها كلما أُعيد التشغيل.
            if ($role->wasRecentlyCreated) {
                $ids = collect();

                foreach ($preset['groups'] as $group) {
                    $ids = $ids->merge(
                        Permission::where('group', $group)->pluck('id')
                    );
                }

                $role->permissions()->sync($ids->unique());
            }
        }
    }

    /**
     * الأعمدة المنطقية القديمة إلى دور لكل مستخدم.
     *
     * بدون هذا يفقد كل أدمن وصوله لحظة تفعيل الفحص الجديد. الدور يحمل
     * اسم المستخدم فيسهل تمييزه وتعديله بعدها.
     */
    private function migrateLegacyColumns(): void
    {
        $columns = array_filter(
            Permissions::LEGACY_COLUMN_MAP,
            fn ($group, $column) => Schema::hasColumn('admins', $column),
            ARRAY_FILTER_USE_BOTH
        );

        $superRoleId = Role::where('name', 'super-admin')->value('id');
        $migrated = 0;

        foreach (DB::table('admins')->where('role', '!=', 'seller')->get() as $admin) {
            // من كان سوبر أدمن يبقى كذلك بالدور لا بالعمود وحده.
            if ($admin->is_super) {
                DB::table('role_admin')->updateOrInsert(
                    ['role_id' => $superRoleId, 'admin_id' => $admin->id]
                );
                $migrated++;
                continue;
            }

            $groups = [];

            foreach ($columns as $column => $group) {
                if (($admin->$column ?? 0) == 1) {
                    $groups[] = $group;
                }
            }

            if (empty($groups)) {
                continue;
            }

            // أقسام لم يكن لها أعمدة، فكانت مفتوحة لكل من يدخل اللوحة.
            // منحها للأدوار المرحَّلة يمنع أن يفقدها من كان يستعملها.
            $groups = array_merge($groups, self::PREVIOUSLY_OPEN_GROUPS);

            $name = 'legacy-admin-' . $admin->id;

            $role = Role::firstOrCreate(
                ['name' => $name],
                [
                    'label'       => 'صلاحيات ' . trim(($admin->f_name ?? '') . ' ' . ($admin->l_name ?? '')),
                    'description' => 'دور مُولَّد من الصلاحيات القديمة، يمكن تعديله أو استبداله.',
                    'is_locked'   => false,
                ]
            );

            // المزامنة عند الإنشاء فقط: إعادة التشغيل لا تلغي تعديلًا
            // أجراه المسؤول على الدور بعد الترحيل.
            if ($role->wasRecentlyCreated) {
                $ids = Permission::whereIn('group', $groups)->pluck('id');
                $role->permissions()->sync($ids);
            }

            DB::table('role_admin')->updateOrInsert(
                ['role_id' => $role->id, 'admin_id' => $admin->id]
            );

            $migrated++;
        }

        $this->command?->info('Admins given a role: ' . $migrated);
    }
}
