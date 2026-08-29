<?php

namespace Tests\Feature\Api\V2;

use App\Models\Product;
use Tests\Feature\Api\ApiTestCase;

/**
 * The product module, including create/update — endpoints v1 advertised but
 * never implemented (its routes pointed at methods that do not exist).
 */
class ProductEndpointsTest extends ApiTestCase
{
    public function test_listing_returns_the_envelope(): void
    {
        $this->asSeller()
            ->getJson('/api/v2/products')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success', 'message',
                'data' => [['id', 'name', 'product_code', 'selling_price']],
                'meta' => ['current_page', 'per_page', 'total'],
            ]);
    }

    public function test_listing_can_be_searched(): void
    {
        $this->asSeller()
            ->getJson('/api/v2/products?search=TP-0001')
            ->assertOk()
            ->assertJsonPath('data.0.product_code', 'TP-0001');

        $this->asSeller()
            ->getJson('/api/v2/products?search=no-such-product-xyz')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_a_product_can_be_fetched_by_id_and_by_code(): void
    {
        $this->asSeller()->getJson('/api/v2/products/90001')
            ->assertOk()->assertJsonPath('data.id', 90001);

        $this->asSeller()->getJson('/api/v2/products/by-code?code=TP-0001')
            ->assertOk()->assertJsonPath('data.id', 90001);
    }

    public function test_an_unknown_code_answers_404_not_500(): void
    {
        $this->asSeller()
            ->getJson('/api/v2/products/by-code?code=NOPE')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_creating_a_product_validates_its_input(): void
    {
        $this->asSeller()
            ->postJson('/api/v2/products', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['name', 'product_code', 'purchase_price', 'selling_price']]);
    }

    public function test_creating_a_product_rejects_a_duplicate_code(): void
    {
        $this->asSeller()
            ->postJson('/api/v2/products', [
                'name' => 'Another', 'product_code' => 'TP-0001',
                'purchase_price' => 1, 'selling_price' => 2,
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['product_code']]);
    }

    public function test_a_product_is_created(): void
    {
        $response = $this->asSeller()->postJson('/api/v2/products', [
            'name'           => 'Paracetamol 500mg',
            'name_en'        => 'Paracetamol 500mg',
            'product_code'   => 'PARA-500',
            'purchase_price' => 12.5,
            'selling_price'  => 20,
            'quantity'       => 100,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Paracetamol 500mg');

        $this->assertDatabaseHas('products', ['product_code' => 'PARA-500']);

        // The tiered price columns are NOT NULL; they default to the base price.
        $created = Product::where('product_code', 'PARA-500')->first();
        $this->assertEquals(20, $created->selling_price1);
        $this->assertEquals(12.5, $created->purchase_price1);
    }

    public function test_a_product_is_updated(): void
    {
        $this->asSeller()
            ->putJson('/api/v2/products', ['id' => 90001, 'selling_price' => 88])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertEquals(88, Product::find(90001)->selling_price);
    }

    public function test_updating_an_unknown_product_is_rejected(): void
    {
        $this->asSeller()
            ->putJson('/api/v2/products', ['id' => 99999999, 'selling_price' => 5])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['id']]);
    }

    public function test_low_stock_listing_works(): void
    {
        // limit_stock defaults to 10; put this product under it.
        Product::where('id', 90001)->update(['quantity' => 2, 'limit_stock' => 10]);

        $this->asSeller()
            ->getJson('/api/v2/products/low-stock')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', 90001);
    }

    public function test_customer_prices_requires_a_cart(): void
    {
        // The v1 equivalent fataled here with "foreach() argument must be of
        // type array|object, null given".
        $this->asSeller()
            ->postJson('/api/v2/products/customer-prices', ['user_id' => 90001])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['cart']]);
    }

    public function test_customer_prices_fall_back_to_the_catalogue_price(): void
    {
        $this->asSeller()
            ->postJson('/api/v2/products/customer-prices', [
                'user_id' => 90001,
                'cart'    => [90001],
            ])
            ->assertOk()
            ->assertJsonPath('data.0.id', 90001)
            ->assertJsonPath('data.0.custom', false);

        // Numeric compare: SQLite returns an int where MySQL gives a float.
        $this->assertEquals(75, $this->asSeller()
            ->postJson('/api/v2/products/customer-prices', ['user_id' => 90001, 'cart' => [90001]])
            ->json('data.0.price'));
    }

    public function test_a_customer_price_can_be_set_and_is_then_returned(): void
    {
        $this->asSeller()
            ->postJson('/api/v2/products/customer-price', [
                'customer_id' => 90001, 'product_id' => 90001, 'price' => 61.5,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('customer_prices', [
            'customer_id' => 90001, 'product_id' => 90001,
        ]);

        $this->asSeller()
            ->postJson('/api/v2/products/customer-prices', [
                'user_id' => 90001, 'cart' => [90001],
            ])
            ->assertOk()
            ->assertJsonPath('data.0.custom', true)
            ->assertJsonPath('data.0.price', 61.5); // stored as a real, comes back as one
    }

    public function test_product_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v2/products')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }
}
