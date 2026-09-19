<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Role;
use App\Support\Permissions;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * أمر منح السوبر أدمن.
 *
 * هو الباب الوحيد حين لا يبقى في النظام من يفتح شاشة الأدوار، فسكوته
 * عن الخطأ أسوأ من فشله: كل مسار هنا يُختبر برمز خروجه.
 */
class GrantSuperAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_grants_the_super_role_by_email(): void
    {
        $this->seed(RolesPermissionsSeeder::class);
        $id = $this->admin('boss@test.test');

        $this->artisan('permissions:super', ['admin' => 'boss@test.test'])
            ->assertSuccessful();

        $admin = Admin::find($id);

        $this->assertTrue($admin->hasPermission(Permissions::SUPER));

        foreach (array_keys(Permissions::groups()) as $group) {
            $this->assertTrue($admin->canAccessGroup($group), "القسم {$group} محجوب");
        }
    }

    public function test_it_grants_by_id(): void
    {
        $this->seed(RolesPermissionsSeeder::class);
        $id = $this->admin('byid@test.test');

        $this->artisan('permissions:super', ['admin' => (string) $id])
            ->assertSuccessful();

        $this->assertTrue(Admin::find($id)->hasPermission(Permissions::SUPER));
    }

    /** بعض الحسابات تحمل لواحق على البريد، فالمطابقة التامة وحدها تُفشل الأمر. */
    public function test_it_matches_an_email_prefix(): void
    {
        $this->seed(RolesPermissionsSeeder::class);
        $id = $this->admin('admin2@gmail.com00');

        $this->artisan('permissions:super', ['admin' => 'admin2@gmail.com'])
            ->assertSuccessful();

        $this->assertTrue(Admin::find($id)->hasPermission(Permissions::SUPER));
    }

    /** بريدان يبدآن بالنص نفسه: الاختيار للمستخدم، والأمر يفشل. */
    public function test_an_ambiguous_email_grants_nobody(): void
    {
        $this->seed(RolesPermissionsSeeder::class);
        $a = $this->admin('same@test.test');
        $b = $this->admin('same@test.test.eg');

        $this->artisan('permissions:super', ['admin' => 'same@test.test'])
            ->assertFailed();

        $this->assertFalse(Admin::find($a)->hasPermission(Permissions::SUPER));
        $this->assertFalse(Admin::find($b)->hasPermission(Permissions::SUPER));
    }

    public function test_an_unknown_email_fails(): void
    {
        $this->seed(RolesPermissionsSeeder::class);

        $this->artisan('permissions:super', ['admin' => 'ghost@test.test'])
            ->assertFailed();
    }

    /** غياب الدور يعني أن البذرة لم تُشغَّل: يُقال ذلك، لا يُنشأ الدور صامتًا. */
    public function test_it_fails_when_the_role_is_missing(): void
    {
        $id = $this->admin('early@test.test');

        $this->artisan('permissions:super', ['admin' => 'early@test.test'])
            ->assertFailed();

        $this->assertFalse(Admin::find($id)->hasPermission(Permissions::SUPER));
    }

    /** السحب يترك بقية الأدوار، فيعود المستخدم إلى ما كان عليه لا إلى لا شيء. */
    public function test_revoking_keeps_the_other_roles(): void
    {
        $this->seed(RolesPermissionsSeeder::class);
        $id = $this->admin('keep@test.test');

        $other = Role::where('name', 'accountant')->first();
        Admin::find($id)->roles()->attach($other->id);

        $this->artisan('permissions:super', ['admin' => (string) $id])->assertSuccessful();
        $this->artisan('permissions:super', ['admin' => (string) $id, '--revoke' => true])
            ->assertSuccessful();

        $admin = Admin::find($id);

        $this->assertFalse($admin->hasPermission(Permissions::SUPER));
        $this->assertTrue($admin->roles->contains('name', 'accountant'));
    }

    /** إعادة المنح لا تكرّر الصف في جدول الربط. */
    public function test_granting_twice_is_harmless(): void
    {
        $this->seed(RolesPermissionsSeeder::class);
        $id = $this->admin('twice@test.test');
        $roleId = Role::where('name', 'super-admin')->value('id');

        $this->artisan('permissions:super', ['admin' => (string) $id])->assertSuccessful();
        $this->artisan('permissions:super', ['admin' => (string) $id])->assertSuccessful();

        $this->assertSame(1, DB::table('role_admin')
            ->where('admin_id', $id)->where('role_id', $roleId)->count());
    }

    public function test_list_runs_without_writing(): void
    {
        $this->seed(RolesPermissionsSeeder::class);
        $id = $this->admin('listed@test.test');

        $this->artisan('permissions:super', ['--list' => true])->assertSuccessful();

        $this->assertFalse(Admin::find($id)->hasPermission(Permissions::SUPER));
    }

    private function admin(string $email): int
    {
        $id = (int) (DB::table('admins')->max('id') ?? 0) + 1;

        DB::table('admins')->insert([
            'id' => $id, 'local_id' => 0,
            'f_name' => 'Test', 'l_name' => 'Admin',
            'email' => $email,
            'password' => bcrypt('secret'),
            'role' => 'admin', 'is_super' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $id;
    }
}
