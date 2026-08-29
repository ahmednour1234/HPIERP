<?php

namespace Tests\Feature\Api\V2;

use App\Models\Order;
use Database\Seeders\ApiTestingSeeder;
use Tests\Feature\Api\ApiTestCase;

/**
 * Search and filtering across invoices, collections and returns.
 *
 * The totals endpoints matter as much as the lists: they are computed over the
 * whole filtered result, so a summary that disagreed with its rows would be
 * worse than no summary at all.
 */
class OrderFilterTest extends ApiTestCase
{
    /** An order owned by the test seller, written straight to the table. */
    private function order(array $attributes = []): Order
    {
        return Order::create(array_merge([
            'owner_id'     => ApiTestingSeeder::SELLER_ID,
            'user_id'      => 90001,
            'type'         => '4',
            'order_amount' => 100,
            'total_tax'    => 10,
            'collected_cash' => 100,
            'cash'         => 1,
            'update_flag'  => 0,
        ], $attributes));
    }

    public function test_orders_can_be_filtered_by_type(): void
    {
        $this->order(['type' => '4']);
        $this->order(['type' => '7']);

        $this->asSeller()->getJson('/api/v2/orders?type=4')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.type', 4);

        $this->asSeller()->getJson('/api/v2/orders?type=7')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.type', 7);
    }

    public function test_orders_can_be_filtered_by_settlement_state(): void
    {
        $this->order(['order_amount' => 100, 'collected_cash' => 100]);  // paid
        $this->order(['order_amount' => 100, 'collected_cash' => 40]);   // partial
        $this->order(['order_amount' => 100, 'collected_cash' => 0]);    // unpaid

        foreach (['paid' => 1, 'partial' => 1, 'unpaid' => 1] as $state => $expected) {
            $this->asSeller()->getJson("/api/v2/orders?payment_status={$state}")
                ->assertOk()
                ->assertJsonCount($expected, 'data');
        }
    }

    public function test_orders_can_be_filtered_by_amount_range(): void
    {
        $this->order(['order_amount' => 50]);
        $this->order(['order_amount' => 500]);
        $this->order(['order_amount' => 5000]);

        $this->asSeller()->getJson('/api/v2/orders?min_amount=100')
            ->assertOk()->assertJsonCount(2, 'data');

        $this->asSeller()->getJson('/api/v2/orders?min_amount=100&max_amount=1000')
            ->assertOk()->assertJsonCount(1, 'data');

        // Numeric compare: SQLite returns an int where MySQL gives a float.
        $this->assertEquals(500, $this->asSeller()
            ->getJson('/api/v2/orders?min_amount=100&max_amount=1000')->json('data.0.order_amount'));
    }

    public function test_a_reversed_amount_range_is_rejected(): void
    {
        $this->asSeller()->getJson('/api/v2/orders?min_amount=500&max_amount=100')
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['max_amount']]);
    }

    public function test_orders_can_be_sorted(): void
    {
        $this->order(['order_amount' => 50]);
        $this->order(['order_amount' => 900]);

        $this->assertEquals(900, $this->asSeller()
            ->getJson('/api/v2/orders?sort=amount_desc')->json('data.0.order_amount'));

        $this->assertEquals(50, $this->asSeller()
            ->getJson('/api/v2/orders?sort=amount_asc')->json('data.0.order_amount'));
    }

    public function test_an_unknown_sort_is_rejected(): void
    {
        $this->asSeller()->getJson('/api/v2/orders?sort=sideways')
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['sort']]);
    }

    public function test_orders_can_be_searched_by_id_and_customer_name(): void
    {
        $order = $this->order();

        $this->asSeller()->getJson('/api/v2/orders?search=' . $order->id)
            ->assertOk()->assertJsonPath('data.0.id', $order->id);

        $this->asSeller()->getJson('/api/v2/orders?search=Test%20Customer')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->asSeller()->getJson('/api/v2/orders?search=no-such-thing')
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_order_totals_cover_the_whole_filter_not_just_one_page(): void
    {
        foreach (range(1, 5) as $i) {
            $this->order(['order_amount' => 100, 'collected_cash' => 60, 'total_tax' => 10]);
        }

        // One row per page, but the totals must describe all five.
        $response = $this->asSeller()
            ->getJson('/api/v2/orders/totals?limit=1')
            ->assertOk();

        $response->assertJsonPath('data.orders', 5);
        $this->assertEquals(500, $response->json('data.total'));
        $this->assertEquals(300, $response->json('data.collected'));
        $this->assertEquals(200, $response->json('data.remaining'));
    }

    public function test_order_totals_respect_the_type_filter(): void
    {
        $this->order(['type' => '4', 'order_amount' => 300]);
        $this->order(['type' => '7', 'order_amount' => 100]);

        $this->assertEquals(300, $this->asSeller()
            ->getJson('/api/v2/orders/totals?type=4')->json('data.total'));

        $this->assertEquals(100, $this->asSeller()
            ->getJson('/api/v2/orders/totals?type=7')->json('data.total'));
    }

    public function test_totals_only_count_this_sellers_orders(): void
    {
        $this->order(['order_amount' => 200]);
        Order::create([
            'owner_id' => 999999, 'user_id' => 90001, 'type' => '4',
            'order_amount' => 9999, 'total_tax' => 0, 'update_flag' => 0,
        ]);

        $this->assertEquals(200, $this->asSeller()
            ->getJson('/api/v2/orders/totals')->json('data.total'));
    }

    public function test_the_listing_and_its_totals_agree(): void
    {
        $this->order(['order_amount' => 100]);
        $this->order(['order_amount' => 250]);
        $this->order(['type' => '7', 'order_amount' => 999]);

        $rows   = $this->asSeller()->getJson('/api/v2/orders?type=4')->json('data');
        $totals = $this->asSeller()->getJson('/api/v2/orders/totals?type=4')->json('data');

        // The summary above a list must describe the rows beneath it.
        $this->assertCount($totals['orders'], $rows);
        $this->assertEquals(array_sum(array_column($rows, 'order_amount')), $totals['total']);
    }

    public function test_order_filters_require_authentication(): void
    {
        $this->getJson('/api/v2/orders/totals')->assertStatus(401);
    }
}
