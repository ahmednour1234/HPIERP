<?php

namespace Tests\Feature\Api\V2;

use Database\Seeders\ApiTestingSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\ApiTestCase;

/**
 * العميل الذي يضيفه المندوب يُنشأ مفعَّلًا وفي قائمته.
 *
 * الشكوى المتكرّرة: المندوب يضيف عميلًا ولا يجده بعدها، فلا يستطيع
 * إصدار فاتورة له.
 */
class CustomerCreatedActiveTest extends ApiTestCase
{
    private function create(array $extra = [])
    {
        return $this->asSeller()->postJson('/api/v2/customers', array_merge([
            'name'   => 'صيدلية اختبار',
            'mobile' => '01000000001',
        ], $extra));
    }

    /** @test */
    public function a_new_customer_is_active(): void
    {
        $response = $this->create()->assertCreated();

        $id = $response->json('data.id');

        $this->assertSame(1, (int) DB::table('customers')->where('id', $id)->value('active'));
        $this->assertTrue((bool) $response->json('data.active'), 'الرد يجب أن يقول إنه مفعَّل.');
    }

    /** @test */
    public function a_new_customer_is_assigned_to_the_seller(): void
    {
        $id = $this->create()->assertCreated()->json('data.id');

        $this->assertDatabaseHas('seller_customers', [
            'customer_id' => $id,
            'seller_id'   => ApiTestingSeeder::SELLER_ID,
        ]);
    }

    /** ويظهر في قائمته فورًا. */
    public function test_it_appears_in_the_sellers_listing(): void
    {
        $id = $this->create(['name' => 'صيدلية ظاهرة'])->assertCreated()->json('data.id');

        $ids = collect($this->asSeller()->getJson('/api/v2/customers')->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($id), 'العميل المضاف يجب أن يظهر في قائمة المندوب.');
    }

    /**
     * منطقة غير موجودة تُرفض بوضوح لا بصمت.
     *
     * region_id مقيَّدة بـ exists، فإرسال منطقة غير معروفة يردّ 422
     * ولا يُنشأ العميل — وهو سبب محتمل لاختفائه من وجهة نظر المندوب.
     *
     * @test
     */
    public function an_unknown_region_is_rejected_with_a_message(): void
    {
        $this->create(['region_id' => 99999999])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['region_id']);
    }

    /**
     * العميل يظهر عند مدير المندوب كذلك.
     *
     * admin_sellers تحمل الإدارة: صفُّ (admin_id, seller_id) يعني أن
     * الأول يدير الثاني. وكان العميل يُسنَد للمندوب وحده، فيبقى خارج
     * قوائم من يتابعه حتى يُسنَد يدويًّا.
     *
     * @test
     */
    public function a_new_customer_reaches_the_sellers_manager(): void
    {
        $manager = 900700;

        DB::table('admins')->updateOrInsert(['id' => $manager], [
            'local_id'   => $manager,
            'f_name'     => 'Area',
            'l_name'     => 'Manager',
            'email'      => 'area.manager@example.test',
            'password'   => \Illuminate\Support\Facades\Hash::make('password'),
            'role'       => 'admin',
            'company_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('admin_sellers')->updateOrInsert(
            ['admin_id' => $manager, 'seller_id' => ApiTestingSeeder::SELLER_ID],
            ['created_at' => now(), 'updated_at' => now()]
        );

        $id = $this->create(['name' => 'صيدلية المدير'])->assertCreated()->json('data.id');

        $this->assertDatabaseHas('seller_customers', [
            'customer_id' => $id,
            'seller_id'   => $manager,
        ]);
    }

    /** وصولًا إلى مدير المدير. */
    public function test_it_reaches_the_whole_chain_above(): void
    {
        foreach ([900701 => 'Mid', 900702 => 'Top'] as $id => $name) {
            DB::table('admins')->updateOrInsert(['id' => $id], [
                'local_id'   => $id,
                'f_name'     => $name,
                'l_name'     => 'Manager',
                'email'      => strtolower($name) . '.mgr@example.test',
                'password'   => \Illuminate\Support\Facades\Hash::make('password'),
                'role'       => 'admin',
                'company_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 702 يدير 701، و701 يدير المندوب.
        DB::table('admin_sellers')->updateOrInsert(
            ['admin_id' => 900701, 'seller_id' => ApiTestingSeeder::SELLER_ID],
            ['created_at' => now(), 'updated_at' => now()]
        );
        DB::table('admin_sellers')->updateOrInsert(
            ['admin_id' => 900702, 'seller_id' => 900701],
            ['created_at' => now(), 'updated_at' => now()]
        );

        $id = $this->create(['name' => 'صيدلية السلسلة'])->assertCreated()->json('data.id');

        $this->assertDatabaseHas('seller_customers', ['customer_id' => $id, 'seller_id' => 900701]);
        $this->assertDatabaseHas('seller_customers', ['customer_id' => $id, 'seller_id' => 900702]);
    }

    /**
     * مديرٌ يدير نفسه لا يُدخل الحلقة في دورة لا تنتهي.
     *
     * البيانات القائمة تحوي ذلك فعلًا (#100).
     *
     * @test
     */
    public function a_self_managing_admin_does_not_loop(): void
    {
        $self = 900703;

        DB::table('admins')->updateOrInsert(['id' => $self], [
            'local_id'   => $self,
            'f_name'     => 'Self',
            'l_name'     => 'Manager',
            'email'      => 'self.mgr@example.test',
            'password'   => \Illuminate\Support\Facades\Hash::make('password'),
            'role'       => 'admin',
            'company_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('admin_sellers')->updateOrInsert(
            ['admin_id' => $self, 'seller_id' => $self],
            ['created_at' => now(), 'updated_at' => now()]
        );
        DB::table('admin_sellers')->updateOrInsert(
            ['admin_id' => $self, 'seller_id' => ApiTestingSeeder::SELLER_ID],
            ['created_at' => now(), 'updated_at' => now()]
        );

        $id = $this->create(['name' => 'صيدلية الدورة'])->assertCreated()->json('data.id');

        $this->assertDatabaseHas('seller_customers', ['customer_id' => $id, 'seller_id' => $self]);
    }
}
