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

    /**
     * صار /stocks/confirm يسجّل طلب إرجاع بانتظار موافقة الأدمن بدل
     * التسوية الفورية، فلا يمس المخزون ولا يكتب سجل تسوية.
     *
     * تفاصيل المسار الجديد في StockReturnTest؛ التسوية الفورية القديمة
     * ما زالت على POST /api/v1/stocks/confirm.
     */
    public function test_confirm_files_a_pending_request_without_settling(): void
    {
        Stock::where('seller_id', ApiTestingSeeder::SELLER_ID)
            ->where('product_id', 90001)
            ->update(['stock' => 470, 'main_stock' => 500]);

        $warehouseBefore = Product::find(90001)->quantity;

        $this->asSeller()
            ->postJson('/api/v2/stocks/confirm', [
                'items' => [['product_id' => 90001, 'quantity' => 470]],
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'pending');

        // لا تسوية ولا حركة مخزون قبل الاعتماد.
        $this->assertSame(0, ConfirmStock::where('seller_id', ApiTestingSeeder::SELLER_ID)->count());
        $this->assertSame(0, StockHistory::where('seller_id', ApiTestingSeeder::SELLER_ID)->count());
        $this->assertSame($warehouseBefore, Product::find(90001)->quantity);
        $this->assertSame(470, (int) Stock::where('seller_id', ApiTestingSeeder::SELLER_ID)->value('stock'));
    }

    public function test_stock_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v2/stocks')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }
}
