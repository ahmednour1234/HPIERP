<?php

namespace Tests\Feature\Api\V1;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Transection;
use Database\Seeders\ApiTestingSeeder;
use Tests\Feature\Api\ApiTestCase;

/**
 * Three v1 endpoints used MySQL-only SQL (YEAR/MONTH, GREATEST) and could not
 * run anywhere else. These cover the rewritten, portable versions - including
 * that the arithmetic still classifies invoices the same way.
 */
class PortableSqlTest extends ApiTestCase
{
    public function test_monthly_revenue_runs_and_groups_by_year_and_month(): void
    {
        Transection::create(['tran_type' => 'Income', 'amount' => 100, 'date' => '2025-01-15', 'balance' => 0]);
        Transection::create(['tran_type' => 'Income', 'amount' => 50,  'date' => '2025-01-20', 'balance' => 0]);
        Transection::create(['tran_type' => 'Income', 'amount' => 70,  'date' => '2026-01-10', 'balance' => 0]);
        Transection::create(['tran_type' => 'Expense', 'amount' => 30, 'date' => '2025-01-05', 'balance' => 0]);

        $response = $this->asSeller()->getJson('/api/v1/dashboard/monthly/revenue')->assertOk();

        $income = collect($response->json('year_wise_income'));

        // Two January rows, not one: the original grouped by month alone, so
        // January 2025 and January 2026 were summed together.
        $januaries = $income->where('month', 1);
        $this->assertCount(2, $januaries, 'each year must keep its own January');

        $this->assertEquals(150, $januaries->firstWhere('year', 2025)['total_amount']);
        $this->assertEquals(70,  $januaries->firstWhere('year', 2026)['total_amount']);

        $this->assertEquals(30, collect($response->json('year_wise_expense'))->first()['total_amount']);
    }

    public function test_the_installment_order_lists_run(): void
    {
        $this->asSeller()->getJson('/api/v1/pos/order/install')
            ->assertOk()->assertJsonStructure(['total', 'limit', 'offset']);

        $this->asSeller()->getJson('/api/v1/pos/order/notinstall')
            ->assertOk()->assertJsonStructure(['total', 'limit', 'offset']);
    }

    public function test_an_unpaid_invoice_is_separated_from_a_paid_one(): void
    {
        // Fully collected: 100 of 100.
        $paid = Order::create([
            'owner_id' => ApiTestingSeeder::SELLER_ID, 'user_id' => 90001, 'type' => 4,
            'total_tax' => 0, 'order_amount' => 100, 'transaction_reference' => 100,
            'update_flag' => 0,
        ]);
        OrderDetail::create(['order_id' => $paid->id, 'product_id' => 90001, 'quantity' => 10,
            'price' => 10, 'update_flag' => 0]);

        // Collected 20 of 100.
        $unpaid = Order::create([
            'owner_id' => ApiTestingSeeder::SELLER_ID, 'user_id' => 90001, 'type' => 4,
            'total_tax' => 0, 'order_amount' => 100, 'transaction_reference' => 20,
            'update_flag' => 0,
        ]);
        OrderDetail::create(['order_id' => $unpaid->id, 'product_id' => 90001, 'quantity' => 10,
            'price' => 10, 'update_flag' => 0]);

        // The endpoint names its result key from ?type=: 'orders' for type 4,
        // 'refnd' otherwise.
        $notInstalled = collect($this->asSeller()->getJson('/api/v1/pos/order/notinstall?type=4')->json('orders'))
            ->pluck('id');
        $installed = collect($this->asSeller()->getJson('/api/v1/pos/order/install?type=4')->json('orders'))
            ->pluck('id');

        $this->assertTrue($notInstalled->contains($unpaid->id), 'the under-collected invoice belongs here');
        $this->assertFalse($notInstalled->contains($paid->id));

        $this->assertTrue($installed->contains($paid->id), 'the fully collected invoice belongs here');
        $this->assertFalse($installed->contains($unpaid->id));
    }

    public function test_a_return_reduces_what_is_owed(): void
    {
        $order = Order::create([
            'owner_id' => ApiTestingSeeder::SELLER_ID, 'user_id' => 90001, 'type' => 4,
            'total_tax' => 0, 'order_amount' => 100, 'transaction_reference' => 50,
            'update_flag' => 0,
        ]);
        OrderDetail::create(['order_id' => $order->id, 'product_id' => 90001, 'quantity' => 10,
            'price' => 10, 'update_flag' => 0]);

        // Half the units come back, so only 50 was ever owed - and 50 was paid.
        $return = Order::create([
            'owner_id' => ApiTestingSeeder::SELLER_ID, 'user_id' => 90001, 'type' => 7,
            'parent_id' => $order->id, 'total_tax' => 0, 'order_amount' => 50, 'update_flag' => 0,
        ]);
        OrderDetail::create(['order_id' => $return->id, 'product_id' => 90001, 'quantity' => 5,
            'price' => 10, 'update_flag' => 0]);

        $installed = collect($this->asSeller()->getJson('/api/v1/pos/order/install?type=4')->json('orders'))
            ->pluck('id');

        $this->assertTrue(
            $installed->contains($order->id),
            'after the return the invoice is settled, so it counts as collected'
        );
    }
}
