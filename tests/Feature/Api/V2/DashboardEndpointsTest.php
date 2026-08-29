<?php

namespace Tests\Feature\Api\V2;

use App\Models\Order;
use App\Models\Product;
use App\Models\Stock;
use Database\Seeders\ApiTestingSeeder;
use Tests\Feature\Api\ApiTestCase;

/**
 * Dashboard figures and sub-categories.
 */
class DashboardEndpointsTest extends ApiTestCase
{
    private function placeSale(int $qty = 2): void
    {
        $this->asSeller()->postJson('/api/v2/orders', [
            'user_id' => 90001, 'order_type' => 4,
            'cart' => [['id' => 90001, 'quantity' => $qty, 'price' => 75]],
        ])->assertStatus(201);
    }

    public function test_summary_reports_the_sellers_own_figures(): void
    {
        $this->placeSale(2);

        $response = $this->asSeller()->getJson('/api/v2/dashboard/summary')->assertOk();

        $response->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'period' => ['from', 'to'],
                    'sales' => ['count', 'amount'],
                    'returns' => ['count', 'amount'],
                    'net_sales', 'collected', 'visits', 'stock_value', 'low_stock_products',
                ],
            ]);

        $this->assertSame(1, $response->json('data.sales.count'));
    }

    public function test_summary_excludes_other_sellers_orders(): void
    {
        $this->placeSale(1);

        // An order owned by somebody else must not count towards these figures.
        Order::create([
            'owner_id' => 999999, 'user_id' => 90001, 'type' => 4,
            'total_tax' => 0, 'order_amount' => 5000, 'update_flag' => 0,
        ]);

        // v1 summed the whole company, so this assertion would have failed there.
        $this->assertSame(
            1,
            $this->asSeller()->getJson('/api/v2/dashboard/summary')->json('data.sales.count')
        );
    }

    public function test_summary_accepts_a_date_range_and_rejects_a_reversed_one(): void
    {
        $this->asSeller()
            ->getJson('/api/v2/dashboard/summary?from=2026-01-01&to=2026-12-31')
            ->assertOk()
            ->assertJsonPath('data.period.from', '2026-01-01');

        $this->asSeller()
            ->getJson('/api/v2/dashboard/summary?from=2026-12-31&to=2026-01-01')
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['to']]);
    }

    public function test_monthly_revenue_returns_a_series_without_mysql_only_sql(): void
    {
        $this->placeSale(1);

        // The v1 equivalent used YEAR()/MONTH() and could not run here at all.
        $response = $this->asSeller()
            ->getJson('/api/v2/dashboard/monthly-revenue?months=3')
            ->assertOk();

        $this->assertCount(3, $response->json('data'));
        $response->assertJsonStructure(['data' => [['month', 'sales', 'returns', 'orders']]]);
    }

    public function test_monthly_revenue_bounds_the_window(): void
    {
        $this->asSeller()->getJson('/api/v2/dashboard/monthly-revenue?months=99')
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['months']]);
    }

    public function test_top_products_ranks_by_quantity_sold(): void
    {
        $this->placeSale(4);

        $response = $this->asSeller()->getJson('/api/v2/dashboard/top-products')->assertOk();

        $response->assertJsonPath('data.0.id', 90001)
            ->assertJsonStructure(['data' => [['id', 'name', 'product_code', 'quantity', 'amount']]]);

        $this->assertEquals(4, $response->json('data.0.quantity'));
    }

    public function test_low_stock_lists_products_under_their_threshold(): void
    {
        Product::where('id', 90001)->update(['limit_stock' => 10]);
        Stock::where('seller_id', ApiTestingSeeder::SELLER_ID)
            ->where('product_id', 90001)->update(['stock' => 3]);

        $this->asSeller()->getJson('/api/v2/dashboard/low-stock')
            ->assertOk()
            ->assertJsonPath('data.0.id', 90001);

        // Numeric compare: SQLite hands back an int where MySQL gives a float.
        $this->assertEquals(3, $this->asSeller()->getJson('/api/v2/dashboard/low-stock')->json('data.0.stock'));
    }

    /* ---------------- sub-categories ---------------- */

    public function test_a_sub_category_is_created_under_its_parent(): void
    {
        $response = $this->asSeller()
            ->postJson('/api/v2/categories/90001/children', ['name' => 'Tablets'])
            ->assertStatus(201);

        $this->assertSame(90001, $response->json('data.parent_id'));

        $this->assertDatabaseHas('categories', [
            'name' => 'Tablets', 'parent_id' => 90001, 'position' => 1,
        ]);
    }

    public function test_sub_categories_are_listed_for_their_parent_only(): void
    {
        $this->asSeller()->postJson('/api/v2/categories/90001/children', ['name' => 'Syrups'])
            ->assertStatus(201);

        $other = $this->asSeller()->postJson('/api/v2/categories', ['name' => 'Other Parent'])
            ->json('data.id');
        $this->asSeller()->postJson("/api/v2/categories/{$other}/children", ['name' => 'Elsewhere'])
            ->assertStatus(201);

        $response = $this->asSeller()->getJson('/api/v2/categories/90001/children')->assertOk();

        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('Syrups'));
        $this->assertFalse($names->contains('Elsewhere'));
    }

    public function test_a_sub_category_is_validated(): void
    {
        $this->asSeller()->postJson('/api/v2/categories/90001/children', [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['name']]);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->getJson('/api/v2/dashboard/summary')->assertStatus(401);
        $this->getJson('/api/v2/dashboard/monthly-revenue')->assertStatus(401);
    }
}
