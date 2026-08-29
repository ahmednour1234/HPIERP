<?php

namespace Tests\Feature\Api;

use Database\Seeders\ApiTestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * Base class for the API feature tests.
 *
 * Builds the schema from the migrations into an in-memory SQLite database and
 * seeds the fixture rows, so tests never read or write the production MySQL
 * database configured in .env.
 */
abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected $seller;

    protected function setUp(): void
    {
        parent::setUp();

        // The api group throttles to 60/min, which a full sweep would trip.
        // Rate limiting has its own test rather than skewing every other one.
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        Artisan::call('db:seed', ['--class' => ApiTestingSeeder::class, '--force' => true]);

        $this->seller = \App\Models\Admin::find(ApiTestingSeeder::SELLER_ID);
    }

    /**
     * Act as the fixture seller on the guard the API routes use.
     *
     * Passport::actingAs bypasses real token issuance, which keeps the tests
     * independent of the oauth key files.
     */
    protected function asSeller(): self
    {
        Passport::actingAs($this->seller, ['*'], 'admin-api');

        return $this;
    }

    /** Standard JSON headers for every request. */
    protected function jsonHeaders(): array
    {
        return ['Accept' => 'application/json'];
    }

    /**
     * Register a Passport personal access client.
     *
     * Only needed by tests that exercise the real login endpoint, which issues
     * a token via createToken(); Passport::actingAs does not.
     */
    protected function createPersonalAccessClient(): void
    {
        $client = \Laravel\Passport\Client::create([
            'name'                   => 'Test Personal Access Client',
            'secret'                 => \Illuminate\Support\Str::random(40),
            'redirect'               => 'http://localhost',
            'personal_access_client' => true,
            'password_client'        => false,
            'revoked'                => false,
        ]);

        \Laravel\Passport\PersonalAccessClient::create(['client_id' => $client->id]);

        // Token issuance signs with the oauth keys; generate them if absent.
        if (!file_exists(storage_path('oauth-private.key'))) {
            \Illuminate\Support\Facades\Artisan::call('passport:keys', ['--force' => true]);
        }
    }
}
