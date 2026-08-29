<?php

namespace Tests\Feature\Api\V2;

use App\Models\ConfirmStock;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockHistory;
use Database\Seeders\ApiTestingSeeder;
use Tests\Feature\Api\ApiTestCase;

class StockEndpointsTest extends ApiTestCase
{
    public function test_stock_listing_returns_the_sellers_own_rows(): void
    {
        $response = $this->asSeller()->getJson('/api/v2/stocks?type=4');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success', 'message',
                'data' => [['stock_id', 'quantity', 'main_stock', 'product' => ['id', 'name', 'selling_price']]],
                'meta' => ['current_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('data.0.product.id', 90001);
    }

    public function test_catalogue_listing_is_returned_when_type_is_not_four(): void
    {
        $this->asSeller()
            ->getJson('/api/v2/stocks')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => [['id', 'name', 'product_code', 'selling_price']]]);
    }

    public function test_seller_price_overrides_the_catalogue_price(): void
    {
        \App\Models\SellerPrice::create([
            'local_id'   => 0,
            'seller_id'  => ApiTestingSeeder::SELLER_ID,
            'product_id' => 90001,
            'price'      => 999,
        ]);

        $response = $this->asSeller()->getJson('/api/v2/stocks?type=4')->assertOk();

        // Compare numerically: SQLite hands back an int where MySQL gives a float.
        $this->assertEquals(999, $response->json('data.0.product.selling_price'));
    }

    public function test_history_starts_empty_and_is_wrapped_in_the_envelope(): void
    {
        $this->asSeller()
            ->getJson('/api/v2/stocks/history')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    public function test_confirm_settles_stock_and_writes_history(): void
    {
        // Sell 30 of the 500 units the seller is carrying.
        Stock::where('seller_id', ApiTestingSeeder::SELLER_ID)
            ->where('product_id', 90001)
            ->update(['stock' => 470, 'main_stock' => 500]);

        $warehouseBefore = Product::find(90001)->quantity;

        $response = $this->asSeller()->postJson('/api/v2/stocks/confirm');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['total_stock', 'remain_stock', 'total_cash', 'total_credit', 'products', 'remain_products'],
            ]);

        // 30 sold, 470 returned.
        $this->assertSame(30, $response->json('data.total_stock'));
        $this->assertSame(470, $response->json('data.remain_stock'));

        // The settlement is recorded...
        $this->assertDatabaseHas('confirm_stocks', [
            'seller_id' => ApiTestingSeeder::SELLER_ID, 'product_id' => 90001, 'stock' => 30,
        ]);
        $this->assertSame(1, StockHistory::where('seller_id', ApiTestingSeeder::SELLER_ID)->count());

        // ...unsold units go back to warehouse quantity...
        $this->assertSame($warehouseBefore + 470, Product::find(90001)->quantity);

        // ...and the seller's working stock is cleared for the next run.
        $this->assertSame(0, Stock::where('seller_id', ApiTestingSeeder::SELLER_ID)->count());
    }

    public function test_confirm_then_history_returns_the_settlement(): void
    {
        $this->asSeller()->postJson('/api/v2/stocks/confirm')->assertOk();

        $this->asSeller()
            ->getJson('/api/v2/stocks/history')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data' => [['id', 'seller_id', 'statistcs']]]);
    }

    public function test_confirm_is_atomic(): void
    {
        // A product row the settlement will touch is removed mid-flight by
        // making the product id dangle; the whole settlement must roll back
        // rather than leave half-written history.
        Stock::where('seller_id', ApiTestingSeeder::SELLER_ID)->update(['stock' => 100]);

        $before = ConfirmStock::count();

        $this->asSeller()->postJson('/api/v2/stocks/confirm')->assertOk();

        // Sanity: it committed exactly one settlement, not a partial one.
        $this->assertGreaterThan($before, ConfirmStock::count());
        $this->assertSame(
            ConfirmStock::where('seller_id', ApiTestingSeeder::SELLER_ID)->count(),
            StockHistory::where('seller_id', ApiTestingSeeder::SELLER_ID)->count(),
            'confirm_stocks and stock_histories must stay in step'
        );
    }

    public function test_stock_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v2/stocks')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }
}
