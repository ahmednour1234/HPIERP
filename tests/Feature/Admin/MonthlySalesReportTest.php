<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\MonthlySalesReportController;
use App\Models\Admin;
use App\Models\Stock;
use Carbon\Carbon;
use Database\Seeders\ApiTestingSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use Tests\Feature\Api\ApiTestCase;

class MonthlySalesReportTest extends ApiTestCase
{
    public function test_stock_summary_uses_month_invoices_and_adds_returns_to_remaining(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 15, 10));

        try {
            $this->actingAs(Admin::find(ApiTestingSeeder::ADMIN_ID), 'admin');

            Stock::where('seller_id', ApiTestingSeeder::SELLER_ID)
                ->where('product_id', 90001)
                ->update(['main_stock' => 100, 'stock' => 75]);

            $this->insertOrderWithLine(910001, 4, 1, 20, '2026-09-04 10:00:00');
            $this->insertOrderWithLine(910002, 4, 2, 10, '2026-09-05 10:00:00');
            $this->insertOrderWithLine(910003, 7, 2, 5, '2026-09-06 10:00:00', 910001);

            $data = $this->buildReport(['month' => '2026-09']);

            $this->assertSame(30, (int) $data['totals']['stock']['supplied'][90001]);
            $this->assertSame(75, (int) $data['totals']['stock']['closing'][90001]);

            $regionStock = $data['perRegion'][0]['stock'];
            $this->assertSame(30, (int) $regionStock['supplied'][90001]);
            $this->assertSame(75, (int) $regionStock['closing'][90001]);
        } finally {
            Carbon::setTestNow();
        }
    }

    private function buildReport(array $query): array
    {
        $controller = new MonthlySalesReportController();
        $method = (new ReflectionClass($controller))->getMethod('build');
        $method->setAccessible(true);

        return $method->invoke($controller, Request::create('/admin/reports/monthly-sales', 'GET', $query));
    }

    private function insertOrderWithLine(
        int $id,
        int $type,
        int $cash,
        int $quantity,
        string $createdAt,
        ?int $parentId = null
    ): void {
        DB::table('orders')->insert([
            'id' => $id,
            'user_id' => 90001,
            'owner_id' => ApiTestingSeeder::SELLER_ID,
            'parent_id' => $parentId,
            'type' => $type,
            'cash' => $cash,
            'total_tax' => 0,
            'order_amount' => $quantity * 75,
            'collected_cash' => $quantity * 75,
            'transaction_reference' => $quantity * 75,
            'update_flag' => 0,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        DB::table('order_details')->insert([
            'order_id' => $id,
            'product_id' => 90001,
            'quantity' => $quantity,
            'price' => 75,
            'tax_amount' => 0,
            'discount_on_product' => 0,
            'discount_type' => 'amount',
            'update_flag' => 0,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    /**
     * فلتر القسم يحذف الجانب غير المطلوب من الحساب والعرض.
     *
     * لا يُخفيه بعد حسابه: الجداول الغائبة يجب ألّا تُبنى أصلًا، وإلا بقي
     * التقرير بطيئًا بلا سبب.
     */
    public function test_the_section_filter_drops_the_other_side(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 15, 10));

        try {
            $this->actingAs(Admin::find(ApiTestingSeeder::ADMIN_ID), 'admin');

            $build = (new ReflectionClass(MonthlySalesReportController::class))
                ->getMethod('build');
            $build->setAccessible(true);

            $controller = app(MonthlySalesReportController::class);

            $all = $build->invoke($controller, Request::create('/', 'GET', ['section' => 'all']));
            $this->assertTrue($all['wantStock']);
            $this->assertTrue($all['wantSales']);

            $stock = $build->invoke($controller, Request::create('/', 'GET', ['section' => 'stock']));
            $this->assertTrue($stock['wantStock']);
            $this->assertFalse($stock['wantSales']);
            $this->assertSame([], $stock['totals']['sales']);
            // التحصيلات جزء من جانب المبيعات.
            $this->assertSame([], $stock['collections']);

            $sales = $build->invoke($controller, Request::create('/', 'GET', ['section' => 'sales']));
            $this->assertFalse($sales['wantStock']);
            $this->assertTrue($sales['wantSales']);
            $this->assertSame([], $sales['totals']['stock']);
        } finally {
            Carbon::setTestNow();
        }
    }

    /** بلا اختيار يبقى التقرير كاملًا كما كان. */
    public function test_it_shows_both_sides_by_default(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 15, 10));

        try {
            $this->actingAs(Admin::find(ApiTestingSeeder::ADMIN_ID), 'admin');

            $build = (new ReflectionClass(MonthlySalesReportController::class))->getMethod('build');
            $build->setAccessible(true);

            $data = $build->invoke(app(MonthlySalesReportController::class), Request::create('/', 'GET'));

            $this->assertSame('all', $data['section']);
            $this->assertTrue($data['wantStock']);
            $this->assertTrue($data['wantSales']);
        } finally {
            Carbon::setTestNow();
        }
    }

    /** قيمة غير معروفة تُرفض بالتحقّق بدل أن تُفسَّر تفسيرًا صامتًا. */
    public function test_an_unknown_section_is_rejected(): void
    {
        $build = (new ReflectionClass(MonthlySalesReportController::class))->getMethod('build');
        $build->setAccessible(true);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $build->invoke(
            app(MonthlySalesReportController::class),
            Request::create('/', 'GET', ['section' => 'nonsense'])
        );
    }

    /**
     * قسطٌ يقبضه مندوبٌ عن فاتورة زميله يظل محسوبًا في التقرير.
     *
     * كان الترشيح على installments.seller_id بينما الفواتير تُجمع
     * بـ owner_id، فيسقط هذا القسط من التقرير كله. وبعد ترحيل العهدة
     * يصير هذا هو الحال الغالب لا الاستثناء.
     */
    public function test_a_collection_taken_by_another_seller_still_counts(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 15, 10));

        try {
            $this->actingAs(Admin::find(ApiTestingSeeder::ADMIN_ID), 'admin');

            $this->insertOrderWithLine(910101, 4, 2, 10, '2026-09-04 10:00:00');

            // الفاتورة لمندوبنا، والقسط قبضه غيره.
            DB::table('installments')->insert([
                'seller_id'   => 900077,
                'customer_id' => 90001,
                'order_id'    => 910101,
                'total_price' => 300,
                'note'        => '',
                'insert_flag' => 0,
                'update_flag' => 0,
                'active'      => 1,
                'created_at'  => '2026-09-10 10:00:00',
                'updated_at'  => '2026-09-10 10:00:00',
            ]);

            $data = $this->report(['month' => '2026-09']);
            $sales = $data['totals']['sales'];

            $this->assertSame(
                300.0,
                round($this->sumRow($sales, 'collected_by_others'), 2),
                'القسط الذي قبضه مندوب آخر يجب أن يظهر مفصولًا.'
            );

            // وهو الإصلاح الأصلي: كان الترشيح بـ seller_id يُسقط
            // المبلغ من صفوف التحصيل نفسها، فلا يظهر في التقرير
            // أصلًا. يقع هذا التوكيد إن عاد الترشيح القديم.
            $this->assertSame(
                300.0,
                round($this->sumRow($sales, 'collected_month'), 2),
                'القسط يجب أن يُحتسب في المحصل من الآجل، لا أن يسقط.'
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    /** ما يقبضه صاحب الفاتورة نفسه لا يُعدّ تحصيل غيره. */
    public function test_the_owners_own_collection_is_not_flagged_as_someone_elses(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 15, 10));

        try {
            $this->actingAs(Admin::find(ApiTestingSeeder::ADMIN_ID), 'admin');

            $this->insertOrderWithLine(910102, 4, 2, 10, '2026-09-04 10:00:00');

            DB::table('installments')->insert([
                'seller_id'   => ApiTestingSeeder::SELLER_ID,
                'customer_id' => 90001,
                'order_id'    => 910102,
                'total_price' => 300,
                'note'        => '',
                'insert_flag' => 0,
                'update_flag' => 0,
                'active'      => 1,
                'created_at'  => '2026-09-10 10:00:00',
                'updated_at'  => '2026-09-10 10:00:00',
            ]);

            $sales = $this->report(['month' => '2026-09'])['totals']['sales'];

            $this->assertSame(0.0, round($this->sumRow($sales, 'collected_by_others'), 2));
        } finally {
            Carbon::setTestNow();
        }
    }

    /** الصف الجديد له تسمية، وإلا لم يُعرض في القالب. */
    public function test_the_new_row_is_labelled(): void
    {
        $this->assertArrayHasKey(
            'collected_by_others',
            MonthlySalesReportController::salesLabels()
        );
    }

    /** @return array<string,mixed> */
    private function report(array $query): array
    {
        $build = (new ReflectionClass(MonthlySalesReportController::class))->getMethod('build');
        $build->setAccessible(true);

        return $build->invoke(
            app(MonthlySalesReportController::class),
            Request::create('/', 'GET', $query)
        );
    }

    private function sumRow(array $sales, string $key): float
    {
        $sum = 0.0;

        foreach ($sales[$key] ?? [] as $cell) {
            $sum += (float) ($cell['amount'] ?? 0);
        }

        return $sum;
    }

    /**
     * المشرف العام يرى كل المناديب.
     *
     * كان النطاق من admin_sellers وحده، ومدير النظام ليس مُسنَدًا إلى
     * أحد فيها. أعمدة المنتجات تُبنى من حركة هؤلاء المناديب، فتخرج
     * فارغةً وتصير كل صفوف التقرير أصفارًا في شهرٍ فيه مئات الفواتير.
     */
    public function test_a_super_admin_sees_every_sellers_movement(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 15, 10));

        try {
            // مدير نظام بلا أي سطر في admin_sellers.
            DB::table('admins')->where('id', ApiTestingSeeder::ADMIN_ID)->update(['is_super' => 1]);
            DB::table('admin_sellers')->where('admin_id', ApiTestingSeeder::ADMIN_ID)->delete();

            $this->actingAs(Admin::find(ApiTestingSeeder::ADMIN_ID), 'admin');

            $this->insertOrderWithLine(915001, 4, 1, 12, '2026-09-04 10:00:00');

            $data = $this->report(['month' => '2026-09']);

            $this->assertNotEmpty(
                $data['products'],
                'حركة المناديب يجب أن تبني أعمدة المنتجات لمدير النظام.'
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    /** ومن ليس مشرفًا عامًّا يبقى محصورًا في مناديبه. */
    public function test_a_plain_admin_stays_scoped_to_their_sellers(): void
    {
        $reflection = new ReflectionClass(MonthlySalesReportController::class);
        $method = $reflection->getMethod('sellerIds');
        $method->setAccessible(true);

        DB::table('admins')->where('id', ApiTestingSeeder::ADMIN_ID)->update(['is_super' => 0]);
        DB::table('admin_sellers')->where('admin_id', ApiTestingSeeder::ADMIN_ID)->delete();

        $this->actingAs(Admin::find(ApiTestingSeeder::ADMIN_ID), 'admin');

        $ids = $method->invoke(app(MonthlySalesReportController::class), []);

        // حسابه هو فقط، لا كل المناديب.
        $this->assertSame([ApiTestingSeeder::ADMIN_ID], $ids);
    }
}
