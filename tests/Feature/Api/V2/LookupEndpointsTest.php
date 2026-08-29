<?php

namespace Tests\Feature\Api\V2;

use Tests\Feature\Api\ApiTestCase;

/**
 * The five lookup modules share one controller base, so they are covered by
 * one data-driven test rather than five near-identical files.
 */
class LookupEndpointsTest extends ApiTestCase
{
    /** prefix => [valid create payload, field to update, new value] */
    private function modules(): array
    {
        return [
            'brands'     => [['name' => 'Acme Pharma'], 'name', 'Renamed Brand'],
            'units'      => [['unit_type' => 'Carton', 'symbol' => 'CTN'], 'symbol', 'BOX'],
            'accounts'   => [['account' => 'Petty Cash', 'account_number' => 'PC-1'], 'account', 'Main Cash'],
            'categories' => [['name' => 'Antibiotics'], 'name', 'Painkillers'],
            'coupons'    => [['title' => 'Eid Offer', 'code' => 'EID25'], 'title', 'Ramadan Offer'],
        ];
    }

    public function test_every_lookup_module_lists_in_the_envelope(): void
    {
        foreach (array_keys($this->modules()) as $prefix) {
            $this->asSeller()
                ->getJson("/api/v2/{$prefix}")
                ->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonStructure(['success', 'message', 'data', 'meta' => ['current_page', 'per_page', 'total']]);
        }
    }

    public function test_every_lookup_module_validates_on_create(): void
    {
        foreach (array_keys($this->modules()) as $prefix) {
            $this->asSeller()
                ->postJson("/api/v2/{$prefix}", [])
                ->assertStatus(422)
                ->assertJsonPath('success', false)
                ->assertJsonStructure(['success', 'message', 'errors']);
        }
    }

    public function test_every_lookup_module_supports_the_full_crud_cycle(): void
    {
        foreach ($this->modules() as $prefix => [$payload, $field, $newValue]) {
            // create
            $created = $this->asSeller()->postJson("/api/v2/{$prefix}", $payload)
                ->assertStatus(201)
                ->assertJsonPath('success', true);

            $id = $created->json('data.id');
            $this->assertNotNull($id, "{$prefix}: create should return an id");

            // read
            $this->asSeller()->getJson("/api/v2/{$prefix}/{$id}")
                ->assertOk()
                ->assertJsonPath('data.id', $id);

            // update
            $this->asSeller()
                ->putJson("/api/v2/{$prefix}", ['id' => $id, $field => $newValue])
                ->assertOk()
                ->assertJsonPath("data.{$field}", $newValue);

            // delete
            $this->asSeller()->deleteJson("/api/v2/{$prefix}/{$id}")
                ->assertOk()
                ->assertJsonPath('success', true);

            // gone
            $this->asSeller()->getJson("/api/v2/{$prefix}/{$id}")
                ->assertStatus(404)
                ->assertJsonPath('success', false);
        }
    }

    public function test_searching_a_lookup_module_narrows_results(): void
    {
        $this->asSeller()->postJson('/api/v2/brands', ['name' => 'Findable Brand'])->assertStatus(201);

        $this->asSeller()->getJson('/api/v2/brands?search=Findable')
            ->assertOk()->assertJsonPath('data.0.name', 'Findable Brand');

        $this->asSeller()->getJson('/api/v2/brands?search=zzz-nope')
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_status_toggles_where_the_table_has_one(): void
    {
        $id = $this->asSeller()
            ->postJson('/api/v2/categories', ['name' => 'Toggle Me', 'status' => true])
            ->json('data.id');

        $this->asSeller()->patchJson("/api/v2/categories/{$id}/status")
            ->assertOk()
            ->assertJsonPath('data.status', false);

        $this->asSeller()->patchJson("/api/v2/categories/{$id}/status")
            ->assertOk()
            ->assertJsonPath('data.status', true);
    }

    public function test_status_toggle_is_rejected_where_the_table_has_none(): void
    {
        $id = $this->asSeller()->postJson('/api/v2/brands', ['name' => 'No Status'])->json('data.id');

        $this->asSeller()->patchJson("/api/v2/brands/{$id}/status")
            ->assertStatus(400)
            ->assertJsonPath('success', false);
    }

    public function test_coupon_rejects_an_expiry_before_its_start(): void
    {
        $this->asSeller()
            ->postJson('/api/v2/coupons', [
                'title' => 'Bad Dates', 'code' => 'BAD',
                'start_date' => '2026-06-01', 'expire_date' => '2026-01-01',
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['expire_date']]);
    }

    public function test_lookup_endpoints_require_authentication(): void
    {
        foreach (array_keys($this->modules()) as $prefix) {
            $this->getJson("/api/v2/{$prefix}")
                ->assertStatus(401)
                ->assertJsonPath('success', false);
        }
    }
}
