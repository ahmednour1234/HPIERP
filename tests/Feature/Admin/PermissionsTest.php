<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Permission;
use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * الأدوار والصلاحيات.
 *
 * كانت الصلاحيات أعمدة منطقية على admins، و26 مسارًا فقط من 327 محميًا،
 * فأي أدمن يفتح أقسامًا لا يملكها بكتابة الرابط. هذه الاختبارات تثبّت أن
 * الفحص صار على كل المسارات وأن الدور يمنح ما فيه لا أكثر.
 */
class PermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesPermissionsSeeder::class);
    }

    public function test_every_registered_permission_is_seeded(): void
    {
        $this->assertSame(
            count(Permissions::all()),
            Permission::count(),
            'عدد الصلاحيات في الجدول لا يطابق السجل'
        );
    }

    public function test_an_admin_without_roles_has_no_permissions(): void
    {
        $admin = $this->admin();

        $this->assertFalse($admin->hasPermission('accounts.view'));
        $this->assertFalse($admin->canAccessGroup('accounts'));
    }

    public function test_a_role_grants_exactly_its_permissions(): void
    {
        $admin = $this->admin();
        $admin->roles()->sync([$this->role(['accounts.view', 'reports.view'])->id]);

        $admin = Admin::find($admin->id);

        $this->assertTrue($admin->hasPermission('accounts.view'));
        $this->assertTrue($admin->hasPermission('reports.view'));

        // ما لم يُمنح لا يُستنتج: عرض الحسابات لا يعني حذفها.
        $this->assertFalse($admin->hasPermission('accounts.delete'));
        $this->assertFalse($admin->hasPermission('products.view'));
    }

    /** رؤية القسم تكفيها أي صلاحية داخله، وإلا حُجب عمّن يملك الإضافة وحدها. */
    public function test_group_access_follows_any_permission_in_it(): void
    {
        $admin = $this->admin();
        $admin->roles()->sync([$this->role(['accounts.create'])->id]);

        $admin = Admin::find($admin->id);

        $this->assertTrue($admin->canAccessGroup('accounts'));
        $this->assertFalse($admin->canAccessGroup('products'));
    }

    public function test_permissions_accumulate_across_roles(): void
    {
        $admin = $this->admin();
        $admin->roles()->sync([
            $this->role(['accounts.view'], 'role-a')->id,
            $this->role(['products.view'], 'role-b')->id,
        ]);

        $admin = Admin::find($admin->id);

        $this->assertTrue($admin->hasPermission('accounts.view'));
        $this->assertTrue($admin->hasPermission('products.view'));
    }

    public function test_the_super_permission_opens_everything(): void
    {
        $admin = $this->admin();
        $admin->roles()->sync([Role::where('name', 'super-admin')->first()->id]);

        $admin = Admin::find($admin->id);

        $this->assertTrue($admin->hasPermission('accounts.delete'));
        $this->assertTrue($admin->hasPermission('roles.create'));
        $this->assertTrue($admin->canAccessGroup('production'));
    }

    /** عمود is_super في البيانات القائمة يظل يفتح كل شيء. */
    public function test_the_is_super_column_still_opens_everything(): void
    {
        $admin = $this->admin(['is_super' => 1]);

        $this->assertTrue($admin->hasPermission('anything.at.all'));
        $this->assertTrue($admin->canAccessGroup('accounts'));
    }

    /**
     * الثغرة التي دفعت لهذا العمل: كانت الأقسام تُفتح بكتابة الرابط لأن
     * القائمة الجانبية وحدها هي ما يُخفيها.
     */
    public function test_a_section_url_is_refused_without_its_permission(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->get('/admin/account/list')
            ->assertForbidden();

        $this->actingAs($admin, 'admin')
            ->get('/admin/reports/monthly-sales')
            ->assertForbidden();
    }

    public function test_the_same_url_opens_once_the_permission_is_granted(): void
    {
        $admin = $this->admin();
        $admin->roles()->sync([$this->role(['accounts.view'])->id]);

        $this->actingAs(Admin::find($admin->id), 'admin')
            ->get('/admin/account/list')
            ->assertOk();
    }

    /** الحارس يستنتج القسم من البادئة، والأخص يسبق الأعم. */
    public function test_a_longer_prefix_wins_over_a_shorter_one(): void
    {
        $admin = $this->admin();
        $admin->roles()->sync([$this->role(['installments.view'])->id]);
        $admin = Admin::find($admin->id);

        // التحصيلات تحت admin/pos/ لكنها ليست فواتير.
        $this->assertTrue($admin->canAccessGroup('installments'));
        $this->assertFalse($admin->canAccessGroup('invoices'));
    }

    public function test_managing_roles_needs_the_roles_permission(): void
    {
        $admin = $this->admin();
        $admin->roles()->sync([$this->role(['accounts.view'])->id]);

        $this->actingAs(Admin::find($admin->id), 'admin')
            ->get('/admin/roles')
            ->assertForbidden();
    }

    public function test_a_locked_role_keeps_its_permissions_when_edited(): void
    {
        $super = $this->admin(['is_super' => 1]);
        $role  = Role::where('name', 'super-admin')->first();

        $this->actingAs($super, 'admin')->put("/admin/roles/{$role->id}", [
            'name'        => 'renamed',
            'label'       => 'محاولة تعديل',
            'permissions' => [],
        ]);

        $role->refresh();

        // الاسم مفتاحه في الكود، والصلاحية الكاملة سبب وجوده.
        $this->assertSame('super-admin', $role->name);
        $this->assertTrue($role->permissions()->where('name', Permissions::SUPER)->exists());
    }

    /**
     * كل مسارات اللوحة مصنَّفة، فمسار جديد بلا تصنيف يُمنع لا يُفتح.
     *
     * الافتراض المعاكس هو ما ترك 300 مسار مكشوفة من قبل.
     */
    public function test_an_unclassified_path_is_refused(): void
    {
        $admin = $this->admin();
        $admin->roles()->sync([$this->role(['accounts.view'])->id]);

        $middleware = new \App\Http\Middleware\EnforceSectionPermission();
        $method = new \ReflectionMethod($middleware, 'groupFor');
        $method->setAccessible(true);

        $group = $method->invoke(
            $middleware,
            \Illuminate\Http\Request::create('/admin/some-new-screen', 'GET')
        );

        $this->assertSame('__unclassified__', $group);
        $this->assertFalse(Admin::find($admin->id)->canAccessGroup($group));
    }

    /** تسجيل الدخول والصفحة الرئيسية لا تُحرسان، وإلا تعذّر الدخول. */
    public function test_auth_and_welcome_paths_stay_open(): void
    {
        $middleware = new \App\Http\Middleware\EnforceSectionPermission();
        $method = new \ReflectionMethod($middleware, 'groupFor');
        $method->setAccessible(true);

        foreach (['/admin', '/admin/auth/login'] as $path) {
            $this->assertNull(
                $method->invoke($middleware, \Illuminate\Http\Request::create($path, 'GET')),
                $path . ' should not be guarded'
            );
        }
    }

    public function test_a_role_in_use_is_not_deleted(): void
    {
        $super = $this->admin(['is_super' => 1]);
        $role  = $this->role(['accounts.view']);

        $holder = $this->admin(['email' => 'holder@test.test']);
        $holder->roles()->sync([$role->id]);

        $this->actingAs($super, 'admin')->delete("/admin/roles/{$role->id}");

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    /** صفحة الأدوار تعرض الأقسام التي يفتحها كل دور، لا عددًا مجرّدًا. */
    public function test_the_roles_page_lists_the_sections_each_role_covers(): void
    {
        $admin = $this->admin();
        $admin->roles()->sync([$this->role([Permissions::SUPER])->id]);

        $covering = $this->role(['accounts.view', 'reports.view'], 'covering');
        $covering->update(['label' => 'دور التغطية']);

        $this->actingAs(Admin::find($admin->id), 'admin')
            ->get('/admin/roles')
            ->assertOk()
            ->assertSee('دور التغطية')
            ->assertSee(Permissions::groups()['accounts']['label'])
            ->assertSee(Permissions::groups()['reports']['label']);
    }

    /** دور بلا صلاحيات لا يكسر الصفحة، وهي حالة تنشأ بعد إفراغ دور. */
    public function test_the_roles_page_renders_a_role_with_no_permissions(): void
    {
        $admin = $this->admin();
        $admin->roles()->sync([$this->role([Permissions::SUPER])->id]);

        $empty = $this->role([], 'empty-role');
        $empty->update(['label' => 'دور فارغ']);

        $this->actingAs(Admin::find($admin->id), 'admin')
            ->get('/admin/roles')
            ->assertOk()
            ->assertSee('دور فارغ')
            ->assertSee('بلا صلاحيات');
    }

    private function admin(array $attributes = []): Admin
    {
        $id = (int) (DB::table('admins')->max('id') ?? 0) + 1;

        DB::table('admins')->insert(array_merge([
            'id' => $id, 'local_id' => 0,
            'f_name' => 'Perm', 'l_name' => 'Tester',
            'email' => 'perm' . $id . '@test.test',
            'password' => bcrypt('secret'),
            'role' => 'admin', 'is_super' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ], $attributes));

        return Admin::find($id);
    }

    /** @param array<int, string> $permissions */
    private function role(array $permissions, string $name = 'test-role'): Role
    {
        $role = Role::create(['name' => $name, 'label' => $name]);

        $role->permissions()->sync(
            Permission::whereIn('name', $permissions)->pluck('id')
        );

        return $role;
    }
}
