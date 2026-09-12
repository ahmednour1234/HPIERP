<?php

namespace Tests\Feature\Api\V2;

use Database\Seeders\ApiTestingSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\ApiTestCase;

/**
 * فلترة العملاء بنوع الجهة (specialist): 1 صيدلية، 2 مركز، 3 مستشفى، 4 طبيب.
 *
 * وهو غير التخصص الطبي category_id، والفلتران يعملان معًا بـ AND.
 */
class CustomerSpecialistFilterTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // العميل الأصلي صيدلية بتخصص 90001.
        DB::table('customers')->where('id', 90001)
            ->update(['specialist' => 1, 'category_id' => 90001]);

        // طبيب بنفس التخصص، وطبيب بتخصص آخر: يفرّقان بين الفلترين.
        $this->makeCustomer(90011, 'Doctor Same Category', specialist: 4, categoryId: 90001);
        $this->makeCustomer(90012, 'Doctor Other Category', specialist: 4, categoryId: 90002);
    }

    public function test_customers_can_be_filtered_by_specialist(): void
    {
        $this->asSeller()
            ->getJson('/api/v2/customers?specialist=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 90001);

        $ids = collect($this->asSeller()->getJson('/api/v2/customers?specialist=4')->json('data'))
            ->pluck('id')->sort()->values()->all();

        $this->assertSame([90011, 90012], $ids);
    }

    public function test_specialist_accepts_more_than_one_value(): void
    {
        $ids = collect($this->asSeller()->getJson('/api/v2/customers?specialist[]=1&specialist[]=4')->json('data'))
            ->pluck('id')->sort()->values()->all();

        $this->assertSame([90001, 90011, 90012], $ids);
    }

    public function test_specialist_and_category_combine_with_and(): void
    {
        // أطباء تخصصهم 90001 فقط — لا كل الأطباء ولا كل من في التخصص.
        $this->asSeller()
            ->getJson('/api/v2/customers?specialist=4&category_id=90001')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 90011);
    }

    public function test_specialist_combines_with_region_and_search(): void
    {
        $region = (int) DB::table('customers')->where('id', 90011)->value('region_id');

        // الطبيبان في نفس المنطقة، فالفلتر يرجعهما معًا لا واحدًا بعينه.
        $ids = collect($this->asSeller()
            ->getJson("/api/v2/customers?specialist=4&region_ids[]={$region}")
            ->assertOk()
            ->json('data'))->pluck('id')->sort()->values()->all();

        $this->assertSame([90011, 90012], $ids);

        $this->asSeller()
            ->getJson('/api/v2/customers?specialist=4&search=Other')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 90012);
    }

    public function test_the_response_carries_the_specialist_name(): void
    {
        $this->asSeller()
            ->getJson('/api/v2/customers?specialist=1')
            ->assertOk()
            ->assertJsonPath('data.0.specialist', 1)
            ->assertJsonPath('data.0.specialist_name', 'صيدلية');

        $this->asSeller()
            ->getJson('/api/v2/customers?specialist=4')
            ->assertOk()
            ->assertJsonPath('data.0.specialist_name', 'طبيب');
    }

    /**
     * العمود int بلا قيد وبعض الصفوف تحمل أرقامًا خارج 1..4، فالاسم
     * يجب أن يكون null لا اسمًا مختلقًا.
     */
    public function test_an_out_of_range_specialist_has_no_name(): void
    {
        DB::table('customers')->where('id', 90011)->update(['specialist' => 6352362]);

        $this->asSeller()
            ->getJson('/api/v2/customers?specialist=6352362')
            ->assertOk()
            ->assertJsonPath('data.0.specialist_name', null);
    }

    public function test_omitting_the_filter_returns_every_assigned_customer(): void
    {
        $this->asSeller()
            ->getJson('/api/v2/customers')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    private function makeCustomer(int $id, string $name, int $specialist, int $categoryId): void
    {
        DB::table('customers')->updateOrInsert(
            ['id' => $id],
            ['local_id' => $id, 'name' => $name, 'name_en' => $name,
             'mobile' => '0100000' . $id, 'specialist' => $specialist,
             'category_id' => $categoryId, 'region_id' => 90001,
             'active' => 1, 'balance' => 0, 'credit' => 0,
             'created_at' => now(), 'updated_at' => now()]
        );

        DB::table('seller_customers')->updateOrInsert(
            ['id' => $id],
            ['local_id' => 0, 'customer_id' => $id, 'seller_id' => ApiTestingSeeder::SELLER_ID]
        );
    }
}
