<?php

namespace Tests\Feature\Api\V2;

use App\Models\Order;
use Database\Seeders\ApiTestingSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\ApiTestCase;

/**
 * حالة التحصيل والمرتجع على كل فاتورة في الرد.
 *
 * كان العميل يشتق هذه القيم بنفسه، فيختلف عن فلتر payment_status.
 */
class OrderSettlementFieldsTest extends ApiTestCase
{
    public function test_an_uncollected_invoice_reports_the_whole_amount_as_remaining(): void
    {
        $id = $this->makeSale(amount: 1000, collected: 0);

        $this->asSeller()
            ->getJson('/api/v2/orders?type=4')
            ->assertOk()
            ->assertJsonPath('data.0.id', $id)
            ->assertJsonPath('data.0.remaining', fn ($v) => (float) $v === 1000.0)
            ->assertJsonPath('data.0.payment_status', 'unpaid')
            ->assertJsonPath('data.0.payment_status_text', 'غير محصلة');
    }

    public function test_a_partly_collected_invoice_reports_what_is_left(): void
    {
        $this->makeSale(amount: 1000, collected: 400);

        $this->asSeller()
            ->getJson('/api/v2/orders?type=4')
            ->assertOk()
            ->assertJsonPath('data.0.remaining', fn ($v) => (float) $v === 600.0)
            ->assertJsonPath('data.0.payment_status', 'partial')
            ->assertJsonPath('data.0.payment_status_text', 'محصلة جزئيًا');
    }

    public function test_a_fully_collected_invoice_has_nothing_remaining(): void
    {
        $this->makeSale(amount: 1000, collected: 1000);

        $this->asSeller()
            ->getJson('/api/v2/orders?type=4')
            ->assertOk()
            ->assertJsonPath('data.0.remaining', fn ($v) => (float) $v === 0.0)
            ->assertJsonPath('data.0.payment_status', 'paid')
            ->assertJsonPath('data.0.payment_status_text', 'محصلة بالكامل');
    }

    /** تحصيل أكثر من قيمة الفاتورة لا يجعل المتبقّي سالبًا. */
    public function test_over_collection_does_not_report_a_negative_remaining(): void
    {
        $this->makeSale(amount: 1000, collected: 1200);

        $this->asSeller()
            ->getJson('/api/v2/orders?type=4')
            ->assertOk()
            ->assertJsonPath('data.0.remaining', fn ($v) => (float) $v === 0.0)
            ->assertJsonPath('data.0.payment_status', 'paid');
    }

    public function test_an_invoice_without_returns_reports_none(): void
    {
        $this->makeSale(amount: 500, collected: 500);

        $this->asSeller()
            ->getJson('/api/v2/orders?type=4')
            ->assertOk()
            ->assertJsonPath('data.0.returned_amount', fn ($v) => (float) $v === 0.0)
            ->assertJsonPath('data.0.has_returns', false);
    }

    public function test_an_invoice_with_returns_reports_the_returned_amount(): void
    {
        $id = $this->makeSale(amount: 1000, collected: 1000);
        $this->makeReturn($id, 250);

        $this->asSeller()
            ->getJson("/api/v2/orders?type=4")
            ->assertOk()
            ->assertJsonPath('data.0.id', $id)
            ->assertJsonPath('data.0.returned_amount', fn ($v) => (float) $v === 250.0)
            ->assertJsonPath('data.0.has_returns', true);
    }

    public function test_several_returns_on_one_invoice_are_summed(): void
    {
        $id = $this->makeSale(amount: 1000, collected: 1000);
        $this->makeReturn($id, 100);
        $this->makeReturn($id, 150);

        $this->asSeller()
            ->getJson('/api/v2/orders?type=4')
            ->assertOk()
            ->assertJsonPath('data.0.returned_amount', fn ($v) => (float) $v === 250.0);
    }

    /** عرض فاتورة واحدة لا يمر بالاستعلام الفرعي، فيحسبها من العلاقة. */
    public function test_showing_one_invoice_reports_the_same_figures(): void
    {
        $id = $this->makeSale(amount: 1000, collected: 400);
        $this->makeReturn($id, 250);

        $this->asSeller()
            ->getJson("/api/v2/orders/{$id}")
            ->assertOk()
            ->assertJsonPath('data.remaining', fn ($v) => (float) $v === 600.0)
            ->assertJsonPath('data.payment_status', 'partial')
            ->assertJsonPath('data.returned_amount', fn ($v) => (float) $v === 250.0)
            ->assertJsonPath('data.has_returns', true);
    }

    /**
     * الحقل المحسوب يطابق الفلتر، وإلا عرضت قائمة مفلترة صفوفًا تناقض
     * الفلتر الذي أنتجها.
     */
    public function test_the_reported_status_agrees_with_the_payment_status_filter(): void
    {
        $this->makeSale(amount: 1000, collected: 0);
        $this->makeSale(amount: 1000, collected: 400);
        $this->makeSale(amount: 1000, collected: 1000);

        foreach (['unpaid', 'partial', 'paid'] as $state) {
            $rows = $this->asSeller()->getJson("/api/v2/orders?type=4&payment_status={$state}")
                ->assertOk()->json('data');

            $this->assertNotEmpty($rows, "no rows for {$state}");

            foreach ($rows as $row) {
                $this->assertSame($state, $row['payment_status'],
                    "row {$row['id']} came back from the {$state} filter reporting {$row['payment_status']}");
            }
        }
    }

    private function makeSale(float $amount, float $collected): int
    {
        $id = (int) (DB::table('orders')->max('id') ?? 0) + 1;

        DB::table('orders')->insert([
            'id' => $id, 'user_id' => 90001,
            'owner_id' => ApiTestingSeeder::SELLER_ID, 'type' => 4,
            'order_amount' => $amount, 'collected_cash' => $collected,
            'total_tax' => 0, 'extra_discount' => 0, 'coupon_discount_amount' => 0,
            'cash' => 1, 'insert_flag' => 1, 'update_flag' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $id;
    }

    private function makeReturn(int $parentId, float $amount): void
    {
        $id = (int) (DB::table('orders')->max('id') ?? 0) + 1;

        DB::table('orders')->insert([
            'id' => $id, 'user_id' => 90001,
            'owner_id' => ApiTestingSeeder::SELLER_ID, 'type' => 7,
            'parent_id' => $parentId,
            'order_amount' => $amount, 'collected_cash' => 0,
            'total_tax' => 0, 'extra_discount' => 0, 'coupon_discount_amount' => 0,
            'cash' => 1, 'insert_flag' => 1, 'update_flag' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
