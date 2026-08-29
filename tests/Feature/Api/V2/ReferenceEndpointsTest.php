<?php

namespace Tests\Feature\Api\V2;

use Tests\Feature\Api\ApiTestCase;

class ReferenceEndpointsTest extends ApiTestCase
{
    public function test_regions_are_listed(): void
    {
        $this->asSeller()->getJson('/api/v2/regions')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => [['id', 'name']]]);
    }

    public function test_a_seller_sees_only_the_regions_assigned_to_them(): void
    {
        \App\Models\Region::create(['local_id' => 0, 'name' => 'Unassigned Region']);

        $names = collect($this->asSeller()->getJson('/api/v2/regions/mine')->json('data'))->pluck('name');

        $this->assertTrue($names->contains('Test Region'));
        $this->assertFalse($names->contains('Unassigned Region'));
    }

    public function test_storages_and_documents_are_listed(): void
    {
        $this->asSeller()->getJson('/api/v2/storages')
            ->assertOk()->assertJsonStructure(['data' => [['id', 'name']]]);

        $this->asSeller()->getJson('/api/v2/documents')
            ->assertOk()->assertJsonPath('success', true);
    }

    public function test_regions_are_returned_as_a_full_list_not_paginated(): void
    {
        // The mobile guide tells clients not to build a paging loop here.
        $response = $this->asSeller()->getJson('/api/v2/regions')->assertOk();

        $this->assertNull($response->json('meta'));
    }

    public function test_categories_are_paginated(): void
    {
        $this->asSeller()->getJson('/api/v2/categories')
            ->assertOk()
            ->assertJsonStructure(['meta' => ['current_page', 'per_page', 'total', 'last_page']]);
    }

    public function test_the_categories_message_is_pluralised_correctly(): void
    {
        // $label . 's' produced "Categorys".
        $this->asSeller()->getJson('/api/v2/categories')
            ->assertOk()
            ->assertJsonPath('message', 'Categories retrieved');
    }

    public function test_sub_categories_are_listed_for_a_parent(): void
    {
        $parent = $this->asSeller()->postJson('/api/v2/categories', ['name' => 'Parent Cat'])
            ->assertStatus(201)->json('data.id');

        $this->asSeller()->postJson("/api/v2/categories/{$parent}/children", ['name' => 'Child Cat'])
            ->assertStatus(201)
            ->assertJsonPath('data.parent_id', $parent);

        $this->asSeller()->getJson("/api/v2/categories/{$parent}/children")
            ->assertOk()
            ->assertJsonPath('message', 'Sub-categories retrieved')
            ->assertJsonPath('data.0.name', 'Child Cat');
    }

    public function test_reference_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v2/regions')->assertStatus(401);
        $this->getJson('/api/v2/storages')->assertStatus(401);
    }
}
