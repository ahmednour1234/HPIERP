<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Database\Seeders\ApiTestingSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\ApiTestCase;

/**
 * نطاق قائمة المناديب.
 *
 * الشجرة تُبنى من admin_sellers، ومدير النظام ليس مُسنَدًا إلى أحد
 * فيها، فكانت القائمة تعود فارغةً ومعها كل أزرار الصفّ — يبدو زرّ
 * التعديل «لا يعمل» وهو غير معروض أصلًا.
 */
class SellerListScopeTest extends ApiTestCase
{
    private function rowCount(string $html): int
    {
        $i = strpos($html, 'set-rows');

        if ($i === false) {
            return 0;
        }

        return substr_count(substr($html, $i, strpos($html, '</tbody>', $i) - $i), 'sl-person');
    }

    /** الصفحة محمية بصلاحية قسمها، والاختبار عن النطاق لا الصلاحيات. */
    private function allowSellers(Admin $admin): void
    {
        $permission = \App\Models\Permission::firstOrCreate(
            ['name' => 'sellers.view'],
            ['label' => 'عرض المناديب', 'group' => 'sellers']
        );

        $role = \App\Models\Role::firstOrCreate(
            ['name' => 'sellers-only'],
            ['label' => 'sellers-only']
        );
        $role->permissions()->syncWithoutDetaching([$permission->id]);
        $admin->roles()->syncWithoutDetaching([$role->id]);
    }

    private function listAs(Admin $admin): string
    {
        $this->allowSellers($admin);

        return $this->actingAs($admin, 'admin')
            ->get(route('admin.seller.list'))
            ->assertOk()
            ->getContent();
    }

    /** @test */
    public function a_super_admin_sees_every_seller_and_their_buttons(): void
    {
        DB::table('admins')->where('id', ApiTestingSeeder::ADMIN_ID)->update(['is_super' => 1]);
        DB::table('admin_sellers')->where('admin_id', ApiTestingSeeder::ADMIN_ID)->delete();

        $html = $this->listAs(Admin::find(ApiTestingSeeder::ADMIN_ID));

        $this->assertGreaterThan(0, $this->rowCount($html), 'القائمة يجب ألّا تعود فارغة.');
        $this->assertStringContainsString('/seller/edit/', $html, 'زرّ التعديل يُعرض مع الصفّ.');
    }

    /** ومن ليس مديرًا للنظام يرى مُسنَديه وحدهم. */
    public function test_a_plain_admin_sees_only_their_assigned_sellers(): void
    {
        DB::table('admins')->where('id', ApiTestingSeeder::ADMIN_ID)->update(['is_super' => 0]);
        DB::table('admin_sellers')->where('admin_id', ApiTestingSeeder::ADMIN_ID)->delete();

        DB::table('admin_sellers')->insert([
            'admin_id'   => ApiTestingSeeder::ADMIN_ID,
            'seller_id'  => ApiTestingSeeder::SELLER_ID,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(
            1,
            $this->rowCount($this->listAs(Admin::find(ApiTestingSeeder::ADMIN_ID))),
            'المُسنَد إليه وحده، لا كل المناديب.'
        );
    }

    /** وبلا إسناد ولا is_super: لا أحد. */
    public function test_an_admin_with_no_sellers_sees_none(): void
    {
        DB::table('admins')->where('id', ApiTestingSeeder::ADMIN_ID)->update(['is_super' => 0]);
        DB::table('admin_sellers')->where('admin_id', ApiTestingSeeder::ADMIN_ID)->delete();

        $this->assertSame(0, $this->rowCount($this->listAs(Admin::find(ApiTestingSeeder::ADMIN_ID))));
    }

    /** صفحة التعديل نفسها تفتح لمدير النظام. */
    public function test_the_edit_page_opens_for_a_super_admin(): void
    {
        DB::table('admins')->where('id', ApiTestingSeeder::ADMIN_ID)->update(['is_super' => 1]);

        $this->actingAs(Admin::find(ApiTestingSeeder::ADMIN_ID), 'admin')
            ->get(route('admin.seller.edit', [ApiTestingSeeder::SELLER_ID]))
            ->assertOk();
    }
}
