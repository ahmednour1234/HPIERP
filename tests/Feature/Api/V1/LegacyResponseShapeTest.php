<?php

namespace Tests\Feature\Api\V1;

use Tests\Feature\Api\ApiTestCase;

/**
 * Guards the promise that the refactor left v1 alone.
 *
 * These assert the *legacy* payload shapes, so if a future change starts
 * wrapping v1 in the new envelope, or swaps its error format, these fail.
 */
class LegacyResponseShapeTest extends ApiTestCase
{
    public function test_v1_auth_failure_keeps_the_legacy_error_shape(): void
    {
        $this->getJson('/api/v1/stocks/list')
            ->assertStatus(401)
            ->assertJsonStructure(['errors' => [['code', 'message']]])
            ->assertJsonPath('errors.0.code', 'auth-001')
            ->assertJsonMissingPath('success');
    }

    public function test_v1_login_validation_keeps_its_legacy_403_shape(): void
    {
        $this->postJson('/api/v1/login', [])
            ->assertStatus(403)
            ->assertJsonStructure(['errors' => [['code', 'message']]])
            ->assertJsonMissingPath('success');
    }

    public function test_v1_login_succeeds_with_the_seeded_credentials(): void
    {
        $this->createPersonalAccessClient();

        $response = $this->postJson('/api/v1/login', [
            'code'     => \Database\Seeders\ApiTestingSeeder::SELLER_CODE,
            'password' => \Database\Seeders\ApiTestingSeeder::PASSWORD,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['message', 'token', 'admin', 'kilometer'])
            ->assertJsonPath('message', 'You are logged in');
    }

    public function test_v1_login_rejects_a_wrong_password(): void
    {
        $this->postJson('/api/v1/login', [
            'code'     => \Database\Seeders\ApiTestingSeeder::SELLER_CODE,
            'password' => 'wrong-password',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Password mismatch');
    }

    public function test_v1_stock_list_keeps_its_unwrapped_payload(): void
    {
        $this->asSeller()
            ->getJson('/api/v1/stocks/list?type=4')
            ->assertOk()
            // Legacy shape: top-level keys, no success/data envelope.
            ->assertJsonStructure(['limit', 'offset', 'stocks', 'current_page', 'last_page'])
            ->assertJsonMissingPath('success');
    }

    public function test_unknown_api_routes_still_answer_the_legacy_404(): void
    {
        $this->getJson('/api/v1/no-such-endpoint')
            ->assertStatus(404)
            ->assertJsonPath('message', 'Not Found.');
    }
}
