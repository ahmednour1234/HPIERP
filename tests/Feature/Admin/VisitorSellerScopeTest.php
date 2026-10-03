<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Database\Seeders\ApiTestingSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\ApiTestCase;

/**
 * فلتر المناديب في الزيارات المنفَّذة يقتصر على المُسنَدين.
 *
 * كان الشرط `in_array($admin->role, ['super_admin', 'admin'])`، و'admin'
 * قيمةُ العمود role لكل موظف إداري لا للمشرف العام وحده، فكان مديرُ
 * منطقةٍ أُسند إليه خمسة مناديب يرى العشرة جميعًا في الفلتر وفي
 * التصدير. بقية اللوحة تقرأ is_super، وهذان الموضعان كانا يخالفانها.
 */
class VisitorSellerScopeTest extends ApiTestCase
{
    private const MANAGER = 900090;

    /** مندوب إضافي، كي يوجد من هو خارج نطاق المدير. */
    private function seller(int $id): void
    {
        DB::table('admins')->updateOrInsert(['id' => $id], [
            'local_id'    => $id,
            'f_name'      => 'Seller',
            'l_name'      => (string) $id,
            'email'       => "scope{$id}@example.test",
            'password'    => \Illuminate\Support\Facades\Hash::make('password'),
            'mandob_code' => 'SC' . $id,
            'role'        => 'seller',
            'type'        => 'mandob',
            'company_id'  => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    /** مديرٌ بـ role = 'admin' و is_super = 0، كحال الحساب المُبلَّغ عنه. */
    private function manager(array $assigned): Admin
    {
        DB::table('admins')->updateOrInsert(['id' => self::MANAGER], [
            'local_id'   => self::MANAGER,
            'f_name'     => 'Region',
            'l_name'     => 'Manager',
            'email'      => 'region.manager@example.test',
            'password'   => \Illuminate\Support\Facades\Hash::make('password'),
            'role'       => 'admin',
            'is_super'   => 0,
            'visit'      => 1,
            'company_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('admin_sellers')->where('admin_id', self::MANAGER)->delete();

        foreach ($assigned as $sellerId) {
            DB::table('admin_sellers')->insert([
                'admin_id'   => self::MANAGER,
                'seller_id'  => $sellerId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $manager = Admin::find(self::MANAGER);

        $permission = \App\Models\Permission::firstOrCreate(
            ['name' => 'visits.view'],
            ['label' => 'عرض الزيارات', 'group' => 'visits']
        );

        $role = \App\Models\Role::firstOrCreate(
            ['name' => 'visits-only'],
            ['label' => 'visits-only']
        );
        $role->permissions()->syncWithoutDetaching([$permission->id]);
        $manager->roles()->sync([$role->id]);

        return $manager;
    }

    /** معرّفات المناديب في قائمة الفلتر على الصفحة. */
    private function dropdownIds(string $html): array
    {
        if (!preg_match('/<select name="seller_id".*?<\/select>/s', $html, $select)) {
            return [];
        }

        preg_match_all('/<option value="(\d+)"/', $select[0], $options);

        return array_map('intval', $options[1]);
    }

    /** @test */
    public function a_manager_only_sees_the_sellers_assigned_to_them(): void
    {
        $this->seller(900091);
        $this->seller(900092);

        $manager = $this->manager([900091]);

        $ids = $this->dropdownIds(
            $this->actingAs($manager, 'admin')
                ->get(route('admin.visitor.indexresult'))
                ->assertOk()
                ->getContent()
        );

        $this->assertContains(900091, $ids, 'المندوب المُسنَد يجب أن يظهر.');
        $this->assertNotContains(900092, $ids, 'مندوبٌ غير مُسنَد يجب ألّا يظهر.');
    }

    /**
     * عمود role لا يمنح شيئًا: 'admin' قيمته لكل موظف إداري.
     *
     * @test
     */
    public function the_role_column_alone_does_not_widen_the_list(): void
    {
        $this->seller(900091);
        $this->seller(900092);

        $manager = $this->manager([900091]);

        $this->assertSame('admin', $manager->role);
        $this->assertSame(0, (int) $manager->is_super);

        $ids = $this->dropdownIds(
            $this->actingAs($manager, 'admin')
                ->get(route('admin.visitor.indexresult'))
                ->getContent()
        );

        $this->assertCount(1, $ids, 'العدد يجب أن يطابق المُسنَد لا كل المناديب.');
    }

    /** @test */
    public function a_super_admin_still_sees_every_seller(): void
    {
        $this->seller(900091);
        $this->seller(900092);

        DB::table('admins')->where('id', ApiTestingSeeder::ADMIN_ID)->update(['is_super' => 1]);

        $ids = $this->dropdownIds(
            $this->actingAs(Admin::find(ApiTestingSeeder::ADMIN_ID), 'admin')
                ->get(route('admin.visitor.indexresult'))
                ->assertOk()
                ->getContent()
        );

        $this->assertContains(900091, $ids);
        $this->assertContains(900092, $ids);
    }

    /**
     * التصدير يحمل ما تعرضه الشاشة لا أكثر.
     *
     * الموضع الثاني من الخطأ نفسه: لو صُحِّحت الشاشة وحدها لظلّ الملف
     * يكشف مناديب لا يراهم صاحبه.
     *
     * @test
     */
    public function the_export_is_scoped_the_same_way(): void
    {
        $this->seller(900091);
        $this->seller(900092);

        $manager = $this->manager([900091]);

        // زيارة لمندوبٍ خارج نطاقه: يجب ألّا تخرج في ملفه.
        DB::table('visitors')->insert([
            'seller_id'   => 900092,
            'customer_id' => 90001,
            'note'        => 'خارج النطاق',
            'date'        => now()->toDateString(),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $response = $this->actingAs($manager, 'admin')
            ->get(route('admin.visitor.indexresult.export'));

        $response->assertOk();

        // التصدير يُرسَل ملفًا، فيُقرأ من القرص لا من جسم الاستجابة.
        $body = $response->baseResponse instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse
            ? file_get_contents($response->baseResponse->getFile()->getPathname())
            : $response->streamedContent();

        $this->assertStringNotContainsString(
            'خارج النطاق',
            (string) $body,
            'التصدير يجب ألّا يكشف زيارة مندوبٍ غير مُسنَد.'
        );
    }
}
