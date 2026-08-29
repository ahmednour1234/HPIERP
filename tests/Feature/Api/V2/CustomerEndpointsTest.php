<?php

namespace Tests\Feature\Api\V2;

use Database\Seeders\ApiTestingSeeder;
use Tests\Feature\Api\ApiTestCase;

/**
 * The refactored customer module: envelope shape, validation, ownership.
 */
class CustomerEndpointsTest extends ApiTestCase
{
    public function test_listing_returns_the_standard_envelope_with_pagination(): void
    {
        $response = $this->asSeller()->getJson('/api/v2/customers');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [['id', 'name', 'mobile', 'balance']],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ])
            ->assertJson(['success' => true]);
    }

    public function test_listing_only_returns_customers_assigned_to_the_seller(): void
    {
        $response = $this->asSeller()->getJson('/api/v2/customers');

        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains(90001), 'fixture customer should be listed');

        // Customers imported from the dump belong to other sellers.
        $foreign = \App\Models\Customer::where('id', '!=', 90001)
            ->whereNotIn('id', \App\Models\SellerCustomer::where('seller_id', ApiTestingSeeder::SELLER_ID)
                ->pluck('customer_id'))
            ->first();

        if ($foreign) {
            $this->assertFalse(
                $ids->contains($foreign->id),
                'a customer belonging to another seller must not appear'
            );
        }
    }

    public function test_search_narrows_the_listing(): void
    {
        $this->asSeller()
            ->getJson('/api/v2/customers?search=Test%20Customer')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Test Customer');

        $this->asSeller()
            ->getJson('/api/v2/customers?search=zzz-no-such-customer')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_creating_a_customer_requires_name_and_mobile(): void
    {
        $response = $this->asSeller()->postJson('/api/v2/customers', []);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['success', 'message', 'errors' => ['name', 'mobile']]);
    }

    public function test_creating_a_customer_rejects_an_invalid_email(): void
    {
        $this->asSeller()
            ->postJson('/api/v2/customers', [
                'name'   => 'Nour',
                'mobile' => '01234567890',
                'email'  => 'not-an-email',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['email']]);
    }

    public function test_a_customer_is_created_and_linked_to_the_seller(): void
    {
        $response = $this->asSeller()->postJson('/api/v2/customers', [
            'name'      => 'Pharmacy Al Nour',
            'mobile'    => '01099887766',
            'email'     => 'nour@example.test',
            'region_id' => 90001,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Pharmacy Al Nour');

        $id = $response->json('data.id');

        $this->assertDatabaseHas('customers', ['id' => $id, 'mobile' => '01099887766']);
        $this->assertDatabaseHas('seller_customers', [
            'customer_id' => $id,
            'seller_id'   => ApiTestingSeeder::SELLER_ID,
        ]);
    }

    public function test_a_seller_cannot_read_a_customer_that_is_not_theirs(): void
    {
        $foreign = \App\Models\Customer::create([
            'local_id' => 0, 'name' => 'Someone Else', 'mobile' => '0100',
        ]);

        $this->asSeller()
            ->getJson('/api/v2/customers/' . $foreign->id)
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_a_missing_customer_answers_404_in_the_envelope(): void
    {
        $this->asSeller()
            ->getJson('/api/v2/customers/99999999')
            ->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message']);
    }

    public function test_a_customer_can_be_updated(): void
    {
        $this->asSeller()
            ->putJson('/api/v2/customers', ['id' => 90001, 'name' => 'Renamed Customer'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Renamed Customer');

        $this->assertDatabaseHas('customers', ['id' => 90001, 'name' => 'Renamed Customer']);
    }

    public function test_a_customer_can_be_deleted(): void
    {
        $this->asSeller()
            ->deleteJson('/api/v2/customers/90001')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('customers', ['id' => 90001]);
    }

    public function test_creating_a_customer_echoes_back_every_field_it_stored(): void
    {
        $response = $this->asSeller()->postJson('/api/v2/customers', [
            'name'        => 'Al Shifa Pharmacy',
            'mobile'      => '01555000999',
            'region_id'   => 90001,
            'type'        => 2,
            'category_id' => 3,
            'specialist'  => 1,
            'limit'       => 7500,
            'city'        => 'Tanta',
        ])->assertStatus(201);

        // whenLoaded() alone dropped the region from a freshly created record,
        // and the un-refreshed model reported active=false for a row the
        // database had stored as active.
        $response->assertJsonPath('data.region_id', 90001)
            ->assertJsonPath('data.region.id', 90001)
            ->assertJsonPath('data.type', 2)
            ->assertJsonPath('data.category_id', 3)
            ->assertJsonPath('data.specialist', 1)
            ->assertJsonPath('data.city', 'Tanta')
            ->assertJsonPath('data.active', true);

        $this->assertEquals(7500, $response->json('data.limit'));
    }

    public function test_updating_a_customer_returns_the_new_region(): void
    {
        \App\Models\Region::create(['local_id' => 0, 'name' => 'Second Region']);
        $newRegion = \App\Models\Region::where('name', 'Second Region')->value('id');

        $this->asSeller()
            ->putJson('/api/v2/customers', ['id' => 90001, 'region_id' => $newRegion, 'type' => 3])
            ->assertOk()
            ->assertJsonPath('data.region_id', $newRegion)
            ->assertJsonPath('data.region.id', $newRegion)
            ->assertJsonPath('data.type', 3);
    }

    public function test_a_customers_map_location_round_trips(): void
    {
        $response = $this->asSeller()->postJson('/api/v2/customers', [
            'name'      => 'Map Pharmacy',
            'mobile'    => '01555777888',
            'latitude'  => 30.0444,
            'longitude' => 31.2357,
        ])->assertStatus(201);

        // The columns are varchar; the resource casts them back to numbers.
        $this->assertSame(30.0444, $response->json('data.latitude'));
        $this->assertSame(31.2357, $response->json('data.longitude'));

        $this->asSeller()
            ->putJson('/api/v2/customers', [
                'id' => $response->json('data.id'), 'latitude' => 31.2001, 'longitude' => 29.9187,
            ])
            ->assertOk()
            ->assertJsonPath('data.latitude', 31.2001)
            ->assertJsonPath('data.longitude', 29.9187);
    }

    public function test_out_of_range_coordinates_are_rejected(): void
    {
        $this->asSeller()
            ->postJson('/api/v2/customers', [
                'name' => 'Bad', 'mobile' => '0199', 'latitude' => 200, 'longitude' => 400,
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['latitude', 'longitude']]);
    }

    public function test_a_customer_without_coordinates_returns_null(): void
    {
        $this->asSeller()
            ->getJson('/api/v2/customers/90001')
            ->assertOk()
            ->assertJsonPath('data.latitude', null)
            ->assertJsonPath('data.longitude', null);
    }

    public function test_add_balance_validates_its_input(): void
    {
        $this->asSeller()
            ->postJson('/api/v2/customers/add-balance', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['customer_id', 'account_id', 'amount', 'date']]);
    }

    public function test_add_balance_rejects_a_non_positive_amount(): void
    {
        $this->asSeller()
            ->postJson('/api/v2/customers/add-balance', [
                'customer_id' => 90001, 'account_id' => 90001,
                'amount' => 0, 'date' => '2026-01-01',
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['amount']]);
    }

    public function test_add_balance_records_the_payment_atomically(): void
    {
        $customerBefore = \App\Models\Customer::find(90001)->balance;
        $accountBefore  = \App\Models\Account::find(90001)->balance;

        $response = $this->asSeller()->postJson('/api/v2/customers/add-balance', [
            'customer_id' => 90001,
            'account_id'  => 90001,
            'amount'      => 250,
            'date'        => '2026-01-01',
            'description' => 'Cash payment',
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        // The money lands on the account and comes off what the customer owes.
        $this->assertEquals($accountBefore + 250, \App\Models\Account::find(90001)->balance);
        $this->assertEquals($customerBefore - 250, \App\Models\Customer::find(90001)->balance);

        // ...and the movement is recorded.
        $this->assertDatabaseHas('transections', [
            'customer_id' => 90001, 'account_id' => 90001, 'amount' => 250,
        ]);
    }

    public function test_add_balance_refuses_a_customer_of_another_seller(): void
    {
        $foreign = \App\Models\Customer::create([
            'local_id' => 0, 'name' => 'Not Mine', 'mobile' => '0102',
        ]);

        $this->asSeller()
            ->postJson('/api/v2/customers/add-balance', [
                'customer_id' => $foreign->id, 'account_id' => 90001,
                'amount' => 10, 'date' => '2026-01-01',
            ])
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_the_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v2/customers')
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthenticated');
    }
}
