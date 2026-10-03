<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\OrderOwnerLog;
use App\Services\OrderOwnershipTransferService;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\ApiTestCase;

/**
 * ترحيل فواتير مندوب إلى آخر.
 *
 * الترحيل يكتب فوق orders.owner_id عمدًا، ليحصّلها الخلف من التطبيق
 * دون تعديل فيه. ولهذا يدور أكثر الاختبارات هنا حول سؤال واحد: هل
 * بقي أصل الفاتورة معروفًا بعد أن كُتب فوق العمود الذي كان يحمله؟
 */
class OrderOwnershipTransferTest extends ApiTestCase
{
    private const FROM = 900077;
    private const TO   = 900078;

    private function service(): OrderOwnershipTransferService
    {
        return app(OrderOwnershipTransferService::class);
    }

    private function seller(int $id, string $name): void
    {
        DB::table('admins')->updateOrInsert(['id' => $id], [
            'local_id'    => $id,
            'f_name'      => $name,
            'l_name'      => 'Seller',
            'email'       => "seller{$id}@example.test",
            'password'    => \Illuminate\Support\Facades\Hash::make('password'),
            'mandob_code' => "CODE{$id}",
            'role'        => 'seller',
            'type'        => 'mandob',
            'company_id'  => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    /** فاتورة لصاحبٍ معيّن. $collected يساوي المبلغ يجعلها مسدَّدة. */
    private function order(int $id, int $owner, float $amount = 500, float $collected = 0): void
    {
        DB::table('orders')->updateOrInsert(['id' => $id], [
            'user_id'               => 90001,
            'owner_id'              => $owner,
            'order_amount'          => $amount,
            'transaction_reference' => $collected,
            'total_tax'             => 0,
            'update_flag'           => 0,
            'type'                  => 1,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller(self::FROM, 'Former');
        $this->seller(self::TO, 'Successor');
    }

    /** @test */
    public function it_moves_the_invoices_to_the_new_seller(): void
    {
        $this->order(900101, self::FROM);
        $this->order(900102, self::FROM);

        $moved = $this->service()->transfer(self::FROM, self::TO);

        $this->assertSame(2, $moved);
        $this->assertSame(self::TO, (int) Order::find(900101)->owner_id);
        $this->assertSame(self::TO, (int) Order::find(900102)->owner_id);
    }

    /**
     * الأصل يبقى معروفًا بعد الكتابة فوق العمود الذي كان يحمله.
     *
     * @test
     */
    public function the_original_owner_survives_the_transfer(): void
    {
        $this->order(900101, self::FROM);

        $this->service()->transfer(self::FROM, self::TO);

        // العمود صار يحمل الخلف.
        $this->assertSame(self::TO, (int) Order::find(900101)->owner_id);

        // والأصل يُقرأ من السجلّ.
        $this->assertSame(self::FROM, OrderOwnerLog::originalOwnerOf(900101));
    }

    /** @test */
    public function the_original_survives_a_second_transfer(): void
    {
        $third = 900079;
        $this->seller($third, 'Third');
        $this->order(900101, self::FROM);

        $this->service()->transfer(self::FROM, self::TO);
        $this->service()->transfer(self::TO, $third);

        $this->assertSame($third, (int) Order::find(900101)->owner_id);

        // الأصل هو الأول لا الوسيط.
        $this->assertSame(self::FROM, OrderOwnerLog::originalOwnerOf(900101));
        $this->assertSame(2, OrderOwnerLog::where('order_id', 900101)->count());
    }

    /** @test */
    public function the_batch_lookup_agrees_with_the_single_one(): void
    {
        $this->order(900101, self::FROM);
        $this->order(900102, self::FROM);

        $this->service()->transfer(self::FROM, self::TO);

        $this->assertSame(
            [900101 => self::FROM, 900102 => self::FROM],
            OrderOwnerLog::originalOwnersFor([900101, 900102])
        );
    }

    /** @test */
    public function a_paid_invoice_is_left_alone(): void
    {
        $this->order(900101, self::FROM, 500, 500);
        $this->order(900102, self::FROM, 500, 0);

        $moved = $this->service()->transfer(self::FROM, self::TO);

        $this->assertSame(1, $moved, 'المسدَّدة لا تُرحَّل افتراضيًّا.');
        $this->assertSame(self::FROM, (int) Order::find(900101)->owner_id);
        $this->assertSame(self::TO, (int) Order::find(900102)->owner_id);
    }

    /** @test */
    public function a_return_is_never_transferred(): void
    {
        $this->order(900101, self::FROM);
        DB::table('orders')->where('id', 900101)->update(['type' => 7]);

        $this->assertSame(0, $this->service()->transfer(self::FROM, self::TO));
        $this->assertSame(self::FROM, (int) Order::find(900101)->owner_id);
    }

    /** @test */
    public function it_can_transfer_named_invoices_only(): void
    {
        $this->order(900101, self::FROM);
        $this->order(900102, self::FROM);

        $moved = $this->service()->transfer(self::FROM, self::TO, [900101]);

        $this->assertSame(1, $moved);
        $this->assertSame(self::TO, (int) Order::find(900101)->owner_id);
        $this->assertSame(self::FROM, (int) Order::find(900102)->owner_id);
    }

    /** @test */
    public function a_transfer_can_be_undone(): void
    {
        $this->order(900101, self::FROM);
        $this->order(900102, self::FROM);

        $this->service()->transfer(self::FROM, self::TO, null, 555);

        $this->assertSame(2, $this->service()->undo(555));

        $this->assertSame(self::FROM, (int) Order::find(900101)->owner_id);
        $this->assertSame(self::FROM, (int) Order::find(900102)->owner_id);
        $this->assertSame(0, OrderOwnerLog::where('handover_id', 555)->count());
    }

    /** @test */
    public function undoing_skips_an_invoice_that_moved_on_since(): void
    {
        $third = 900079;
        $this->seller($third, 'Third');
        $this->order(900101, self::FROM);

        $this->service()->transfer(self::FROM, self::TO, null, 555);
        $this->service()->transfer(self::TO, $third);

        $this->service()->undo(555);

        // رُحِّلت بعدها، فلا يُتراجَع عنها: آخر نقل ليس هذا.
        $this->assertSame($third, (int) Order::find(900101)->owner_id);
    }

    /** @test */
    public function transferring_to_oneself_is_refused(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service()->transfer(self::FROM, self::FROM);
    }

    /** @test */
    public function nothing_is_logged_when_nothing_moves(): void
    {
        $this->assertSame(0, $this->service()->transfer(self::FROM, self::TO));
        $this->assertSame(0, OrderOwnerLog::count());
    }

    /**
     * التحصيلات لا تُنسب إلى الخلف بأثر رجعي.
     *
     * transections.seller_id يسجّل من قبض فعلًا، والترحيل لا يمسّه:
     * ما حصّله السلف يبقى له.
     *
     * @test
     */
    public function past_collections_stay_with_whoever_took_them(): void
    {
        $this->order(900101, self::FROM);

        DB::table('transections')->insert([
            'tran_type'  => 'Receivable',
            'seller_id'  => self::FROM,
            'order_id'   => 900101,
            'amount'     => 100,
            'debit'      => 1,
            'credit'     => 0,
            'date'       => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->service()->transfer(self::FROM, self::TO);

        $this->assertSame(
            self::FROM,
            (int) DB::table('transections')->where('order_id', 900101)->value('seller_id'),
            'تحصيل السلف يبقى منسوبًا إليه بعد الترحيل.'
        );
    }
}
