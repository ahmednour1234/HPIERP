<?php

namespace Tests\Feature\Api\V2;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Transection;
use Tests\Feature\Api\ApiTestCase;

/**
 * Collecting a payment against a specific invoice.
 *
 * Different from `customers/add-balance`, which settles a customer's overall
 * balance: this moves `collected_cash` on one document, so the invoice's
 * derived payment_status follows.
 */
class OrderCollectionTest extends ApiTestCase
{
    /** Sell for `total`, collecting nothing up front. */
    private function unpaidInvoice(int $qty = 1, float $price = 100): Order
    {
        $id = $this->asSeller()->postJson('/api/v2/orders', [
            'user_id' => 90001, 'order_type' => 4,
            'cart' => [['id' => 90001, 'quantity' => $qty, 'price' => $price]],
            'collected_cash' => 0,
        ])->assertStatus(201)->json('data.id');

        return Order::find($id);
    }

    private function collect(int $orderId, array $overrides = [])
    {
        return $this->asSeller()->postJson("/api/v2/orders/{$orderId}/collect", array_merge([
            'amount'     => 50,
            'account_id' => 90001,
            'date'       => now()->toDateString(),
        ], $overrides));
    }

    public function test_a_partial_collection_moves_the_invoice_to_partial(): void
    {
        $order = $this->unpaidInvoice();      // 114 including tax

        $response = $this->collect($order->id, ['amount' => 50])->assertOk();

        $response->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Payment collected')
            ->assertJsonPath('data.order_id', $order->id)
            ->assertJsonPath('data.payment_status', 'partial');

        $this->assertEquals(50, $response->json('data.collected_cash'));
        $this->assertEquals(
            round((float) $order->order_amount - 50, 2),
            $response->json('data.remaining')
        );
    }

    public function test_collecting_the_remainder_marks_the_invoice_paid(): void
    {
        $order = $this->unpaidInvoice();
        $total = round((float) $order->order_amount, 2);

        $this->collect($order->id, ['amount' => 50])->assertOk();
        $response = $this->collect($order->id, ['amount' => $total - 50])->assertOk();

        $response->assertJsonPath('data.payment_status', 'paid');
        $this->assertEquals(0, $response->json('data.remaining'));
        $this->assertEquals($total, $response->json('data.collected_cash'));
    }

    public function test_the_collection_reaches_the_ledger_the_account_and_the_customer(): void
    {
        $order = $this->unpaidInvoice();

        $accountBefore  = (float) Account::find(90001)->balance;
        $customerBefore = (float) Customer::find(90001)->balance;

        $this->collect($order->id, ['amount' => 40, 'note' => 'first instalment'])->assertOk();

        // The entry is tied to the invoice, not just the customer.
        $this->assertDatabaseHas('transections', [
            'order_id'    => $order->id,
            'customer_id' => 90001,
            'tran_type'   => 'Receivable',
            'debit'       => 1,
        ]);

        $this->assertEquals($accountBefore + 40, (float) Account::find(90001)->balance);
        $this->assertEquals($customerBefore - 40, (float) Customer::find(90001)->balance);
    }

    public function test_collecting_more_than_is_owed_is_refused_and_writes_nothing(): void
    {
        $order = $this->unpaidInvoice();

        $accountBefore = (float) Account::find(90001)->balance;
        $entries       = Transection::count();

        $this->collect($order->id, ['amount' => 99999])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertEquals(0, (float) Order::find($order->id)->collected_cash);
        $this->assertEquals($accountBefore, (float) Account::find(90001)->balance);
        $this->assertSame($entries, Transection::count());
    }

    public function test_a_fully_paid_invoice_cannot_be_collected_again(): void
    {
        $order = $this->unpaidInvoice();
        $total = round((float) $order->order_amount, 2);

        $this->collect($order->id, ['amount' => $total])->assertOk();

        // Nothing left to collect, so even one more unit is refused.
        $this->collect($order->id, ['amount' => 1])->assertStatus(422);

        $this->assertEquals($total, (float) Order::find($order->id)->collected_cash);
    }

    public function test_a_return_invoice_cannot_be_collected(): void
    {
        $returnId = $this->asSeller()->postJson('/api/v2/orders', [
            'user_id' => 90001, 'order_type' => 7,
            'cart' => [['id' => 90001, 'quantity' => 1, 'price' => 50]],
            'collected_cash' => 50,
        ])->assertStatus(201)->json('data.id');

        $this->collect($returnId, ['amount' => 10])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_the_request_is_validated(): void
    {
        $order = $this->unpaidInvoice();

        $this->asSeller()->postJson("/api/v2/orders/{$order->id}/collect", [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['amount', 'account_id', 'date']]);

        $this->collect($order->id, ['amount' => 0])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['amount']]);

        $this->collect($order->id, ['account_id' => 99999999])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['account_id']]);
    }

    public function test_the_payment_status_filter_agrees_with_the_collection(): void
    {
        $order = $this->unpaidInvoice();
        $total = round((float) $order->order_amount, 2);

        $this->asSeller()->getJson('/api/v2/orders?payment_status=unpaid')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->collect($order->id, ['amount' => $total])->assertOk();

        // The endpoint and the list filter must reach the same conclusion.
        $this->asSeller()->getJson('/api/v2/orders?payment_status=paid')
            ->assertOk()->assertJsonPath('data.0.id', $order->id);

        $this->asSeller()->getJson('/api/v2/orders?payment_status=unpaid')
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_another_sellers_invoice_cannot_be_collected(): void
    {
        $foreign = Order::create([
            'owner_id' => 999999, 'user_id' => 90001, 'type' => '4',
            'order_amount' => 100, 'total_tax' => 0, 'collected_cash' => 0, 'update_flag' => 0,
        ]);

        $this->collect($foreign->id, ['amount' => 10])->assertStatus(403);
        $this->assertEquals(0, (float) Order::find($foreign->id)->collected_cash);
    }

    public function test_a_receipt_photo_can_be_attached(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $order = $this->unpaidInvoice();

        $this->asSeller()->post("/api/v2/orders/{$order->id}/collect", [
            'amount' => 30, 'account_id' => 90001, 'date' => now()->toDateString(),
            'img' => \Illuminate\Http\UploadedFile::fake()->image('receipt.png'),
        ], ['Accept' => 'application/json'])->assertOk();

        // Scope to the Receivable entry: the sale itself also has a row
        // against this order, and it legitimately carries no slip.
        $this->assertNotNull(
            Transection::where('order_id', $order->id)
                ->where('tran_type', 'Receivable')->value('img'),
            'the slip should be stored on the collection entry'
        );
    }

    public function test_collection_requires_authentication(): void
    {
        $this->postJson('/api/v2/orders/1/collect', [])->assertStatus(401);
    }
}
