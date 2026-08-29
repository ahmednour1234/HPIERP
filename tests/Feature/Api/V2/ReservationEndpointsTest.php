<?php

namespace Tests\Feature\Api\V2;

use App\Models\CurrentReserveProduct;
use App\Models\ReserveProduct;
use Database\Seeders\ApiTestingSeeder;
use Tests\Feature\Api\ApiTestCase;

/**
 * Stock requests a seller files from the app.
 *
 * The lines live as a JSON blob on `data`, so the tests lean on the decoding
 * and the server-side pricing as much as the routing.
 */
class ReservationEndpointsTest extends ApiTestCase
{
    private function place(array $overrides = [])
    {
        return $this->asSeller()->postJson('/api/v2/reservations', array_merge([
            'customer_id' => 90001,
            'type'        => '4',
            'note'        => 'محتاج الكمية دي بكرة',
            'data'        => [['product_id' => 90001, 'stock' => 10]],
        ], $overrides));
    }

    public function test_a_reservation_is_filed_with_its_note(): void
    {
        $response = $this->place()->assertStatus(201);

        $response->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Reservation submitted')
            ->assertJsonPath('data.note', 'محتاج الكمية دي بكرة')
            ->assertJsonPath('data.type', '4')
            ->assertJsonPath('data.type_text', 'request')
            ->assertJsonPath('data.status_text', 'pending')
            ->assertJsonPath('data.items_count', 1)
            ->assertJsonStructure([
                'data' => [
                    'id', 'type', 'note', 'date', 'customer', 'items_count', 'total',
                    'items' => [['product_id', 'product_name', 'product_code',
                                 'quantity', 'price', 'line_total']],
                ],
            ]);
    }

    public function test_the_note_is_stored_on_both_tables(): void
    {
        $id = $this->place(['note' => 'ملاحظة الاختبار'])->json('data.id');

        // current_reserve_products is the working copy the settlement screen
        // reads; the note has to reach it too.
        $this->assertSame('ملاحظة الاختبار', ReserveProduct::find($id)->note);
        $this->assertSame('ملاحظة الاختبار', CurrentReserveProduct::find($id)->note);
    }

    public function test_a_reservation_without_a_note_is_accepted(): void
    {
        $this->place(['note' => null])
            ->assertStatus(201)
            ->assertJsonPath('data.note', null);
    }

    public function test_prices_come_from_the_server_not_the_client(): void
    {
        \App\Models\CustomerPrice::create([
            'local_id' => 0, 'customer_id' => 90001, 'product_id' => 90001, 'price' => 42,
        ]);

        // The client sends no price at all; a negotiated one is used.
        $response = $this->place(['data' => [['product_id' => 90001, 'stock' => 2]]])
            ->assertStatus(201);

        $this->assertEquals(42, $response->json('data.items.0.price'));
        $this->assertEquals(84, $response->json('data.total'));
    }

    public function test_the_line_total_and_order_total_agree(): void
    {
        $response = $this->place(['data' => [
            ['product_id' => 90001, 'stock' => 3],
        ]])->assertStatus(201);

        $items = $response->json('data.items');
        $this->assertEquals(
            array_sum(array_column($items, 'line_total')),
            $response->json('data.total')
        );
    }

    public function test_the_request_is_validated(): void
    {
        $this->asSeller()->postJson('/api/v2/reservations', [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['data']]);

        $this->place(['data' => [['product_id' => 90001, 'stock' => 0]]])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['data.0.stock']]);

        $this->place(['data' => [['product_id' => 99999999, 'stock' => 1]]])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['data.0.product_id']]);

        $this->place(['type' => '99'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['type']]);
    }

    public function test_a_json_encoded_payload_from_an_older_client_is_accepted(): void
    {
        $this->asSeller()->postJson('/api/v2/reservations', [
            'customer_id' => 90001,
            'data' => json_encode([['product_id' => 90001, 'stock' => 4]]),
        ])->assertStatus(201)->assertJsonPath('data.items_count', 1);
    }

    public function test_a_customer_of_another_seller_is_refused(): void
    {
        $foreign = \App\Models\Customer::create([
            'local_id' => 0, 'name' => 'Not Mine', 'mobile' => '0109',
        ]);

        $this->place(['customer_id' => $foreign->id])->assertStatus(403);
    }

    public function test_a_return_request_cannot_exceed_what_is_held(): void
    {
        // balance is what the seller holds; stock is what they want to send back.
        $this->place([
            'type' => '7',
            'data' => [['product_id' => 90001, 'stock' => 50, 'balance' => 5]],
        ])->assertStatus(422)->assertJsonPath('success', false);

        $this->place([
            'type' => '7',
            'data' => [['product_id' => 90001, 'stock' => 5, 'balance' => 50]],
        ])->assertStatus(201)->assertJsonPath('data.type_text', 'return');
    }

    public function test_reservations_are_listed_and_filtered(): void
    {
        $this->place(['type' => '4'])->assertStatus(201);
        $this->place(['type' => '7', 'data' => [['product_id' => 90001, 'stock' => 1, 'balance' => 9]]])
            ->assertStatus(201);

        $this->asSeller()->getJson('/api/v2/reservations')
            ->assertOk()->assertJsonCount(2, 'data');

        $this->asSeller()->getJson('/api/v2/reservations?type=4')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->asSeller()->getJson('/api/v2/reservations?search=' . urlencode('بكرة'))
            ->assertOk()->assertJsonCount(2, 'data');

        $this->asSeller()->getJson('/api/v2/reservations?search=no-such-note')
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_a_seller_only_sees_their_own_reservations(): void
    {
        $mine = $this->place()->json('data.id');

        $foreign = ReserveProduct::create([
            'data' => '[]', 'seller_id' => 999999, 'date' => now()->toDateString(),
            'type' => '4', 'active' => 1, 'update_flag' => 0,
        ]);

        $ids = collect($this->asSeller()->getJson('/api/v2/reservations')->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($mine));
        $this->assertFalse($ids->contains($foreign->id));

        $this->asSeller()->getJson("/api/v2/reservations/{$foreign->id}")->assertStatus(403);
    }

    public function test_reservation_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v2/reservations')->assertStatus(401);
        $this->postJson('/api/v2/reservations', [])->assertStatus(401);
    }
}
