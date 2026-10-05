<?php

namespace Tests\Feature\Api\V2;

use App\Services\SellerFinanceService;
use Database\Seeders\ApiTestingSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\ApiTestCase;

/**
 * الوضع المالي للمندوب.
 *
 * ثلاثة أرقام يخلط بينها كثيرًا: مديونية العملاء (مالٌ عندهم)،
 * والعهدة (مالٌ في يد المندوب)، والمعلّق (توريدٌ ينتظر الاعتماد).
 */
class SellerFinanceTest extends ApiTestCase
{
    private const SELLER = ApiTestingSeeder::SELLER_ID;

    protected function setUp(): void
    {
        parent::setUp();

        // أرضية نظيفة: الحساب يُقاس على ما يُدرَج هنا وحده.
        DB::table('orders')->where('owner_id', self::SELLER)->delete();
        DB::table('transaction_sellers')->where('seller_id', self::SELLER)->delete();
    }

    private function order(int $id, int $type, float $amount, float $collected = 0, ?int $parent = null): void
    {
        DB::table('orders')->insert([
            'id'                    => $id,
            'user_id'               => 90001,
            'owner_id'              => self::SELLER,
            'parent_id'             => $parent,
            'type'                  => $type,
            'order_amount'          => $amount,
            'transaction_reference' => $collected,
            'total_tax'             => 0,
            'update_flag'           => 0,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);
    }

    private function deposit(float $amount, int $status): void
    {
        DB::table('transaction_sellers')->insert([
            'seller_id'  => self::SELLER,
            'account_id' => 90001,
            'amount'     => $amount,
            'active'     => $status,
            'img'        => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function figures(): array
    {
        return app(SellerFinanceService::class)->forSeller(self::SELLER);
    }

    /** @test */
    public function the_endpoint_returns_the_three_groups(): void
    {
        $this->asSeller()->getJson('/api/v2/finance/summary')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'sales'      => ['count', 'invoiced', 'returned', 'net'],
                    'collection' => ['collected', 'outstanding', 'rate'],
                    'custody'    => ['in_hand', 'deposited', 'pending'],
                    'open_invoices',
                ],
            ]);
    }

    /** مديونية العملاء = الفواتير − المحصَّل − المرتجع. */
    public function test_what_customers_still_owe(): void
    {
        $this->order(950001, 4, 1000, 300);
        $this->order(950002, 4, 500, 0);
        $this->order(950003, 7, 200, 0, 950001);

        $f = $this->figures();

        $this->assertSame(1500.0, $f['sales']['invoiced']);
        $this->assertSame(200.0, $f['sales']['returned']);
        $this->assertSame(1300.0, $f['sales']['net']);
        $this->assertSame(300.0, $f['collection']['collected']);
        $this->assertSame(1000.0, $f['collection']['outstanding']);
    }

    /**
     * العهدة = ما قبضه − ما ورّده − المعلّق.
     *
     * المعلّق خرج من يده وإن لم يُعتمد بعد، فيُطرح ويُعرض وحده.
     *
     * @test
     */
    public function what_is_still_in_the_sellers_hands(): void
    {
        $this->order(950001, 4, 1000, 800);
        $this->deposit(500, 1);
        $this->deposit(100, 0);

        $f = $this->figures();

        $this->assertSame(800.0, $f['collection']['collected']);
        $this->assertSame(500.0, $f['custody']['deposited']);
        $this->assertSame(100.0, $f['custody']['pending']);
        $this->assertSame(200.0, $f['custody']['in_hand']);
    }

    /** التوريد المرفوض لا يُحتسب في شيء. */
    public function test_a_rejected_deposit_counts_for_nothing(): void
    {
        $this->order(950001, 4, 1000, 800);
        $this->deposit(500, 2);

        $f = $this->figures();

        $this->assertSame(0.0, $f['custody']['deposited']);
        $this->assertSame(0.0, $f['custody']['pending']);
        $this->assertSame(800.0, $f['custody']['in_hand']);
    }

    /** نسبة التحصيل من الصافي لا من الإجمالي: المرتجع لا يُحصَّل. */
    public function test_the_rate_excludes_returns(): void
    {
        $this->order(950001, 4, 1000, 400);
        $this->order(950002, 7, 200, 0, 950001);

        // 400 من 800 صافية = 50%، لا 40% من الألف.
        $this->assertSame(50.0, $this->figures()['collection']['rate']);
    }

    /** المديونية لا تصير سالبة بتحصيلٍ زائد. */
    public function test_outstanding_never_goes_negative(): void
    {
        $this->order(950001, 4, 1000, 1200);

        $this->assertSame(0.0, $this->figures()['collection']['outstanding']);
    }

    /** الفواتير المفتوحة: ما لم يُسدَّد بالكامل. */
    public function test_open_invoices_are_the_unsettled_ones(): void
    {
        $this->order(950001, 4, 1000, 1000);
        $this->order(950002, 4, 500, 100);
        $this->order(950003, 4, 300, 0);

        $this->assertSame(2, $this->figures()['open_invoices']);
    }

    /** لا يرى ملخّص غيره: النقطة تقرأ هويّته من الرمز. */
    public function test_a_seller_only_sees_their_own_figures(): void
    {
        $this->order(950001, 4, 1000, 400);

        DB::table('orders')->insert([
            'id' => 950099, 'user_id' => 90001, 'owner_id' => 900077,
            'type' => 4, 'order_amount' => 9999, 'transaction_reference' => 0,
            'total_tax' => 0, 'update_flag' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertSame(1000.0, $this->figures()['sales']['invoiced']);
    }

    /** @test */
    public function the_summary_needs_authentication(): void
    {
        $this->getJson('/api/v2/finance/summary')->assertStatus(401);
    }
}
