<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\ApiTestCase;

/**
 * دليل الصلاحيات.
 *
 * صفحة قراءة تشرح ما يفتحه كل قسم ولماذا يُمنع حساب. الشكوى المتكرّرة
 * أن حسابًا «له صلاحية» يُمنع، والسبب في كل مرة أن القسم الذي تتبعه
 * الشاشة غير الذي يظنّه صاحبه.
 */
class PermissionsGuideTest extends ApiTestCase
{
    private const CLERK = 900600;

    private function clerkWith(?string $permission): Admin
    {
        DB::table('admins')->updateOrInsert(['id' => self::CLERK], [
            'local_id'   => self::CLERK,
            'f_name'     => 'Guide',
            'l_name'     => 'Reader',
            'email'      => 'guide.reader@example.test',
            'password'   => \Illuminate\Support\Facades\Hash::make('password'),
            'role'       => 'admin',
            'is_super'   => 0,
            'company_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $clerk = Admin::find(self::CLERK);

        if ($permission === null) {
            $clerk->roles()->sync([]);

            return $clerk;
        }

        [$group] = explode('.', $permission);

        $perm = Permission::firstOrCreate(
            ['name' => $permission],
            ['label' => $permission, 'group' => $group]
        );

        $role = Role::firstOrCreate(['name' => 'only-' . $permission], ['label' => $permission]);
        $role->permissions()->sync([$perm->id]);
        $clerk->roles()->sync([$role->id]);

        return $clerk;
    }

    /** @test */
    public function the_guide_opens_for_whoever_may_see_the_roles(): void
    {
        $this->actingAs($this->clerkWith('roles.view'), 'admin')
            ->get(route('admin.roles.guide'))
            ->assertOk()
            ->assertSee('دليل الصلاحيات');
    }

    /** @test */
    public function the_guide_is_closed_without_that_permission(): void
    {
        $this->actingAs($this->clerkWith(null), 'admin')
            ->get(route('admin.roles.guide'))
            ->assertForbidden();
    }

    /**
     * «guide» ليست معرِّف دورٍ.
     *
     * المسار يجاور roles/{role}، فلو سبقه في الترتيب لالتُقط «guide»
     * معرِّفًا وسقطت الصفحة بـ404 بدل أن تُعرض.
     *
     * @test
     */
    public function the_guide_path_is_not_swallowed_by_the_role_route(): void
    {
        $this->assertSame(
            'admin.roles.guide',
            app('router')->getRoutes()->match(
                \Illuminate\Http\Request::create('/admin/roles/guide', 'GET')
            )->getName()
        );
    }

    /** تذكر الصفحة المسارات التي يضلّ اسمُها عن قسمها. */
    public function test_it_names_the_misleading_sections(): void
    {
        $html = $this->actingAs($this->clerkWith('roles.view'), 'admin')
            ->get(route('admin.roles.guide'))
            ->getContent();

        // الرحلات: البادئة storages والقسم storages، وهي الشكوى الأخيرة.
        $this->assertStringContainsString('storages', $html);

        // وتقرير المنتجات الذي يتبع reports لا products.
        $this->assertStringContainsString('getreportProducts', $html);
    }
}
