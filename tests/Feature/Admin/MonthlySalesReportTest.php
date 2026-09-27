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
}
