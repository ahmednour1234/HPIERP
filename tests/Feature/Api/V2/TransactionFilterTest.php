<?php

namespace Tests\Feature\Api\V2;

use App\Models\Transection;
use Tests\Feature\Api\ApiTestCase;

/**
 * Filtering the ledger: collections, expenses and returns.
 */
class TransactionFilterTest extends ApiTestCase
{
    private function entry(array $attributes = []): Transection
    {
        return Transection::create(array_merge([
            'tran_type'   => '4',
            'account_id'  => 90001,
            'amount'      => 100,
            'description' => 'sale',
            'debit'       => 1,
            'credit'      => 0,
            'balance'     => 100,
            'date'        => now()->toDateString(),
            'cash'        => 1,
        ], $attributes));
    }

    public function test_entries_can_be_filtered_by_direction(): void
    {
        $this->entry(['debit' => 1, 'credit' => 0]);
        $this->entry(['debit' => 0, 'credit' => 1]);

        $this->asSeller()->getJson('/api/v2/transactions?direction=in')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->asSeller()->getJson('/api/v2/transactions?direction=out')
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_an_unknown_direction_is_rejected(): void
    {
        $this->asSeller()->getJson('/api/v2/transactions?direction=sideways')
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['direction']]);
    }

    public function test_entries_can_be_filtered_by_amount_and_order(): void
    {
        $this->entry(['amount' => 50]);
        $this->entry(['amount' => 5000, 'order_id' => 4242]);

        $this->asSeller()->getJson('/api/v2/transactions?min_amount=100')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->asSeller()->getJson('/api/v2/transactions?order_id=4242')
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_entries_can_be_searched_by_description(): void
    {
        $this->entry(['description' => 'cash collection']);
        $this->entry(['description' => 'fuel expense']);

        $this->asSeller()->getJson('/api/v2/transactions?search=collection')
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_arabic_descriptions_are_searchable(): void
    {
        $this->entry(['description' => 'مبيعات']);
        $this->entry(['description' => 'مرتجع مبيعات']);

        $this->asSeller()->getJson('/api/v2/transactions?search=' . urlencode('مرتجع'))
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_totals_split_money_in_from_money_out(): void
    {
        $this->entry(['amount' => 300, 'debit' => 1, 'credit' => 0]);
        $this->entry(['amount' => 100, 'debit' => 0, 'credit' => 1]);

        $data = $this->asSeller()->getJson('/api/v2/transactions/totals')->assertOk()->json('data');

        $this->assertSame(2, $data['entries']);
        $this->assertEquals(300, $data['money_in']);
        $this->assertEquals(100, $data['money_out']);
        $this->assertEquals(200, $data['net']);
    }

    public function test_totals_respect_the_filter(): void
    {
        $this->entry(['amount' => 300, 'tran_type' => '4']);
        $this->entry(['amount' => 900, 'tran_type' => '7']);

        $this->assertEquals(300, $this->asSeller()
            ->getJson('/api/v2/transactions/totals?type=4')->json('data.money_in'));
    }

    public function test_the_ledger_and_its_totals_agree(): void
    {
        $this->entry(['amount' => 120]);
        $this->entry(['amount' => 380]);

        $rows   = $this->asSeller()->getJson('/api/v2/transactions?direction=in')->json('data');
        $totals = $this->asSeller()->getJson('/api/v2/transactions/totals?direction=in')->json('data');

        $this->assertCount($totals['entries'], $rows);
        $this->assertEquals(array_sum(array_column($rows, 'amount')), $totals['money_in']);
    }

    public function test_transaction_filters_require_authentication(): void
    {
        $this->getJson('/api/v2/transactions/totals')->assertStatus(401);
    }
}
