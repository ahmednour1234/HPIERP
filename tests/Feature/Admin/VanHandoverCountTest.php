<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\Stock;
use App\Models\VanHandoverCount;
use App\Services\VanHandoverCountService;
use Database\Seeders\ApiTestingSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\ApiTestCase;

/**
 * جرد تسليم عربية مندوب انتهت خدمته.
 *
 * الفرق يُحفظ كميةً فقط: التسعير قرار إداري خارج النظام.
 */
class VanHandoverCountTest extends ApiTestCase
{
    private const SELLER = ApiTestingSeeder::SELLER_ID;
    private const ADMIN  = ApiTestingSeeder::ADMIN_ID;

    private function service(): VanHandoverCountService
    {
        return app(VanHandoverCountService::class);
    }

    /** يضع رصيدًا في العربية ويعيد رصيد المخزن قبل الجرد. */
    private function van(int $productId, int $stock): int
    {
        // الصف مزروع أصلًا، فيُحدَّث لا يُضاف: إضافة صفٍّ ثانٍ لنفس
        // المنتج تجعل العربية تحمل رصيدين.
        DB::table('stocks')
            ->where('seller_id', self::SELLER)
            ->where('product_id', $productId)
            ->update(['stock' => $stock, 'main_stock' => $stock]);

        return (int) Product::whereKey($productId)->value('quantity');
    }

    /** @test */
    public function the_van_listing_shows_what_the_records_hold(): void
    {
        $this->van(90001, 40);

        $rows = $this->service()->vanStock(self::SELLER);

        $this->assertSame(40, (int) $rows->firstWhere('product_id', 90001)->stock);
    }

    /** @test */
    public function a_shortage_is_recorded_as_a_negative_difference(): void
    {
        $this->van(90001, 40);

        $count = $this->service()->open(self::SELLER, [90001 => 35]);
        $item  = $count->items->firstWhere('product_id', 90001);

        $this->assertSame(40, $item->expected);
        $this->assertSame(35, $item->counted);
        $this->assertSame(-5, $item->difference);
        $this->assertSame(5, $count->shortage());
        $this->assertSame(0, $count->surplus());
    }

    /** @test */
    public function a_surplus_is_recorded_as_a_positive_difference(): void
    {
        $this->van(90001, 40);

        $count = $this->service()->open(self::SELLER, [90001 => 42]);

        $this->assertSame(2, $count->items->firstWhere('product_id', 90001)->difference);
        $this->assertSame(2, $count->surplus());
        $this->assertSame(0, $count->shortage());
    }

    /** @test */
    public function a_product_left_unwritten_counts_as_nothing_delivered(): void
    {
        $this->van(90001, 40);

        // لم يُكتب رقم لهذا الصنف إطلاقًا.
        $count = $this->service()->open(self::SELLER, []);

        $item = $count->items->firstWhere('product_id', 90001);

        $this->assertSame(0, $item->counted);
        $this->assertSame(-40, $item->difference, 'تخطّي الصنف لا يعني أنه سُلِّم.');
    }

    /** @test */
    public function the_count_does_not_move_stock_before_approval(): void
    {
        $before = $this->van(90001, 40);

        $this->service()->open(self::SELLER, [90001 => 35]);

        $this->assertSame(40, (int) Stock::where('seller_id', self::SELLER)
            ->where('product_id', 90001)->value('stock'));
        $this->assertSame($before, (int) Product::whereKey(90001)->value('quantity'));
    }

    /**
     * الاعتماد: المسلَّم فعلًا يدخل المخزن، والعجز لا يدخل.
     *
     * @test
     */
    public function approval_returns_only_what_was_actually_delivered(): void
    {
        $before = $this->van(90001, 40);

        $count = $this->service()->open(self::SELLER, [90001 => 35]);
        $this->service()->approve($count->id, self::ADMIN);

        $this->assertSame(
            $before + 35,
            (int) Product::whereKey(90001)->value('quantity'),
            'يدخل المخزن ما سُلِّم فعلًا لا ما كان مسجَّلًا.'
        );
    }

    /**
     * العربية تُفرَّغ: العجز بضاعة مفقودة لا باقية على مندوبٍ مشى.
     *
     * @test
     */
    public function approval_empties_the_van(): void
    {
        $this->van(90001, 40);

        $count = $this->service()->open(self::SELLER, [90001 => 35]);
        $this->service()->approve($count->id, self::ADMIN);

        $this->assertSame(
            0,
            (int) Stock::where('seller_id', self::SELLER)->where('product_id', 90001)->value('stock'),
            'العجز يجب ألّا يبقى مسجَّلًا على العربية فيظهر في كل جرد لاحق.'
        );
    }

    /** @test */
    public function the_difference_survives_approval(): void
    {
        $this->van(90001, 40);

        $count = $this->service()->open(self::SELLER, [90001 => 35]);
        $this->service()->approve($count->id, self::ADMIN);

        $fresh = VanHandoverCount::with('items')->find($count->id);

        $this->assertSame(5, $fresh->shortage(), 'العجز يُقرأ بعد الاعتماد كما سُجِّل.');
        $this->assertSame(40, $fresh->items->first()->expected, 'اللقطة محفوظة لا تُعاد قراءتها.');
    }

    /** @test */
    public function rejection_leaves_the_stock_alone(): void
    {
        $before = $this->van(90001, 40);

        $count = $this->service()->open(self::SELLER, [90001 => 35]);
        $this->service()->reject($count->id, self::ADMIN, 'إعادة الجرد');

        $this->assertSame(40, (int) Stock::where('seller_id', self::SELLER)
            ->where('product_id', 90001)->value('stock'));
        $this->assertSame($before, (int) Product::whereKey(90001)->value('quantity'));
        $this->assertSame(
            VanHandoverCount::STATUS_REJECTED,
            VanHandoverCount::find($count->id)->status
        );
    }

    /** @test */
    public function a_count_cannot_be_reviewed_twice(): void
    {
        $this->van(90001, 40);
        $count = $this->service()->open(self::SELLER, [90001 => 35]);

        $this->service()->approve($count->id, self::ADMIN);

        $this->expectException(\RuntimeException::class);
        $this->service()->approve($count->id, self::ADMIN);
    }

    /** @test */
    public function a_second_pending_count_is_refused(): void
    {
        $this->van(90001, 40);
        $this->service()->open(self::SELLER, [90001 => 35]);

        $this->expectException(\RuntimeException::class);
        $this->service()->open(self::SELLER, [90001 => 30]);
    }

    /** @test */
    public function a_negative_delivered_quantity_is_refused(): void
    {
        $this->van(90001, 40);

        $this->expectException(\InvalidArgumentException::class);
        $this->service()->open(self::SELLER, [90001 => -1]);
    }

    /**
     * الفرق كمية لا مال: لا عمود مبلغ في الجدول.
     *
     * @test
     */
    public function the_shortage_is_never_priced(): void
    {
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('van_handover_count_items');

        foreach (['amount', 'price', 'value', 'total'] as $money) {
            $this->assertNotContains($money, $columns, 'الجرد كميات فقط.');
        }
    }

    /** @test */
    public function approving_an_exact_count_leaves_no_difference(): void
    {
        $before = $this->van(90001, 40);

        $count = $this->service()->open(self::SELLER, [90001 => 40]);
        $this->service()->approve($count->id, self::ADMIN);

        $this->assertSame(0, VanHandoverCount::find($count->id)->load('items')->shortage());
        $this->assertSame($before + 40, (int) Product::whereKey(90001)->value('quantity'));
    }
}
