<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Database\Seeders\ApiTestingSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\ApiTestCase;

/**
 * فلاتر تقرير المنتجات المباعة.
 *
 * قائمة المناديب كانت من admin_sellers وحدها، ومدير النظام ليس مُسنَدًا
 * إلى أحد فيها، فتخرج فارغةً ولا يمكنه الترشيح بمندوب أصلًا.
 */
class ProductReportFiltersTest extends ApiTestCase
{
    /** عدد الخيارات داخل قائمةٍ بعينها. */
    private function countOptions(string $html, string $name): int
    {
        $i = strpos($html, 'name="' . $name . '"');

        if ($i === false) {
            return -1;
        }

        return substr_count(substr($html, $i, strpos($html, '</select>', $i) - $i), '<option');
    }

    private function page(): string
    {
        return $this->get(route('admin.product.getreportProducts'))->assertOk()->getContent();
    }

    /** الصفحة تقريرٌ فتحتاج reports.view، والاختبار عن الفلاتر لا عن الصلاحيات. */
    private function allowReports(Admin $admin): void
    {
        $permission = \App\Models\Permission::firstOrCreate(
            ['name' => 'reports.view'],
            ['label' => 'عرض التقارير', 'group' => 'reports']
        );

        $role = \App\Models\Role::firstOrCreate(
            ['name' => 'reports-only'],
            ['label' => 'reports-only']
        );
        $role->permissions()->syncWithoutDetaching([$permission->id]);
        $admin->roles()->syncWithoutDetaching([$role->id]);
    }

    /** @test */
    public function a_super_admin_can_filter_by_any_seller(): void
    {
        DB::table('admins')->where('id', ApiTestingSeeder::ADMIN_ID)->update(['is_super' => 1]);
        DB::table('admin_sellers')->where('admin_id', ApiTestingSeeder::ADMIN_ID)->delete();

        $this->actingAs(Admin::find(ApiTestingSeeder::ADMIN_ID), 'admin');

        $this->assertGreaterThan(
            0,
            $this->countOptions($this->page(), 'seller_id[]'),
            'قائمة المناديب يجب ألّا تخرج فارغة لمدير النظام.'
        );
    }

    /** ومن ليس مديرًا للنظام يرى مناديبه وحدهم. */
    public function test_a_plain_admin_sees_only_their_own_sellers(): void
    {
        DB::table('admins')->where('id', ApiTestingSeeder::ADMIN_ID)->update(['is_super' => 0]);
        DB::table('admin_sellers')->where('admin_id', ApiTestingSeeder::ADMIN_ID)->delete();

        DB::table('admin_sellers')->insert([
            'admin_id'   => ApiTestingSeeder::ADMIN_ID,
            'seller_id'  => ApiTestingSeeder::SELLER_ID,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = Admin::find(ApiTestingSeeder::ADMIN_ID);
        $this->allowReports($admin);
        $this->actingAs($admin, 'admin');

        $this->assertSame(
            1,
            $this->countOptions($this->page(), 'seller_id[]'),
            'المُسنَد إليه وحده يظهر.'
        );
    }

    /** قائمة المنتجات غير مقيدة بمندوب: كل المنتجات تُعرض. */
    public function test_every_product_is_offered(): void
    {
        DB::table('admins')->where('id', ApiTestingSeeder::ADMIN_ID)->update(['is_super' => 1]);
        $this->actingAs(Admin::find(ApiTestingSeeder::ADMIN_ID), 'admin');

        $this->assertSame(
            DB::table('products')->count(),
            $this->countOptions($this->page(), 'product_code[]')
        );
    }

    /**
     * بطاقة الفلاتر لا تقصّ قوائمها.
     *
     * overflow:hidden عليها كان يقطع ما ينسدل خارج حدودها، فتبدو
     * قوائم الصف الثاني كأنها لا تفتح.
     */
    public function test_the_filter_card_does_not_clip_its_dropdowns(): void
    {
        $admin = Admin::find(ApiTestingSeeder::ADMIN_ID);
        DB::table('admins')->where('id', $admin->id)->update(['is_super' => 1]);
        $this->actingAs(Admin::find($admin->id), 'admin');

        $this->assertStringContainsString(
            '.product-report-filter { overflow: visible; }',
            $this->page()
        );
    }
}
