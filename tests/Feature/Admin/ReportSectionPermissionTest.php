<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\ApiTestCase;

/**
 * تقارير تحت بادئة product تُصنَّف تقاريرَ لا منتجات.
 *
 * «كشف المنتجات المباعة» مساره admin/product/getreportProducts، وكانت
 * البادئة product تضعه في قسم المنتجات، فحسابٌ أُعطي صلاحية التقارير
 * وحدها يُرفض عند فتحه ولا يُفهم السبب إلا بمنحه صلاحية المنتجات كلها.
 */
class ReportSectionPermissionTest extends ApiTestCase
{
    private const CLERK = 900500;

    /** حساب يملك صلاحيةً واحدة بعينها. */
    private function clerkWith(string $permission): Admin
    {
        DB::table('admins')->updateOrInsert(['id' => self::CLERK], [
            'local_id'   => self::CLERK,
            'f_name'     => 'Reports',
            'l_name'     => 'Only',
            'email'      => 'reports.only@example.test',
            'password'   => \Illuminate\Support\Facades\Hash::make('password'),
            'role'       => 'admin',
            'is_super'   => 0,
            'company_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        [$group] = explode('.', $permission);

        $perm = Permission::firstOrCreate(
            ['name' => $permission],
            ['label' => $permission, 'group' => $group]
        );

        $role = Role::firstOrCreate(['name' => 'only-' . $permission], ['label' => $permission]);
        $role->permissions()->sync([$perm->id]);

        $clerk = Admin::find(self::CLERK);
        $clerk->roles()->sync([$role->id]);

        return $clerk;
    }

    /** @test */
    public function the_sold_products_report_opens_with_the_reports_permission(): void
    {
        $this->actingAs($this->clerkWith('reports.view'), 'admin')
            ->get(route('admin.product.getreportProducts'))
            ->assertOk();
    }

    /** @test */
    public function the_expiry_report_opens_with_the_reports_permission(): void
    {
        $this->actingAs($this->clerkWith('reports.view'), 'admin')
            ->get('/admin/product/listreportexpire')
            ->assertOk();
    }

    /**
     * ولا تُفتح معها بقية شاشات المنتجات.
     *
     * بغير هذا التوكيد قد يُصنَّف المسار كلّه تقاريرَ فتُفتح إدارة
     * المنتجات لمن يملك صلاحية التقارير وحدها.
     */
    public function test_the_reports_permission_does_not_open_product_management(): void
    {
        $this->actingAs($this->clerkWith('reports.view'), 'admin')
            ->get('/admin/product/list')
            ->assertForbidden();
    }

    /** ومن يملك صلاحية المنتجات لا يفتح التقرير بها. */
    public function test_the_products_permission_does_not_open_the_report(): void
    {
        $this->actingAs($this->clerkWith('products.view'), 'admin')
            ->get(route('admin.product.getreportProducts'))
            ->assertForbidden();
    }
}
