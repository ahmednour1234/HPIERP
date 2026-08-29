<?php

namespace Tests\Feature\Api\V2;

use App\Models\Customer;
use App\Models\ResultVisitor;
use Database\Seeders\ApiTestingSeeder;
use Tests\Feature\Api\ApiTestCase;

/**
 * The field-seller surface: visits, visit outcomes, attendance and profile.
 */
class FieldEndpointsTest extends ApiTestCase
{
    /* ---------------- visits ---------------- */

    public function test_a_visit_can_be_planned_for_an_assigned_customer(): void
    {
        $this->asSeller()
            ->postJson('/api/v2/visits', [
                'customer_id' => 90001,
                'date'        => '2026-03-01',
                'note'        => 'Quarterly stock review',
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.customer_id', 90001);

        $this->assertDatabaseHas('visitors', [
            'customer_id' => 90001, 'seller_id' => ApiTestingSeeder::SELLER_ID,
        ]);
    }

    public function test_planning_a_visit_is_validated(): void
    {
        $this->asSeller()->postJson('/api/v2/visits', [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['customer_id', 'date']]);
    }

    public function test_a_seller_cannot_plan_a_visit_for_someone_elses_customer(): void
    {
        $foreign = Customer::create(['local_id' => 0, 'name' => 'Other', 'mobile' => '0109']);

        // v1 checked the role but never the assignment.
        $this->asSeller()
            ->postJson('/api/v2/visits', ['customer_id' => $foreign->id, 'date' => '2026-03-01'])
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_planned_visits_are_listed_for_the_seller_only(): void
    {
        $this->asSeller()->postJson('/api/v2/visits', [
            'customer_id' => 90001, 'date' => '2026-03-02',
        ])->assertStatus(201);

        $this->asSeller()->getJson('/api/v2/visits')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.customer_id', 90001)
            ->assertJsonStructure(['data' => [['id', 'date', 'customer' => ['id', 'name']]], 'meta']);
    }

    public function test_visits_can_be_filtered_by_date(): void
    {
        $this->asSeller()->postJson('/api/v2/visits', ['customer_id' => 90001, 'date' => '2026-03-05'])
            ->assertStatus(201);

        $this->asSeller()->getJson('/api/v2/visits?date=2026-03-05')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->asSeller()->getJson('/api/v2/visits?date=2026-12-31')
            ->assertOk()->assertJsonCount(0, 'data');
    }

    /* ---------------- visit outcomes ---------------- */

    public function test_a_visit_result_is_recorded_with_its_location(): void
    {
        $this->asSeller()
            ->postJson('/api/v2/visits/results', [
                'customer_id' => 90001,
                'note'        => 'Order placed, restock in two weeks',
                'lat'         => 30.0444,
                'lang'        => 31.2357,
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.latitude', '30.0444')
            ->assertJsonPath('data.longitude', '31.2357');

        $this->assertDatabaseHas('result_visitors', [
            'customer_id' => 90001, 'admin_id' => ApiTestingSeeder::SELLER_ID,
        ]);
    }

    public function test_a_visit_result_requires_a_note(): void
    {
        $this->asSeller()
            ->postJson('/api/v2/visits/results', ['customer_id' => 90001])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['note']]);
    }

    public function test_a_visit_result_rejects_an_impossible_coordinate(): void
    {
        $this->asSeller()
            ->postJson('/api/v2/visits/results', [
                'customer_id' => 90001, 'note' => 'x', 'lat' => 999,
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['lat']]);
    }

    public function test_visit_results_are_listed(): void
    {
        ResultVisitor::create([
            'admin_id' => ApiTestingSeeder::SELLER_ID, 'customer_id' => 90001,
            'note' => 'Seen', 'lat' => '30', 'lang' => '31',
        ]);

        $this->asSeller()->getJson('/api/v2/visits/results')
            ->assertOk()->assertJsonPath('data.0.customer_id', 90001);

        $this->asSeller()->getJson('/api/v2/visits/customers/90001/results')
            ->assertOk()->assertJsonPath('data.0.customer_id', 90001);
    }

    public function test_results_of_an_unassigned_customer_are_refused(): void
    {
        $foreign = Customer::create(['local_id' => 0, 'name' => 'Other', 'mobile' => '0110']);

        $this->asSeller()->getJson("/api/v2/visits/customers/{$foreign->id}/results")
            ->assertStatus(403);
    }

    /* ---------------- attendance ---------------- */

    public function test_attendance_lists_only_the_signed_in_seller(): void
    {
        \App\Models\Attendance::create([
            'admin_id' => ApiTestingSeeder::SELLER_ID,
            'date' => '2026-03-01', 'check_in' => '09:00:00', 'status' => 1,
        ]);
        \App\Models\Attendance::create([
            'admin_id' => 999999, 'date' => '2026-03-01', 'check_in' => '09:00:00', 'status' => 1,
        ]);

        $response = $this->asSeller()->getJson('/api/v2/attendance')->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame(ApiTestingSeeder::SELLER_ID, $response->json('data.0.admin_id'));
    }

    public function test_attendance_rejects_a_reversed_date_range(): void
    {
        $this->asSeller()
            ->getJson('/api/v2/attendance?from=2026-05-01&to=2026-01-01')
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['to']]);
    }

    /* ---------------- profile ---------------- */

    public function test_profile_returns_the_seller_without_the_password(): void
    {
        $response = $this->asSeller()->getJson('/api/v2/profile')->assertOk();

        $response->assertJsonPath('data.id', ApiTestingSeeder::SELLER_ID)
            ->assertJsonPath('data.mandob_code', ApiTestingSeeder::SELLER_CODE);

        $this->assertArrayNotHasKey('password', $response->json('data'));
        $this->assertArrayNotHasKey('remember_token', $response->json('data'));
    }

    public function test_password_change_requires_the_current_password(): void
    {
        $this->asSeller()
            ->postJson('/api/v2/profile/change-password', [
                'current_password'      => 'wrong-one',
                'password'              => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_password_change_enforces_confirmation_and_length(): void
    {
        $this->asSeller()
            ->postJson('/api/v2/profile/change-password', [
                'current_password'      => ApiTestingSeeder::PASSWORD,
                'password'              => 'short',
                'password_confirmation' => 'different',
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['password']]);
    }

    public function test_the_password_can_be_changed(): void
    {
        $this->asSeller()
            ->postJson('/api/v2/profile/change-password', [
                'current_password'      => ApiTestingSeeder::PASSWORD,
                'password'              => 'a-brand-new-password',
                'password_confirmation' => 'a-brand-new-password',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check(
            'a-brand-new-password',
            \App\Models\Admin::find(ApiTestingSeeder::SELLER_ID)->password
        ));
    }

    public function test_field_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v2/visits')->assertStatus(401);
        $this->getJson('/api/v2/attendance')->assertStatus(401);
        $this->getJson('/api/v2/profile')->assertStatus(401);
    }
}
