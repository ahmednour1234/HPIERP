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
}
