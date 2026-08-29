<?php

namespace Tests\Feature\Api\V2;

use App\Models\Account;
use App\Models\Seller;
use App\Models\TransactionSeller;
use App\Models\Transection;
use App\Services\SellerDepositService;
use Database\Seeders\ApiTestingSeeder;
use Tests\Feature\Api\ApiTestCase;

/**
 * A seller handing in the cash they collected.
 *
 * This is the money side of the app, so the tests lean on what must stay true
 * when something goes wrong: nothing moves before approval, and an approval
 * cannot be applied twice.
 */
class SellerDepositTest extends ApiTestCase
{
    private function file(array $overrides = []): int
    {
        $response = $this->asSeller()->postJson('/api/v2/deposits', array_merge([
            'account_id' => 90001,
            'amount'     => 1500,
            'note'       => 'Cash handed in',
        ], $overrides));

        $response->assertStatus(201);

        return $response->json('data.id');
    }

    public function test_a_deposit_is_filed_as_pending(): void
    {
        $response = $this->asSeller()->postJson('/api/v2/deposits', [
            'account_id' => 90001, 'amount' => 1500, 'note' => 'Cash handed in',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', SellerDepositService::PENDING)
            ->assertJsonPath('data.status_text', 'pending')
            ->assertJsonPath('data.account.id', 90001);

        $this->assertEquals(1500, $response->json('data.amount'));
    }

    public function test_filing_a_deposit_moves_no_money(): void
    {
        $balanceBefore = Account::find(90001)->balance;
        $ledgerBefore  = Transection::count();

        $this->file();

        // The whole point of the pending state: an unapproved deposit must not
        // credit the company's account.
        $this->assertEquals($balanceBefore, Account::find(90001)->balance);
        $this->assertSame($ledgerBefore, Transection::count());
    }

    public function test_approval_credits_the_account_and_clears_the_sellers_balance(): void
    {
        $id = $this->file();

        $accountBefore = (float) Account::find(90001)->balance;
        $creditBefore  = (float) Seller::find(ApiTestingSeeder::SELLER_ID)->credit;

        app(SellerDepositService::class)->approve($id);

        $this->assertEquals($accountBefore + 1500, (float) Account::find(90001)->balance);
        $this->assertEquals($creditBefore - 1500, (float) Seller::find(ApiTestingSeeder::SELLER_ID)->credit);

        $this->assertDatabaseHas('transections', [
            'tran_type' => SellerDepositService::TRAN_TYPE,
            'account_id' => 90001,
            'seller_id'  => ApiTestingSeeder::SELLER_ID,
        ]);

        $this->assertSame(
            SellerDepositService::APPROVED,
            (int) TransactionSeller::find($id)->active
        );
    }

    public function test_a_deposit_cannot_be_approved_twice(): void
    {
        $id      = $this->file();
        $service = app(SellerDepositService::class);

        $service->approve($id);

        $balanceAfterFirst = (float) Account::find(90001)->balance;
        $ledgerAfterFirst  = Transection::count();

        // Without this guard the account would be credited a second time.
        $this->expectException(\InvalidArgumentException::class);

        try {
            $service->approve($id);
        } finally {
            $this->assertEquals($balanceAfterFirst, (float) Account::find(90001)->balance);
            $this->assertSame($ledgerAfterFirst, Transection::count());
        }
    }

    public function test_a_rejected_deposit_moves_nothing_and_cannot_be_approved(): void
    {
        $id      = $this->file();
        $service = app(SellerDepositService::class);

        $balanceBefore = (float) Account::find(90001)->balance;
        $service->reject($id);

        $this->assertSame(SellerDepositService::REJECTED, (int) TransactionSeller::find($id)->active);
        $this->assertEquals($balanceBefore, (float) Account::find(90001)->balance);

        $this->expectException(\InvalidArgumentException::class);
        $service->approve($id);
    }

    public function test_the_amount_must_be_positive(): void
    {
        foreach ([0, -500] as $amount) {
            $this->asSeller()
                ->postJson('/api/v2/deposits', ['account_id' => 90001, 'amount' => $amount])
                ->assertStatus(422)
                ->assertJsonStructure(['errors' => ['amount']]);
        }
    }

    public function test_the_account_must_exist(): void
    {
        // v1 accepted any account_id and only failed at insert time.
        $this->asSeller()
            ->postJson('/api/v2/deposits', ['account_id' => 99999999, 'amount' => 100])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['account_id']]);
    }

    public function test_a_deposit_without_a_photo_is_accepted(): void
    {
        // img is NOT NULL with no default, so the v1 endpoint 500'd on every
        // request that did not attach a file.
        $this->asSeller()
            ->postJson('/api/v2/deposits', ['account_id' => 90001, 'amount' => 250])
            ->assertStatus(201)
            ->assertJsonPath('data.image', null);
    }

    public function test_a_seller_only_sees_their_own_deposits(): void
    {
        $mine = $this->file();

        $foreign = TransactionSeller::create([
            'seller_id' => 999999, 'account_id' => 90001, 'amount' => '900',
            'note' => 'someone else', 'img' => '',
        ]);

        $ids = collect($this->asSeller()->getJson('/api/v2/deposits')->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($mine));
        $this->assertFalse($ids->contains($foreign->id));

        $this->asSeller()->getJson("/api/v2/deposits/{$foreign->id}")->assertStatus(403);
    }

    public function test_the_listing_can_be_filtered_by_status(): void
    {
        $approved = $this->file();
        $this->file();                       // stays pending

        app(SellerDepositService::class)->approve($approved);

        $this->asSeller()->getJson('/api/v2/deposits?status=' . SellerDepositService::APPROVED)
            ->assertOk()->assertJsonCount(1, 'data');

        $this->asSeller()->getJson('/api/v2/deposits?status=' . SellerDepositService::PENDING)
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_the_summary_totals_each_status(): void
    {
        $approved = $this->file();
        $this->file(['amount' => 500]);

        app(SellerDepositService::class)->approve($approved);

        $data = $this->asSeller()->getJson('/api/v2/deposits/summary')->assertOk()->json('data');

        $this->assertSame(1, $data['approved']['count']);
        $this->assertEquals(1500, $data['approved']['total']);
        $this->assertSame(1, $data['pending']['count']);
        $this->assertEquals(500, $data['pending']['total']);
    }

    public function test_deposit_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v2/deposits')->assertStatus(401);
        $this->postJson('/api/v2/deposits', [])->assertStatus(401);
    }
}
