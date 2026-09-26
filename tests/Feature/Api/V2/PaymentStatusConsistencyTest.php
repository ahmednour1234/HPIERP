<?php

namespace Tests\Feature\Api\V2;

use Database\Seeders\ApiTestingSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\ApiTestCase;

/**
 * مصدر واحد لحالة التحصيل: الفلترة والإجماليات والحقل في الرد.
 *
 * القاعدة، بهذا الترتيب:
 *   collected >= amount  -> paid
 *   collected >  0       -> partial
 *   collected == 0       -> unpaid
 *
 * التطبيق يحسب الشارة محليًا بنفس المعادلة عمدًا، ليظهر أي اختلاف بدل أن
 * يختفي. هذه الاختبارات تثبّت الطرفين على معادلة واحدة.
 */
class PaymentStatusConsistencyTest extends ApiTestCase
{
    /** الحد الفاصل: التحصيل المساوي للمبلغ محصَّل بالكامل لا جزئيًا. */
    public function test_collecting_exactly_the_amount_is_paid(): void
    {
        $this->sale(amount: 1000, collected: 1000);

        $this->assertStatusEverywhere('paid', 1);
    }

    /** المقارنة >= لا ==، فالدفعة الزائدة محصَّلة بالكامل. */
    public function test_collecting_more_than_the_amount_is_paid_not_partial(): void
    {
        $this->sale(amount: 1000, collected: 1200);

        $this->assertStatusEverywhere('paid', 1);

        $this->asSeller()->getJson('/api/v2/orders?type=4&payment_status=partial')
            ->assertOk()->assertJsonCount(0, 'data');
    }

    /** جنيه واحد يخرجها من "غير محصلة" إلى "جزئي". */
    public function test_collecting_a_single_unit_is_partial_not_unpaid(): void
    {
        $this->sale(amount: 1000, collected: 1);

        $this->assertStatusEverywhere('partial', 1);
    }

    /** جنيه واحد ناقص يبقيها جزئية لا محصَّلة. */
    public function test_one_unit_short_is_partial_not_paid(): void
    {
        $this->sale(amount: 1000, collected: 999);

        $this->assertStatusEverywhere('partial', 1);
    }

    public function test_collecting_nothing_is_unpaid(): void
    {
        $this->sale(amount: 1000, collected: 0);

        $this->assertStatusEverywhere('unpaid', 1);
    }

    /** collected_cash فارغة تعامَل معاملة الصفر لا تُستبعد من الفلاتر. */
    public function test_a_null_collected_cash_is_unpaid(): void
    {
        $id = $this->sale(amount: 1000, collected: 0);
        DB::table('orders')->where('id', $id)->update(['collected_cash' => null]);

        $this->assertStatusEverywhere('unpaid', 1);
    }

    /**
     * الحالات الثلاث تقسم الفواتير قسمة كاملة لا تتداخل: مجموع التبويبات
     * يساوي الكل، فلا فاتورة تغيب من كل تبويب أو تظهر في اثنين.
     */
    public function test_the_three_states_partition_the_invoices(): void
    {
        $this->sale(amount: 1000, collected: 0);
        $this->sale(amount: 1000, collected: 400);
        $this->sale(amount: 1000, collected: 1000);
        $this->sale(amount: 1000, collected: 1500);

        $all = $this->asSeller()->getJson('/api/v2/orders?type=4')->json('data');
        $this->assertCount(4, $all);

        $seen = [];
        foreach (['paid', 'partial', 'unpaid'] as $state) {
            foreach ($this->asSeller()->getJson("/api/v2/orders?type=4&payment_status={$state}")->json('data') as $row) {
                $this->assertSame($state, $row['payment_status']);
                $this->assertNotContains($row['id'], $seen, "invoice {$row['id']} appeared in two tabs");
                $seen[] = $row['id'];
            }
        }

        $this->assertCount(4, $seen, 'an invoice appeared in no tab at all');
    }

    /**
     * الإجماليات تحترم الفلتر: totals تشارك سلسلة الفلاتر نفسها، فمجموع
     * التبويبات يساوي الكل ولا يعرض الشريط أرقام صفوف غير معروضة.
     */
    public function test_totals_respect_the_payment_status_filter(): void
    {
        $this->sale(amount: 1000, collected: 0);
        $this->sale(amount: 1000, collected: 400);
        $this->sale(amount: 1000, collected: 1000);

        $all = $this->asSeller()->getJson('/api/v2/orders/totals?type=4')->json('data');
        $this->assertSame(3, $all['orders']);
        $this->assertEqualsWithDelta(1600.0, (float) $all['remaining'], 0.01);

        $paid = $this->asSeller()->getJson('/api/v2/orders/totals?type=4&payment_status=paid')->json('data');
        $this->assertSame(1, $paid['orders']);
        $this->assertEqualsWithDelta(0.0, (float) $paid['remaining'], 0.01);

        $partial = $this->asSeller()->getJson('/api/v2/orders/totals?type=4&payment_status=partial')->json('data');
        $this->assertSame(1, $partial['orders']);
        $this->assertEqualsWithDelta(600.0, (float) $partial['remaining'], 0.01);

        $unpaid = $this->asSeller()->getJson('/api/v2/orders/totals?type=4&payment_status=unpaid')->json('data');
        $this->assertSame(1, $unpaid['orders']);
        $this->assertEqualsWithDelta(1000.0, (float) $unpaid['remaining'], 0.01);

        // القسمة كاملة: التبويبات تجمع إلى الكل.
        $this->assertSame(
            $all['orders'],
            $paid['orders'] + $partial['orders'] + $unpaid['orders']
        );
        $this->assertEqualsWithDelta(
            (float) $all['remaining'],
            (float) $paid['remaining'] + (float) $partial['remaining'] + (float) $unpaid['remaining'],
            0.01
        );
    }

    /**
     * المرتجعات لا تحصَّل، فـ collected_cash = 0 يضعها في "غير محصلة"
     * ويضيف قيمتها إلى remaining كأنها دين. التطبيق يمرر type=4 دائمًا
     * فلا يتأثر، وهذا الاختبار يوثّق السلوك حتى لا يُقرأ الرقم خطأً.
     */
    public function test_returns_count_as_unpaid_when_the_type_filter_is_omitted(): void
    {
        $saleId = $this->sale(amount: 1000, collected: 1000);
        $this->returnAgainst($saleId, 250);

        // مع type=4: الفاتورة وحدها، ولا شيء مستحق.
        $sales = $this->asSeller()->getJson('/api/v2/orders/totals?type=4&payment_status=unpaid')->json('data');
        $this->assertSame(0, $sales['orders']);
        $this->assertEqualsWithDelta(0.0, (float) $sales['remaining'], 0.01);

        // بدونه: المرتجع يُحسب دينًا لأنه لم يُحصَّل.
        $mixed = $this->asSeller()->getJson('/api/v2/orders/totals?payment_status=unpaid')->json('data');
        $this->assertSame(1, $mixed['orders']);
        $this->assertEqualsWithDelta(250.0, (float) $mixed['remaining'], 0.01);
    }

    /** الحالة المعلنة في الرد هي نفسها التي أنتجها الفلتر. */
    private function assertStatusEverywhere(string $expected, int $count): void
    {
        $this->asSeller()->getJson('/api/v2/orders?type=4')
            ->assertOk()
            ->assertJsonPath('data.0.payment_status', $expected);

        $this->asSeller()->getJson("/api/v2/orders?type=4&payment_status={$expected}")
            ->assertOk()
            ->assertJsonCount($count, 'data')
            ->assertJsonPath('data.0.payment_status', $expected);

        $totals = $this->asSeller()
            ->getJson("/api/v2/orders/totals?type=4&payment_status={$expected}")
            ->json('data');

        $this->assertSame($count, $totals['orders'],
            "totals disagreed with the listing for {$expected}");
    }

    private function sale(float $amount, float $collected): int
    {
        return $this->order(['type' => 4, 'order_amount' => $amount, 'collected_cash' => $collected]);
    }

    private function returnAgainst(int $parentId, float $amount): int
    {
        return $this->order([
            'type' => 7, 'parent_id' => $parentId,
            'order_amount' => $amount, 'collected_cash' => 0,
        ]);
    }

    private function order(array $attributes): int
    {
        $id = (int) (DB::table('orders')->max('id') ?? 0) + 1;

        // الإنتاج يكتب العمودين معًا، فتُحاكيه التجهيزة: تحديد
        // collected_cash وحده كان يُنتج صفًّا لا يوجد مثله.
        if (array_key_exists('collected_cash', $attributes)
            && !array_key_exists('transaction_reference', $attributes)) {
            $attributes['transaction_reference'] = $attributes['collected_cash'];
        }

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
