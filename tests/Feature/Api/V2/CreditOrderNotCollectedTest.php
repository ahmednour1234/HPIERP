<?php

namespace Tests\Feature\Api\V2;

use App\Models\Order;
use App\Services\OrderService;
use Database\Seeders\ApiTestingSeeder;
use Tests\Feature\Api\ApiTestCase;

/**
 * فاتورة آجل تُنشأ غير محصَّلة.
 *
 * كان الافتراضي عند الإنشاء `$data['collected_cash'] ?? $grandTotal`،
 * فالتطبيق إن لم يرسل المبلغ — وهو لا يرسله مع الآجل — تُكتب الفاتورة
 * محصَّلةً بالكامل لحظة إنشائها. فتظهر في الشاشة «محصّلة بالكامل»
 * وهي دَين لم يُقبض منه شيء، وتختفي من فلتر غير المحصَّل.
 */
class CreditOrderNotCollectedTest extends ApiTestCase
{
    private function helper(array $data, float $grandTotal): float
    {
        $method = new \ReflectionMethod(OrderService::class, 'collectedAtSale');
        $method->setAccessible(true);

        return $method->invoke(app(OrderService::class), $data, $grandTotal);
    }

    /** @test */
    public function a_credit_sale_collects_nothing_at_creation(): void
    {
        $this->assertSame(0.0, $this->helper(['cash' => OrderService::CREDIT], 110.60));
    }

    /** @test */
    public function a_cash_sale_is_collected_in_full(): void
    {
        $this->assertSame(110.60, $this->helper(['cash' => OrderService::CASH], 110.60));
    }

    /** بلا عمود cash يُعامل نقديًّا، كما كان. */
    public function test_the_default_stays_cash(): void
    {
        $this->assertSame(110.60, $this->helper([], 110.60));
    }

    /**
     * مبلغٌ مُرسَل صراحةً يُحترم، ولو كان صفرًا أو جزئيًّا.
     *
     * @test
     */
    public function an_explicit_amount_wins(): void
    {
        $this->assertSame(50.0, $this->helper(
            ['cash' => OrderService::CREDIT, 'collected_cash' => 50], 110.60
        ));

        $this->assertSame(50.0, $this->helper(
            ['cash' => OrderService::CASH, 'collected_cash' => 50], 110.60
        ));

        // صفرٌ صريح على بيع نقدي يبقى صفرًا: ?? كان يتجاوزه.
        $this->assertSame(0.0, $this->helper(
            ['cash' => OrderService::CASH, 'collected_cash' => 0], 110.60
        ));
    }

    /**
     * الفاتورة الآجلة المنشأة من v2 لا تُقرأ محصَّلة.
     *
     * @test
     */
    public function a_credit_invoice_is_not_reported_as_paid(): void
    {
        $account = \Illuminate\Support\Facades\DB::table('accounts')->first();

        if (!$account) {
            $this->markTestSkipped('لا يوجد حساب في البيانات.');
        }

        $response = $this->asSeller()->postJson('/api/v2/orders', [
            'user_id'    => 90001,
            'order_type' => 4,
            'cash'       => OrderService::CREDIT,
            'type'       => $account->id,
            'cart'       => [[
                'id'       => 90001,
                'quantity' => 1,
                'price'    => 110.60,
            ]],
        ]);

        $response->assertSuccessful();

        $order = Order::latest('id')->first();

        $this->assertSame(
            0.0,
            round((float) $order->transaction_reference, 2),
            'الآجل يجب أن يُنشأ غير محصَّل.'
        );

        $this->assertSame(
            \App\Support\InvoiceSettlement::UNPAID,
            \App\Support\InvoiceSettlement::for(
                (float) $order->order_amount,
                (float) $order->transaction_reference,
                1.0,
                0.0
            ),
            'التصنيف المعروض يجب أن يقرأها غير محصَّلة.'
        );
    }
}
