<?php

namespace Tests\Feature\Api\V2;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Stock;
use Database\Seeders\ApiTestingSeeder;
use Tests\Feature\Api\ApiTestCase;

/**
 * Returns filed against the invoice they reverse.
 *
 * The rule that matters: a line can never give back more than it sold, however
 * many separate returns are filed against it.
 */
class OrderReturnTest extends ApiTestCase
{
    /** Sell `qty` units and hand back the invoice id. */
    private function sell(int $qty = 5, float $price = 100): int
    {
        return $this->asSeller()->postJson('/api/v2/orders', [
            'user_id' => 90001, 'order_type' => 4,
            'cart' => [['id' => 90001, 'quantity' => $qty, 'price' => $price]],
            'collected_cash' => $qty * $price,
        ])->assertStatus(201)->json('data.id');
    }

    public function test_an_invoices_returnable_lines_are_listed(): void
    {
        $id = $this->sell(5, 100);

        $response = $this->asSeller()->getJson("/api/v2/orders/{$id}/returnable")->assertOk();

        $response->assertJsonPath('data.order.id', $id)
            ->assertJsonPath('data.fully_returned', false)
            ->assertJsonStructure([
                'data' => [
                    'order' => ['id', 'type', 'order_amount', 'customer'],
                    'lines' => [[
                        'order_detail_id', 'product_id', 'product_name', 'product_code',
                        'price', 'quantity_sold', 'quantity_returned', 'quantity_returnable',
                    ]],
                ],
            ]);

        $this->assertEquals(5, $response->json('data.lines.0.quantity_sold'));
        $this->assertEquals(0, $response->json('data.lines.0.quantity_returned'));
        $this->assertEquals(5, $response->json('data.lines.0.quantity_returnable'));
    }

    public function test_a_partial_return_refunds_and_restocks_only_what_came_back(): void
    {
        $id = $this->sell(5, 100);

        $stockAfterSale = Stock::where('seller_id', ApiTestingSeeder::SELLER_ID)
            ->where('product_id', 90001)->value('stock');
        $balanceBefore = (float) Customer::find(90001)->balance;

        $response = $this->asSeller()->postJson('/api/v2/orders/returns', [
            'order_id' => $id,
            'items'    => [['product_id' => 90001, 'quantity' => 2]],
            'note'     => 'damaged',
        ])->assertStatus(201);

        $response->assertJsonPath('data.type', 7);

        // Two units back on the van, not five.
        $this->assertEquals(
            $stockAfterSale + 2,
            Stock::where('seller_id', ApiTestingSeeder::SELLER_ID)->where('product_id', 90001)->value('stock')
        );

        // And the refund is credited to the customer.
        $this->assertGreaterThan($balanceBefore, (float) Customer::find(90001)->balance);
    }

    public function test_the_return_is_linked_to_the_invoice_it_reverses(): void
    {
        $id = $this->sell(3, 100);

        $returnId = $this->asSeller()->postJson('/api/v2/orders/returns', [
            'order_id' => $id, 'items' => [['product_id' => 90001, 'quantity' => 1]],
        ])->assertStatus(201)->json('data.id');

        $this->assertSame($id, (int) Order::find($returnId)->parent_id);

        // The original line records what has come back.
        $this->assertEquals(1, OrderDetail::where('order_id', $id)->value('quantity_returned'));

        // And the refund is on the ledger as money out.
        $this->assertDatabaseHas('transections', ['order_id' => $returnId, 'credit' => 1]);
    }

    public function test_returns_accumulate_and_stop_at_what_was_sold(): void
    {
        $id = $this->sell(5, 100);

        $this->asSeller()->postJson('/api/v2/orders/returns', [
            'order_id' => $id, 'items' => [['product_id' => 90001, 'quantity' => 2]],
        ])->assertStatus(201);

        $this->assertEquals(3, $this->asSeller()
            ->getJson("/api/v2/orders/{$id}/returnable")->json('data.lines.0.quantity_returnable'));

        $this->asSeller()->postJson('/api/v2/orders/returns', [
            'order_id' => $id, 'items' => [['product_id' => 90001, 'quantity' => 3]],
        ])->assertStatus(201);

        // Nothing left, and the invoice reports itself fully returned.
        $response = $this->asSeller()->getJson("/api/v2/orders/{$id}/returnable")->assertOk();
        $this->assertEquals(0, $response->json('data.lines.0.quantity_returnable'));
        $this->assertTrue($response->json('data.fully_returned'));
    }

    public function test_returning_more_than_is_left_is_refused_and_changes_nothing(): void
    {
        $id = $this->sell(5, 100);

        $this->asSeller()->postJson('/api/v2/orders/returns', [
            'order_id' => $id, 'items' => [['product_id' => 90001, 'quantity' => 2]],
        ])->assertStatus(201);

        $stockBefore = Stock::where('seller_id', ApiTestingSeeder::SELLER_ID)
            ->where('product_id', 90001)->value('stock');
        $orders = Order::count();

        // Only three remain.
        $this->asSeller()->postJson('/api/v2/orders/returns', [
            'order_id' => $id, 'items' => [['product_id' => 90001, 'quantity' => 4]],
        ])->assertStatus(422)->assertJsonPath('success', false);

        $this->assertEquals(
            $stockBefore,
            Stock::where('seller_id', ApiTestingSeeder::SELLER_ID)->where('product_id', 90001)->value('stock')
        );
        $this->assertSame($orders, Order::count());
        $this->assertEquals(2, OrderDetail::where('order_id', $id)->value('quantity_returned'));
    }

    public function test_a_product_that_is_not_on_the_invoice_is_refused(): void
    {
        $id = $this->sell(2, 100);

        $other = \App\Models\Product::create([
            'local_id' => 0, 'name' => 'Not Sold', 'product_code' => 'NOT-SOLD',
            'purchase_price' => 1, 'selling_price' => 2, 'type' => 'product',
            'purchase_price1' => 1, 'purchase_price2' => 1, 'purchase_price3' => 1, 'purchase_price4' => 1,
            'selling_price1' => 2, 'selling_price2' => 2, 'selling_price3' => 2, 'selling_price4' => 2,
        ]);

        $this->asSeller()->postJson('/api/v2/orders/returns', [
            'order_id' => $id, 'items' => [['product_id' => $other->id, 'quantity' => 1]],
        ])->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_a_return_cannot_be_filed_against_another_return(): void
    {
        $id = $this->sell(3, 100);

        $returnId = $this->asSeller()->postJson('/api/v2/orders/returns', [
            'order_id' => $id, 'items' => [['product_id' => 90001, 'quantity' => 1]],
        ])->assertStatus(201)->json('data.id');

        // Returning against a return would double-credit the customer.
        $this->asSeller()->postJson('/api/v2/orders/returns', [
            'order_id' => $returnId, 'items' => [['product_id' => 90001, 'quantity' => 1]],
        ])->assertStatus(422);

        $this->asSeller()->getJson("/api/v2/orders/{$returnId}/returnable")->assertStatus(422);
    }

    public function test_the_request_is_validated(): void
    {
        $this->asSeller()->postJson('/api/v2/orders/returns', [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['order_id', 'items']]);

        $id = $this->sell(2, 100);

        $this->asSeller()->postJson('/api/v2/orders/returns', [
            'order_id' => $id, 'items' => [['product_id' => 90001, 'quantity' => 0]],
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['items.0.quantity']]);

        $this->asSeller()->postJson('/api/v2/orders/returns', [
            'order_id' => 99999999, 'items' => [['product_id' => 90001, 'quantity' => 1]],
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['order_id']]);
    }

    public function test_another_sellers_invoice_cannot_be_returned(): void
    {
        $foreign = Order::create([
            'owner_id' => 999999, 'user_id' => 90001, 'type' => '4',
            'order_amount' => 100, 'total_tax' => 0, 'update_flag' => 0,
        ]);

        $this->asSeller()->getJson("/api/v2/orders/{$foreign->id}/returnable")->assertStatus(403);

        $this->asSeller()->postJson('/api/v2/orders/returns', [
            'order_id' => $foreign->id, 'items' => [['product_id' => 90001, 'quantity' => 1]],
        ])->assertStatus(403);
    }

    public function test_return_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v2/orders/1/returnable')->assertStatus(401);
        $this->postJson('/api/v2/orders/returns', [])->assertStatus(401);
    }
}
