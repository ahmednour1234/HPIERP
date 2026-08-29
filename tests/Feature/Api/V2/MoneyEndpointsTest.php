<?php

namespace Tests\Feature\Api\V2;

use App\Models\Account;
use App\Models\Supplier;
use App\Models\Transection;
use Tests\Feature\Api\ApiTestCase;

/**
 * Suppliers and the money movements (transfer, expense, income, supplier
 * payment). These write to more than one row, so the tests check that balances
 * stay consistent and that a rejected movement writes nothing at all.
 */
class MoneyEndpointsTest extends ApiTestCase
{
    private function secondAccount(float $balance = 500): Account
    {
        return Account::create([
            'account' => 'Second Account', 'account_number' => 'SEC-1',
            'balance' => $balance, 'total_in' => 0, 'total_out' => 0,
        ]);
    }

    /* ---------------- suppliers ---------------- */

    public function test_suppliers_crud_and_search(): void
    {
        $id = $this->asSeller()->postJson('/api/v2/suppliers', [
            'name' => 'Delta Pharma', 'mobile' => '01000000001', 'city' => 'Cairo',
        ])->assertStatus(201)->json('data.id');

        $this->asSeller()->getJson('/api/v2/suppliers?search=Delta')
            ->assertOk()->assertJsonPath('data.0.id', $id);

        $this->asSeller()->getJson('/api/v2/suppliers/by-city?city=Cairo')
            ->assertOk()->assertJsonPath('data.0.id', $id);

        $this->asSeller()->putJson('/api/v2/suppliers', ['id' => $id, 'name' => 'Delta Pharma Co'])
            ->assertOk()->assertJsonPath('data.name', 'Delta Pharma Co');

        $this->asSeller()->deleteJson("/api/v2/suppliers/{$id}")->assertOk();
        $this->asSeller()->getJson("/api/v2/suppliers/{$id}")->assertStatus(404);
    }

    public function test_supplier_creation_is_validated(): void
    {
        $this->asSeller()->postJson('/api/v2/suppliers', [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['name', 'mobile']]);
    }

    public function test_transactions_of_an_unknown_supplier_answer_404(): void
    {
        $this->asSeller()->getJson('/api/v2/suppliers/99999999/transactions')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_paying_a_supplier_moves_money_and_reduces_the_due(): void
    {
        $supplier = Supplier::create([
            'local_id' => 0, 'name' => 'Payable Co', 'mobile' => '0100', 'due_amount' => 400,
        ]);
        $account = Account::find(90001);
        $before  = $account->balance;

        $this->asSeller()->postJson('/api/v2/suppliers/pay', [
            'supplier_id' => $supplier->id, 'account_id' => $account->id, 'amount' => 150,
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertEquals($before - 150, Account::find(90001)->balance);
        $this->assertEquals(250, Supplier::find($supplier->id)->due_amount);
        $this->assertDatabaseHas('transections', [
            'supplier_id' => $supplier->id, 'amount' => 150, 'tran_type' => 'Expense',
        ]);
    }

    public function test_paying_more_than_the_account_holds_is_rejected_and_writes_nothing(): void
    {
        $supplier = Supplier::create([
            'local_id' => 0, 'name' => 'Too Big', 'mobile' => '0101', 'due_amount' => 999999,
        ]);
        $account = Account::find(90001);
        $before  = $account->balance;
        $rows    = Transection::count();

        // v1 answered `success: true` here, so clients recorded a payment that
        // never happened.
        $this->asSeller()->postJson('/api/v2/suppliers/pay', [
            'supplier_id' => $supplier->id, 'account_id' => $account->id,
            'amount' => $before + 1000,
        ])->assertStatus(422)->assertJsonPath('success', false);

        $this->assertEquals($before, Account::find(90001)->balance);
        $this->assertSame($rows, Transection::count(), 'a rejected payment must write no ledger rows');
    }

    /* ---------------- transfers ---------------- */

    public function test_a_transfer_moves_money_between_both_accounts(): void
    {
        $to   = $this->secondAccount(0);
        $from = Account::find(90001);
        $fromBefore = $from->balance;

        $this->asSeller()->postJson('/api/v2/transactions/transfer', [
            'account_from_id' => $from->id, 'account_to_id' => $to->id,
            'amount' => 300, 'description' => 'Float top-up', 'date' => '2026-01-01',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertEquals($fromBefore - 300, Account::find($from->id)->balance);
        $this->assertEquals(300, Account::find($to->id)->balance);

        // Both legs are recorded.
        $this->assertSame(2, Transection::where('tran_type', 'Transfer')->count());
    }

    public function test_a_transfer_without_enough_balance_moves_nothing(): void
    {
        $to   = $this->secondAccount(0);
        $from = Account::find(90001);
        $fromBefore = $from->balance;
        $rows = Transection::count();

        $this->asSeller()->postJson('/api/v2/transactions/transfer', [
            'account_from_id' => $from->id, 'account_to_id' => $to->id,
            'amount' => $fromBefore + 5000, 'description' => 'Too much', 'date' => '2026-01-01',
        ])->assertStatus(422)->assertJsonPath('success', false);

        // The v1 version wrote both legs with no transaction, so a failure
        // part-way through could debit one account without crediting the other.
        $this->assertEquals($fromBefore, Account::find($from->id)->balance);
        $this->assertEquals(0, Account::find($to->id)->balance);
        $this->assertSame($rows, Transection::count());
    }

    public function test_a_transfer_to_the_same_account_is_rejected(): void
    {
        $this->asSeller()->postJson('/api/v2/transactions/transfer', [
            'account_from_id' => 90001, 'account_to_id' => 90001,
            'amount' => 10, 'description' => 'Nowhere', 'date' => '2026-01-01',
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['account_from_id']]);
    }

    public function test_a_transfer_rejects_a_non_numeric_amount(): void
    {
        // v1 used 'min:1' without 'numeric', so "abc" passed as a 3-char string.
        $this->asSeller()->postJson('/api/v2/transactions/transfer', [
            'account_from_id' => 90001, 'account_to_id' => $this->secondAccount()->id,
            'amount' => 'abc', 'description' => 'Bad', 'date' => '2026-01-01',
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['amount']]);
    }

    /* ---------------- expense / income ---------------- */

    public function test_an_expense_reduces_the_account(): void
    {
        $before = Account::find(90001)->balance;

        $this->asSeller()->postJson('/api/v2/transactions/expense', [
            'account_id' => 90001, 'amount' => 75,
            'description' => 'Fuel', 'date' => '2026-01-01',
        ])->assertStatus(201)->assertJsonPath('success', true);

        $this->assertEquals($before - 75, Account::find(90001)->balance);
    }

    public function test_income_increases_the_account(): void
    {
        $before = Account::find(90001)->balance;

        $this->asSeller()->postJson('/api/v2/transactions/income', [
            'account_id' => 90001, 'amount' => 500,
            'description' => 'Sales', 'date' => '2026-01-01',
        ])->assertStatus(201);

        $this->assertEquals($before + 500, Account::find(90001)->balance);
    }

    public function test_the_ledger_lists_and_filters(): void
    {
        $this->asSeller()->postJson('/api/v2/transactions/income', [
            'account_id' => 90001, 'amount' => 10, 'description' => 'x', 'date' => '2026-01-01',
        ])->assertStatus(201);

        $this->asSeller()->getJson('/api/v2/transactions')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'tran_type', 'amount', 'balance']], 'meta']);

        $this->asSeller()->getJson('/api/v2/transactions?type=Income')
            ->assertOk()->assertJsonPath('data.0.tran_type', 'Income');

        $this->asSeller()->getJson('/api/v2/transactions/types')
            ->assertOk()->assertJsonPath('success', true);
    }

    public function test_money_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v2/transactions')->assertStatus(401);
        $this->getJson('/api/v2/suppliers')->assertStatus(401);
    }
}
