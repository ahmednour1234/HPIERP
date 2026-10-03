<?php

namespace Tests\Feature\Api\V2;

use App\Models\Order;
use App\Models\SellerHandover;
use Database\Seeders\ApiTestingSeeder;
use Tests\Feature\Api\ApiTestCase;

/**
 * تسليم عهدة مندوب إلى آخر.
 *
 * الشرط الأهم هنا ليس أن الميزة تعمل، بل ألّا تغيّر شيئًا قائمًا:
 * لا تُنقل ملكية فاتورة ولا يتبدّل رقم في تقرير سابق. ولهذا تُقاس
 * الأرقام قبل التسليم وبعده وتُقارن.
 */
class SellerHandoverTest extends ApiTestCase
{
    private const SELLER = ApiTestingSeeder::SELLER_ID;

    /** صاحب العهدة ومعه فاتورة، يُنشآن هنا لا في البيانات العامة. */
    private const OTHER = 900077;

    /**
     * مندوبٌ آخر غير مندوب الاختبار، ليكون صاحب العهدة.
     *
     * يُنشأ في الاختبار نفسه: الاعتماد على بيانات موجودة يجعل
     * الاختبار يُتخطّى بصمت حين تغيب، فلا يحمي شيئًا.
     */
    private function otherSellerId(): int
    {
        \Illuminate\Support\Facades\DB::table('admins')->updateOrInsert(
            ['id' => self::OTHER],
            [
                'local_id'    => self::OTHER,
                'f_name'      => 'Former',
                'l_name'      => 'Seller',
                'email'       => 'former.seller@example.test',
                'password'    => \Illuminate\Support\Facades\Hash::make('password'),
                'mandob_code' => 'TESTFORMER',
                'role'        => 'seller',
                'type'        => 'mandob',
                'company_id'  => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]
        );

        if (!Order::where('owner_id', self::OTHER)->exists()) {
            \Illuminate\Support\Facades\DB::table('orders')->insert([
                'id'           => 900077,
                'user_id'      => 90001,
                'owner_id'     => self::OTHER,
                'order_amount' => 500,
                'total_tax'    => 0,
                'update_flag'  => 0,
                'type'         => 1,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        return self::OTHER;
    }

    private function handFrom(int $fromSeller): SellerHandover
    {
        return SellerHandover::create([
            'from_seller_id' => $fromSeller,
            'to_seller_id'   => self::SELLER,
            'started_at'     => now()->subDay(),
        ]);
    }

    /** @test */
    public function with_no_handover_the_model_grants_nothing(): void
    {
        $this->assertSame([], SellerHandover::sourcesFor(self::SELLER));
        $this->assertFalse(SellerHandover::allows(self::SELLER, $this->otherSellerId()));
    }

    /** @test */
    public function an_active_handover_names_its_source(): void
    {
        $other = $this->otherSellerId();
        $this->handFrom($other);

        $this->assertSame([$other], SellerHandover::sourcesFor(self::SELLER));
        $this->assertTrue(SellerHandover::allows(self::SELLER, $other));
    }

    /** @test */
    public function ending_a_handover_withdraws_the_permission(): void
    {
        $other     = $this->otherSellerId();
        $handover  = $this->handFrom($other);

        $this->assertTrue(SellerHandover::allows(self::SELLER, $other));

        $handover->update(['ended_at' => now()]);

        // التاريخ يبقى، والصلاحية وحدها تنتهي.
        $this->assertFalse(SellerHandover::allows(self::SELLER, $other));
        $this->assertDatabaseHas('seller_handovers', ['id' => $handover->id]);
    }

    /** @test */
    public function a_handover_that_has_not_started_grants_nothing_yet(): void
    {
        $other = $this->otherSellerId();

        SellerHandover::create([
            'from_seller_id' => $other,
            'to_seller_id'   => self::SELLER,
            'started_at'     => now()->addWeek(),
        ]);

        $this->assertFalse(SellerHandover::allows(self::SELLER, $other));
    }

    /** @test */
    public function the_permission_runs_one_way_only(): void
    {
        $other = $this->otherSellerId();
        $this->handFrom($other);

        // استلم مندوبُنا عهدةَ الآخر، لا العكس.
        $this->assertTrue(SellerHandover::allows(self::SELLER, $other));
        $this->assertFalse(SellerHandover::allows($other, self::SELLER));
    }

    /**
     * لا أثر رجعي: التسليم لا يَنقل ملكية فاتورة.
     *
     * @test
     */
    public function a_handover_never_rewrites_invoice_ownership(): void
    {
        $other  = $this->otherSellerId();
        $before = Order::orderBy('id')->pluck('owner_id', 'id')->all();

        $this->handFrom($other);

        $after = Order::orderBy('id')->pluck('owner_id', 'id')->all();

        $this->assertSame($before, $after, 'ملكية الفواتير يجب أن تبقى كما هي بعد التسليم.');
    }

    /**
     * لا أثر رجعي: أرقام المندوب لا تتغير بعد التسليم.
     *
     * لوحته وقائمة فواتيره تُحسبان على owner_id، فلو اتسعت لتشمل
     * فواتير العهدة لتبدّلت أرقام تاريخية بأثر رجعي. هذا الاختبار
     * هو ما يمنع ذلك.
     *
     * @test
     */
    public function a_handover_does_not_change_the_sellers_own_figures(): void
    {
        $other = $this->otherSellerId();

        $ordersBefore    = $this->asSeller()->getJson('/api/v2/orders');
        $dashboardBefore = $this->asSeller()->getJson('/api/v2/dashboard');

        $ordersBefore->assertOk();

        $this->handFrom($other);

        $ordersAfter    = $this->asSeller()->getJson('/api/v2/orders');
        $dashboardAfter = $this->asSeller()->getJson('/api/v2/dashboard');

        $this->assertSame(
            $ordersBefore->json('meta.total'),
            $ordersAfter->json('meta.total'),
            'عدد فواتير المندوب يجب ألّا يتغير بالتسليم.'
        );

        $this->assertSame(
            collect($ordersBefore->json('data'))->pluck('id')->all(),
            collect($ordersAfter->json('data'))->pluck('id')->all(),
            'قائمة فواتير المندوب يجب ألّا تتغير بالتسليم.'
        );

        if ($dashboardBefore->status() === 200) {
            $this->assertSame(
                $dashboardBefore->json('data'),
                $dashboardAfter->json('data'),
                'أرقام لوحة المندوب يجب ألّا تتغير بالتسليم.'
            );
        }
    }

    /**
     * لا أثر رجعي: فواتير صاحب العهدة تبقى محسوبةً له.
     *
     * @test
     */
    public function the_original_owner_keeps_their_invoices(): void
    {
        $other  = $this->otherSellerId();
        $before = Order::where('owner_id', $other)->count();

        $this->handFrom($other);

        $this->assertSame(
            $before,
            Order::where('owner_id', $other)->count(),
            'فواتير صاحب العهدة يجب أن تبقى محسوبةً عليه.'
        );
    }
}
