<?php

namespace Tests\Feature\Api\V2;

use Database\Seeders\ApiTestingSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\ApiTestCase;

/**
 * التسوية بعد المرتجع.
 *
 * المرتجع صف مستقل ولا يمس الفاتورة الأصلية، فـ payment_status و
 * remaining يقارنان بالمبلغ قبل الإرجاع: فاتورة 1000 حُصِّل منها 400
 * وأُرجع 600 تبدو "جزئية بمتبقٍّ 600" بينما العميل لا يدين بشيء.
 */
class OrderNetSettlementTest extends ApiTestCase
{
    /** الحالة التي كشفها فريق التطبيق: جزء محصَّل وجزء مرتجع. */
    public function test_part_collected_and_part_returned_leaves_nothing_owed(): void
    {
        $id = $this->sale(1000, 400);
        $this->returnAgainst($id, 600);

        $row = $this->firstRow();

        // الحقول القديمة تتجاهل المرتجع.
        $this->assertSame('partial', $row['payment_status']);
        $this->assertEqualsWithDelta(600.0, (float) $row['remaining'], 0.01);

        // والصافي يأخذه في الحسبان.
        $this->assertEqualsWithDelta(400.0, (float) $row['net_amount'], 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $row['net_remaining'], 0.01);
        $this->assertSame('settled', $row['settlement_status']);
        $this->assertSame('مسوّاة', $row['settlement_status_text']);
    }

    public function test_a_fully_returned_invoice_is_marked_as_such(): void
    {
        $id = $this->sale(1000, 0);
        $this->returnAgainst($id, 1000);

        $row = $this->firstRow();

        $this->assertEqualsWithDelta(0.0, (float) $row['net_amount'], 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $row['net_remaining'], 0.01);
        $this->assertSame('fully_returned', $row['settlement_status']);
    }

    public function test_an_invoice_without_returns_settles_on_its_own_amount(): void
    {
        $this->sale(1000, 1000);

        $row = $this->firstRow();

        $this->assertEqualsWithDelta(1000.0, (float) $row['net_amount'], 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $row['net_remaining'], 0.01);
        $this->assertSame('settled', $row['settlement_status']);
    }

    public function test_a_partial_return_still_leaves_a_balance(): void
    {
        $id = $this->sale(1000, 300);
        $this->returnAgainst($id, 200);

        $row = $this->firstRow();

        // 1000 - 200 مرتجع = 800 مستحق، حُصِّل 300 => 500 باقية.
        $this->assertEqualsWithDelta(800.0, (float) $row['net_amount'], 0.01);
        $this->assertEqualsWithDelta(500.0, (float) $row['net_remaining'], 0.01);
        $this->assertSame('partial', $row['settlement_status']);
    }

    /** مرتجع بلا تحصيل: الباقي ينقص لكن الفاتورة تبقى غير محصلة. */
    public function test_a_return_without_any_collection_stays_unpaid(): void
    {
        $id = $this->sale(1000, 0);
        $this->returnAgainst($id, 400);

        $row = $this->firstRow();

        $this->assertEqualsWithDelta(600.0, (float) $row['net_amount'], 0.01);
        $this->assertEqualsWithDelta(600.0, (float) $row['net_remaining'], 0.01);
        $this->assertSame('unpaid', $row['settlement_status']);
    }

    public function test_several_returns_are_summed_into_the_net(): void
    {
        $id = $this->sale(1000, 200);
        $this->returnAgainst($id, 300);
        $this->returnAgainst($id, 500);

        $row = $this->firstRow();

        $this->assertEqualsWithDelta(200.0, (float) $row['net_amount'], 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $row['net_remaining'], 0.01);
        $this->assertSame('settled', $row['settlement_status']);
    }

    /** المرتجع لا يتجاوز الفاتورة، لكن الصافي لا ينزل تحت الصفر لو حدث. */
    public function test_returning_more_than_the_invoice_does_not_go_negative(): void
    {
        $id = $this->sale(1000, 0);
        $this->returnAgainst($id, 1500);

        $row = $this->firstRow();

        $this->assertEqualsWithDelta(0.0, (float) $row['net_amount'], 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $row['net_remaining'], 0.01);
        $this->assertSame('fully_returned', $row['settlement_status']);
    }

    /** عرض فاتورة واحدة يعطي نفس الأرقام رغم غياب الاستعلام الفرعي. */
    public function test_showing_one_invoice_reports_the_same_net_figures(): void
    {
        $id = $this->sale(1000, 400);
        $this->returnAgainst($id, 600);

        $this->asSeller()
            ->getJson("/api/v2/orders/{$id}")
            ->assertOk()
            ->assertJsonPath('data.net_amount', fn ($v) => (float) $v === 400.0)
            ->assertJsonPath('data.net_remaining', fn ($v) => (float) $v === 0.0)
            ->assertJsonPath('data.settlement_status', 'settled');
    }

    /**
     * payment_status يبقى كما هو: الفلترة تعتمد عليه، فتغييره يجعل الصف
     * يناقض التبويب الذي جاء منه.
     */
    public function test_the_payment_status_filter_still_agrees_with_the_row(): void
    {
        $id = $this->sale(1000, 400);
        $this->returnAgainst($id, 600);

        $rows = $this->asSeller()
            ->getJson('/api/v2/orders?type=4&payment_status=partial')
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $rows);
        $this->assertSame('partial', $rows[0]['payment_status']);
        $this->assertSame('settled', $rows[0]['settlement_status']);
    }

    /**
     * الفرق ينقلب لصالح العميل: تحصيل كامل ثم إرجاع جزء يترك مبلغًا
     * يُردّ إليه، والمتبقّي مصفور عند الصفر فلا يُظهره.
     */
    public function test_collecting_in_full_then_returning_leaves_the_customer_owed(): void
    {
        $id = $this->sale(1000, 1000);
        $this->returnAgainst($id, 600);

        $row = $this->firstRow();

        $this->assertEqualsWithDelta(400.0, (float) $row['net_amount'], 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $row['net_remaining'], 0.01);
        $this->assertEqualsWithDelta(600.0, (float) $row['overpaid'], 0.01);
        $this->assertSame('overpaid', $row['settlement_status']);
        $this->assertSame('محصلة بالزيادة', $row['settlement_status_text']);
    }

    /** الدفعة الزائدة بلا مرتجع تُبلَّغ كذلك. */
    public function test_a_plain_overpayment_is_reported(): void
    {
        $this->sale(1000, 1200);

        $row = $this->firstRow();

        $this->assertEqualsWithDelta(200.0, (float) $row['overpaid'], 0.01);
        $this->assertSame('overpaid', $row['settlement_status']);
    }

    /** الإرجاع الكامل بعد التحصيل الكامل: المبلغ كله يُردّ. */
    public function test_a_fully_collected_invoice_returned_in_full_is_owed_back(): void
    {
        $id = $this->sale(1000, 1000);
        $this->returnAgainst($id, 1000);

        $row = $this->firstRow();

        $this->assertEqualsWithDelta(0.0, (float) $row['net_amount'], 0.01);
        $this->assertEqualsWithDelta(1000.0, (float) $row['overpaid'], 0.01);

        // الزيادة تسبق "مرتجعة بالكامل": هناك ما يُردّ فعلًا.
        $this->assertSame('overpaid', $row['settlement_status']);
    }

    public function test_a_settled_invoice_reports_no_overpayment(): void
    {
        $id = $this->sale(1000, 400);
        $this->returnAgainst($id, 600);

        $row = $this->firstRow();

        $this->assertEqualsWithDelta(0.0, (float) $row['overpaid'], 0.01);
        $this->assertSame('settled', $row['settlement_status']);
    }

    /**
     * الإجماليات تجمع لكل فاتورة على حدة: طرحها إجماليًا يجعل زيادة
     * فاتورة تُلغي دين أخرى فيبدو الشريط خاليًا وكلاهما قائم.
     */
    public function test_totals_do_not_let_an_overpayment_cancel_another_invoices_debt(): void
    {
        $this->sale(1000, 500);              // عليها 500

        $paid = $this->sale(1000, 1000);     // وله فيها 600
        $this->returnAgainst($paid, 600);

        $totals = $this->asSeller()
            ->getJson('/api/v2/orders/totals?type=4')
            ->assertOk()
            ->json('data');

        $this->assertEqualsWithDelta(500.0, (float) $totals['net_remaining'], 0.01);
        $this->assertEqualsWithDelta(600.0, (float) $totals['overpaid'], 0.01);
    }

    /** شريط الإجماليات يحمل نفس التصحيح، وإلا ناقض مجموع الصفوف تحته. */
    public function test_totals_report_the_returned_amount_and_the_net(): void
    {
        $id = $this->sale(1000, 400);
        $this->returnAgainst($id, 600);
        $this->sale(500, 0);

        $totals = $this->asSeller()
            ->getJson('/api/v2/orders/totals?type=4')
            ->assertOk()
            ->json('data');

        $this->assertSame(2, $totals['orders']);
        $this->assertEqualsWithDelta(1500.0, (float) $totals['total'], 0.01);
        $this->assertEqualsWithDelta(600.0, (float) $totals['returned'], 0.01);

        // قبل المرتجع: 1500 - 400 محصَّل.
        $this->assertEqualsWithDelta(1100.0, (float) $totals['remaining'], 0.01);

        // بعده: 1500 - 600 مرتجع - 400 محصَّل = 500، وهي الفاتورة الثانية.
        $this->assertEqualsWithDelta(900.0, (float) $totals['net_total'], 0.01);
        $this->assertEqualsWithDelta(500.0, (float) $totals['net_remaining'], 0.01);
    }

    /** المرتجعات المحسوبة هي مرتجعات الفواتير المطابقة للفلتر وحدها. */
    public function test_totals_only_count_returns_of_the_filtered_invoices(): void
    {
        $old = $this->sale(1000, 0);
        DB::table('orders')->where('id', $old)->update(['created_at' => '2026-01-10 10:00:00']);
        $oldReturn = $this->returnAgainst($old, 400);
        DB::table('orders')->where('id', $oldReturn)->update(['created_at' => '2026-01-11 10:00:00']);

        $recent = $this->sale(1000, 0);
        $this->returnAgainst($recent, 250);

        // نافذة تستبعد الفاتورة القديمة: مرتجعها لا يُحسب.
        $totals = $this->asSeller()
            ->getJson('/api/v2/orders/totals?type=4&from=2026-06-01')
            ->assertOk()
            ->json('data');

        $this->assertSame(1, $totals['orders']);
        $this->assertEqualsWithDelta(250.0, (float) $totals['returned'], 0.01);
        $this->assertEqualsWithDelta(750.0, (float) $totals['net_remaining'], 0.01);
    }

    private function firstRow(): array
    {
        return $this->asSeller()
            ->getJson('/api/v2/orders?type=4')
            ->assertOk()
            ->json('data.0');
    }

    private function sale(float $amount, float $collected): int
    {
        return $this->order(['type' => 4, 'order_amount' => $amount, 'collected_cash' => $collected]);
    }

    private function returnAgainst(int $parentId, float $amount): int
    {
        return $this->order([
            'type' => 7, 'parent_id' => $parentId,
            'order_amount' => $amount, 'collected_cash' => $amount,
        ]);
    }

    private function order(array $attributes): int
    {
        $id = (int) (DB::table('orders')->max('id') ?? 0) + 1;

        DB::table('orders')->insert(array_merge([
            'id' => $id, 'user_id' => 90001,
            'owner_id' => ApiTestingSeeder::SELLER_ID,
            'total_tax' => 0, 'extra_discount' => 0, 'coupon_discount_amount' => 0,
            'cash' => 1, 'insert_flag' => 1, 'update_flag' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ], $attributes));

        return $id;
    }
}
