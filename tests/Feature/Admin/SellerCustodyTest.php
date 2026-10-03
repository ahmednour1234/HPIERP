<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Database\Seeders\ApiTestingSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\ApiTestCase;

/**
 * شاشة عهدة المناديب.
 *
 * شاشة قراءة: تحسب العهدة من سجلّاتها وتعرض الفرق عن admins.credit،
 * ولا تكتب شيئًا. أهم ما تثبته الاختبارات هنا أنها لا تُعدِّل رصيدًا.
 */
class SellerCustodyTest extends ApiTestCase
{
    private const SELLER = ApiTestingSeeder::SELLER_ID;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('admins')->where('id', ApiTestingSeeder::ADMIN_ID)->update(['is_super' => 1]);
        $this->actingAs(Admin::find(ApiTestingSeeder::ADMIN_ID), 'admin');
    }

    /** @return \Illuminate\Support\Collection<int,array> */
    private function rows()
    {
        $request = Request::create('/', 'GET');
        app()->instance('request', $request);

        return app(\App\Http\Controllers\Admin\SellerCustodyController::class)
            ->index($request)->getData()['rows'];
    }

    private function row(int $sellerId): ?array
    {
        return $this->rows()->firstWhere('id', $sellerId);
    }

    private function instalment(float $amount): void
    {
        DB::table('installments')->insert([
            'seller_id'   => self::SELLER,
            'customer_id' => 90001,
            'order_id'    => 940001,
            'total_price' => $amount,
            'note'        => '',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    private function deposit(float $amount, int $status): void
    {
        DB::table('transaction_sellers')->insert([
            'seller_id'  => self::SELLER,
            'account_id' => DB::table('accounts')->value('id') ?? 1,
            'amount'     => $amount,
            'active'     => $status,
            'img'        => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function the_screen_opens(): void
    {
        $this->get(route('admin.handover.custody'))
            ->assertOk()
            ->assertSee('عهدة المناديب');
    }

    /** @test */
    public function custody_is_collections_minus_approved_deposits(): void
    {
        $this->instalment(1000);
        $this->deposit(400, 1);

        $row = $this->row(self::SELLER);

        $this->assertSame(1000.0, $row['instalments']);
        $this->assertSame(400.0, $row['deposited']);
        $this->assertSame(round($row['collected'] - 400, 2), $row['expected']);
    }

    /**
     * الإيداع المعلّق لا يُخصم: لم يصل بعد.
     *
     * @test
     */
    public function a_pending_deposit_is_shown_but_not_deducted(): void
    {
        $this->instalment(1000);
        $this->deposit(300, 0);

        $row = $this->row(self::SELLER);

        $this->assertSame(300.0, $row['pending']);
        $this->assertSame(0.0, $row['deposited']);
        $this->assertSame($row['collected'], $row['expected']);
    }

    /** الإيداع المرفوض لا يُحتسب إطلاقًا. */
    public function test_a_rejected_deposit_counts_for_nothing(): void
    {
        $this->instalment(1000);
        $this->deposit(500, 2);

        $row = $this->row(self::SELLER);

        $this->assertSame(0.0, $row['deposited']);
        $this->assertSame(0.0, $row['pending']);
    }

    /**
     * الفرق هو المسجَّل ناقص المتوقع.
     *
     * @test
     */
    public function the_gap_compares_the_recorded_balance_to_the_computed_one(): void
    {
        $this->instalment(1000);
        DB::table('admins')->where('id', self::SELLER)->update(['credit' => 250]);

        $row = $this->row(self::SELLER);

        $this->assertSame(250.0, $row['recorded']);
        $this->assertSame(round(250 - $row['expected'], 2), $row['gap']);
    }

    /**
     * الشاشة لا تكتب شيئًا.
     *
     * هذا هو شرطها الأهم: أرصدة العهدة مالٌ على أشخاص، وتصحيحها
     * آليًّا من حسابٍ قد يغفل تسويةً يدويةً خطأ.
     *
     * @test
     */
    public function the_screen_never_writes_a_balance(): void
    {
        $this->instalment(5000);
        DB::table('admins')->where('id', self::SELLER)->update(['credit' => 7, 'commission' => 9]);

        $this->get(route('admin.handover.custody'))->assertOk();

        $seller = DB::table('admins')->where('id', self::SELLER)->first(['credit', 'commission']);

        $this->assertSame(7.0, (float) $seller->credit);
        $this->assertSame(9.0, (float) $seller->commission);
    }

    /**
     * لا يُستعمل tran_type = 26 في الحساب.
     *
     * يكتبه ستة مواضع مختلفة، فمجموعه يفوق إجمالي المبيعات كلها.
     * صفٌّ منه يجب ألّا يحرّك رقمًا على هذه الشاشة.
     *
     * @test
     */
    public function a_transection_row_does_not_affect_the_figures(): void
    {
        $this->instalment(1000);
        $before = $this->row(self::SELLER);

        DB::table('transections')->insert([
            'tran_type'  => '26',
            'seller_id'  => self::SELLER,
            'amount'     => 99999,
            'debit'      => 0,
            'credit'     => 0,
            'date'       => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame($before['expected'], $this->row(self::SELLER)['expected']);
    }
}
