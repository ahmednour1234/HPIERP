<?php

namespace Tests\Feature\Api\V2;

use App\Models\StockReturnRequest;
use Database\Seeders\ApiTestingSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\ApiTestCase;

/**
 * إرجاع البضاعة من العربية إلى المخزن بموافقة الأدمن.
 *
 * القاعدة التي تحمي المخزون: لا شيء يتحرك قبل الاعتماد.
 */
class StockReturnTest extends ApiTestCase
{
    private const PRODUCT = 90001;

    public function test_a_return_request_does_not_move_any_stock(): void
    {
        $vanBefore = $this->vanStock();
        $warehouseBefore = $this->warehouseQuantity();

        $this->asSeller()
            ->postJson('/api/v2/stocks/confirm', [
                'items' => [['product_id' => self::PRODUCT, 'quantity' => 10]],
                'note'  => 'باقي اليوم',
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.items.0.quantity', 10);

        $this->assertSame($vanBefore, $this->vanStock(), 'الطلب حرّك مخزون العربية قبل الاعتماد');
        $this->assertSame($warehouseBefore, $this->warehouseQuantity(), 'الطلب حرّك المخزن قبل الاعتماد');
    }

    public function test_approval_moves_the_quantities_from_the_van_to_the_warehouse(): void
    {
        $vanBefore = $this->vanStock();
        $warehouseBefore = $this->warehouseQuantity();

        $id = $this->fileRequest(10);

        app(\App\Services\StockReturnService::class)->approve($id, ApiTestingSeeder::SELLER_ID);

        $this->assertSame($vanBefore - 10, $this->vanStock());
        $this->assertSame($warehouseBefore + 10, $this->warehouseQuantity());
        $this->assertDatabaseHas('stock_return_requests', ['id' => $id, 'status' => 'approved']);
    }

    public function test_rejection_leaves_the_stock_untouched(): void
    {
        $id = $this->fileRequest(10);

        $vanBefore = $this->vanStock();
        $warehouseBefore = $this->warehouseQuantity();

        app(\App\Services\StockReturnService::class)->reject($id, ApiTestingSeeder::SELLER_ID, 'فرق جرد');

        $this->assertSame($vanBefore, $this->vanStock());
        $this->assertSame($warehouseBefore, $this->warehouseQuantity());
        $this->assertDatabaseHas('stock_return_requests', ['id' => $id, 'status' => 'rejected']);
    }

    public function test_a_second_pending_request_is_refused(): void
    {
        $this->fileRequest(5);

        $this->asSeller()
            ->postJson('/api/v2/stocks/confirm', [
                'items' => [['product_id' => self::PRODUCT, 'quantity' => 5]],
            ])
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertSame(1, StockReturnRequest::count());
    }

    public function test_returning_more_than_the_van_holds_is_refused(): void
    {
        $this->asSeller()
            ->postJson('/api/v2/stocks/confirm', [
                'items' => [['product_id' => self::PRODUCT, 'quantity' => $this->vanStock() + 1]],
            ])
            ->assertStatus(422);

        $this->assertSame(0, StockReturnRequest::count());
    }

    public function test_duplicate_lines_are_summed_before_the_check(): void
    {
        $half = (int) ($this->vanStock() / 2) + 1;

        // كل سطر وحده ضمن المتاح، ومجموعهما يتجاوزه.
        $this->asSeller()
            ->postJson('/api/v2/stocks/confirm', [
                'items' => [
                    ['product_id' => self::PRODUCT, 'quantity' => $half],
                    ['product_id' => self::PRODUCT, 'quantity' => $half],
                ],
            ])
            ->assertStatus(422);

        $this->assertSame(0, StockReturnRequest::count());
    }

    public function test_the_request_is_validated(): void
    {
        $this->asSeller()->postJson('/api/v2/stocks/confirm', [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['items']]);

        $this->asSeller()
            ->postJson('/api/v2/stocks/confirm', [
                'items' => [['product_id' => self::PRODUCT, 'quantity' => 0]],
            ])
            ->assertStatus(422);
    }

    public function test_current_returns_null_when_nothing_is_filed(): void
    {
        $this->asSeller()
            ->getJson('/api/v2/stocks/confirm/current')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', null);
    }

    public function test_current_returns_the_pending_request(): void
    {
        $id = $this->fileRequest(7);

        $this->asSeller()
            ->getJson('/api/v2/stocks/confirm/current')
            ->assertStatus(200)
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.items.0.quantity', 7);
    }

    public function test_a_seller_can_cancel_their_pending_request(): void
    {
        $id = $this->fileRequest(7);

        $this->asSeller()
            ->postJson("/api/v2/stocks/confirm/{$id}/cancel")
            ->assertStatus(200);

        $this->assertDatabaseMissing('stock_return_requests', ['id' => $id]);
        $this->assertDatabaseMissing('stock_return_request_items', ['request_id' => $id]);
    }

    public function test_a_reviewed_request_cannot_be_cancelled(): void
    {
        $id = $this->fileRequest(7);
        app(\App\Services\StockReturnService::class)->reject($id, ApiTestingSeeder::SELLER_ID, 'لا');

        $this->asSeller()
            ->postJson("/api/v2/stocks/confirm/{$id}/cancel")
            ->assertStatus(409);

        $this->assertDatabaseHas('stock_return_requests', ['id' => $id]);
    }

    public function test_approving_twice_does_not_move_the_stock_twice(): void
    {
        $id = $this->fileRequest(10);
        $service = app(\App\Services\StockReturnService::class);

        $service->approve($id, ApiTestingSeeder::SELLER_ID);
        $vanAfterFirst = $this->vanStock();

        try {
            $service->approve($id, ApiTestingSeeder::SELLER_ID);
            $this->fail('اعتماد الطلب مرتين لم يُرفض');
        } catch (\App\Services\Exceptions\ReturnRequestException $e) {
            $this->assertSame(409, $e->status());
        }

        $this->assertSame($vanAfterFirst, $this->vanStock());
    }

    /** يسجّل طلبًا معلّقًا ويعيد رقمه. */
    private function fileRequest(int $quantity): int
    {
        return (int) app(\App\Services\StockReturnService::class)
            ->request(ApiTestingSeeder::SELLER_ID, [
                ['product_id' => self::PRODUCT, 'quantity' => $quantity],
            ])->id;
    }

    private function vanStock(): int
    {
        return (int) DB::table('stocks')
            ->where('seller_id', ApiTestingSeeder::SELLER_ID)
            ->where('product_id', self::PRODUCT)
            ->value('stock');
    }

    private function warehouseQuantity(): int
    {
        return (int) DB::table('products')->where('id', self::PRODUCT)->value('quantity');
    }
}
