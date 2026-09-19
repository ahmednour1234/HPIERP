<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Permission;
use App\Models\Role;
use App\Support\Permissions;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * بذرة الأدوار والصلاحيات.
 *
 * تُشغَّل على قواعد قائمة فيها أدوار مُعدَّلة بالفعل، فلا يكفي أن تعمل
 * مرة: يجب أن تُعاد دون أن تكرّر صفًّا أو تمحو تعديلًا.
 */
class RolesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_every_registered_permission(): void
    {
        $this->seed(RolesPermissionsSeeder::class);

        $this->assertSame(count(Permissions::all()), Permission::count());

        // كل صلاحية في السجل موجودة بالاسم نفسه.
        foreach (array_keys(Permissions::all()) as $name) {
            $this->assertDatabaseHas('permissions', ['name' => $name]);
        }
    }

    public function test_it_creates_the_locked_super_admin_role(): void
    {
        $this->seed(RolesPermissionsSeeder::class);

        $role = Role::where('name', 'super-admin')->first();

        $this->assertNotNull($role);
        $this->assertTrue($role->is_locked, 'دور النظام يجب أن يكون مقفلًا');
        $this->assertTrue($role->permissions()->where('name', Permissions::SUPER)->exists());
    }

    public function test_it_creates_the_preset_roles(): void
    {
        $this->seed(RolesPermissionsSeeder::class);

        foreach (['accountant', 'store-keeper', 'sales-manager', 'hr-manager'] as $name) {
            $role = Role::where('name', $name)->first();

            $this->assertNotNull($role, "الدور {$name} لم يُنشأ");
            $this->assertFalse($role->is_locked, 'الأدوار الجاهزة تُعدَّل');
            $this->assertGreaterThan(0, $role->permissions()->count());
        }
    }

    /** إعادة التشغيل لا تكرّر صفًّا. */
    public function test_running_it_twice_changes_nothing(): void
    {
        $this->seed(RolesPermissionsSeeder::class);

        $before = $this->counts();

        $this->seed(RolesPermissionsSeeder::class);
        $this->seed(RolesPermissionsSeeder::class);

        $this->assertSame($before, $this->counts());
    }

    /** تعديل المسؤول على دور جاهز يبقى بعد إعادة التشغيل. */
    public function test_it_does_not_overwrite_an_edited_role(): void
    {
        $this->seed(RolesPermissionsSeeder::class);

        $role = Role::where('name', 'accountant')->first();
        $role->permissions()->sync(Permission::where('name', 'accounts.view')->pluck('id'));

        $this->seed(RolesPermissionsSeeder::class);

        $this->assertSame(1, $role->fresh()->permissions()->count(),
            'البذرة محت تعديلًا يدويًا على الدور');
    }

    /**
     * الترحيل: الأدمن القديم يخرج بدور يحمل ما كانت أعمدته تمنحه.
     *
     * بدون هذا يفقد كل أدمن وصوله لحظة تفعيل الفحص الجديد.
     */
    public function test_it_gives_a_legacy_admin_a_role_matching_their_columns(): void
    {
        $id = $this->legacyAdmin(['accounts' => 1, 'reports' => 1]);

        $this->seed(RolesPermissionsSeeder::class);

        $admin = Admin::find($id);

        $this->assertTrue($admin->canAccessGroup('accounts'));
        $this->assertTrue($admin->canAccessGroup('reports'));

        // ما لم يكن في أعمدته لا يُمنح.
        $this->assertFalse($admin->canAccessGroup('products'));
    }

    /** أقسام كانت بلا أعمدة تُمنح، وإلا انقلبت الحماية حرمانًا. */
    public function test_a_legacy_admin_keeps_the_previously_open_sections(): void
    {
        $id = $this->legacyAdmin(['accounts' => 1]);

        $this->seed(RolesPermissionsSeeder::class);

        $admin = Admin::find($id);

        foreach (['documents', 'brands', 'taxes', 'factories', 'purchases'] as $group) {
            $this->assertTrue($admin->canAccessGroup($group),
                "القسم {$group} كان مفتوحًا للجميع وصار محجوبًا");
        }
    }

    /** من يدير المستخدمين يدير الأدوار، وإلا بقي النظام بلا موزِّع صلاحيات. */
    public function test_a_legacy_user_manager_can_reach_the_roles_screen(): void
    {
        $id = $this->legacyAdmin(['admin' => 1]);

        $this->seed(RolesPermissionsSeeder::class);

        $this->assertTrue(Admin::find($id)->canAccessGroup('roles'));
    }

    /** ومن لا يدير المستخدمين لا يُمنحها ضمنًا. */
    public function test_a_legacy_admin_without_users_has_no_roles_access(): void
    {
        $id = $this->legacyAdmin(['accounts' => 1]);

        $this->seed(RolesPermissionsSeeder::class);

        $this->assertFalse(Admin::find($id)->canAccessGroup('roles'));
    }

    public function test_a_super_admin_column_maps_to_the_super_role(): void
    {
        $id = $this->legacyAdmin(['is_super' => 1]);

        $this->seed(RolesPermissionsSeeder::class);

        $this->assertTrue(
            Admin::find($id)->roles()->where('name', 'super-admin')->exists()
        );
    }

    /** المندوبون ليسوا مستخدمي لوحة، فلا تُنشأ لهم أدوار. */
    public function test_sellers_are_skipped(): void
    {
        $id = $this->legacyAdmin(['role' => 'seller', 'accounts' => 1]);

        $this->seed(RolesPermissionsSeeder::class);

        $this->assertSame(0, Admin::find($id)->roles()->count());
    }

    /** @return array<string, int> */
    private function counts(): array
    {
        return [
            'permissions'     => DB::table('permissions')->count(),
            'roles'           => DB::table('roles')->count(),
            'permission_role' => DB::table('permission_role')->count(),
            'role_admin'      => DB::table('role_admin')->count(),
        ];
    }

    private function legacyAdmin(array $columns): int
    {
        $id = (int) (DB::table('admins')->max('id') ?? 0) + 1;

        DB::table('admins')->insert(array_merge([
            'id' => $id, 'local_id' => 0,
            'f_name' => 'Legacy', 'l_name' => 'Admin',
            'email' => 'legacy' . $id . '@test.test',
            'password' => bcrypt('secret'),
            'role' => 'admin', 'is_super' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ], $columns));

        return $id;
    }
}
