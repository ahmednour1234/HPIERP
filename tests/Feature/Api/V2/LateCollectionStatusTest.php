<?php

namespace Tests\Feature\Api\V2;

use Database\Seeders\ApiTestingSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\ApiTestCase;

/**
 * فاتورة حُصِّلت بعد البيع تُبلَّغ محصَّلة.
 *
 * التحصيل اللاحق (من شاشة العميل) يزيد transaction_reference ولا يمسّ
 * collected_cash. كانت الفلاتر والإجماليات تقرأ الثانية، فتُبلَّغ فاتورة
 * محصَّلة بالكامل على أنها «غير محصَّلة» — 1328 فاتورة في قاعدة الإنتاج.
 */
class LateCollectionStatusTest extends ApiTestCase
{
    public function test_an_invoice_collected_after_the_sale_is_reported_paid(): void
    {
        // كما يكتبها التحصيل اللاحق: المرجع ممتلئ والعمود القديم صفر.
        $id = $this->lateCollected(500.0, 500.0);

        $listed = $this->asSeller()
            ->getJson('/api/v2/orders?type=4&payment_status=paid')
            ->assertOk()
            ->json('data');

        $this->assertContains($id, array_column($listed, 'id'),
            'فاتورة محصَّلة بعد البيع لم تظهر ضمن المحصَّلة');

        $this->asSeller()
            ->getJson('/api/v2/orders?type=4&payment_status=unpaid')
            ->assertOk()
            ->assertJsonMissing(['id' => $id]);
    }

    public function test_the_totals_count_a_late_collection(): void
    {
        $this->lateCollected(500.0, 500.0);

        $totals = $this->asSeller()
            ->getJson('/api/v2/orders/totals?type=4')
            ->assertOk()
            ->json('data');

        $this->assertEqualsWithDelta(500.0, (float) $totals['collected'], 0.01,
            'الإجمالي لم يحسب التحصيل اللاحق');
    }

    /** @return int */
    private function lateCollected(float $amount, float $collected): int
    {
        $id = (int) (DB::table('orders')->max('id') ?? 0) + 1;

        DB::table('orders')->insert([
            'id' => $id, 'user_id' => 90001,
            'owner_id' => ApiTestingSeeder::SELLER_ID, 'type' => 4,
            'order_amount' => $amount,
            // التحصيل اللاحق يزيد المرجع وحده.
            'transaction_reference' => $collected,
            'collected_cash' => 0,
            'total_tax' => 0, 'extra_discount' => 0, 'coupon_discount_amount' => 0,
            'cash' => 1, 'insert_flag' => 1, 'update_flag' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $id;
    }
}
