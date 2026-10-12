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
}
