<?php

namespace Tests\Feature\Api\V2;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Stock;
use App\Models\Transection;
use Database\Seeders\ApiTestingSeeder;
use Tests\Feature\Api\ApiTestCase;

/**
 * Point of sale. These are the highest-risk writes in the system - an order
 * touches stock, the ledger and the customer balance at once - so the tests
 * lean on what must stay consistent when something goes wrong.
 */
class OrderEndpointsTest extends ApiTestCase
{
    private function cart(int $qty = 5, float $price = 75): array
    {
        return [['id' => 90001, 'quantity' => $qty, 'price' => $price]];
    }

    public function test_placing_a_sale_writes_the_order_its_lines_and_the_ledger(): void
    {
        $stockBefore = Stock::where('seller_id', ApiTestingSeeder::SELLER_ID)
            ->where('product_id', 90001)->value('stock');

        $response = $this->asSeller()->postJson('/api/v2/orders', [
            'user_id'        => 90001,
            'order_type'     => 4,
            'cart'           => $this->cart(5, 75),
            'collected_cash' => 375,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 4)
            ->assertJsonStructure(['data' => ['id', 'order_amount', 'details' => [['product_id', 'quantity', 'price']]]]);

        $orderId = $response->json('data.id');

        $this->assertDatabaseHas('orders', ['id' => $orderId, 'owner_id' => ApiTestingSeeder::SELLER_ID]);
        $this->assertSame(1, OrderDetail::where('order_id', $orderId)->count());

        // Stock comes down by exactly what was sold.
        $this->assertEquals(
            $stockBefore - 5,
            Stock::where('seller_id', ApiTestingSeeder::SELLER_ID)->where('product_id', 90001)->value('stock')
        );

        // And the sale is on the ledger.
        $this->assertDatabaseHas('transections', ['order_id' => $orderId, 'customer_id' => 90001]);
    }

    public function test_a_sale_beyond_available_stock_is_rejected_and_changes_nothing(): void
    {
        $stockBefore  = Stock::where('seller_id', ApiTestingSeeder::SELLER_ID)
            ->where('product_id', 90001)->value('stock');
        $orders       = Order::count();
        $transactions = Transection::count();

        $this->asSeller()->postJson('/api/v2/orders', [
            'user_id'    => 90001,
            'order_type' => 4,
            'cart'       => $this->cart($stockBefore + 100),
        ])->assertStatus(422)->assertJsonPath('success', false);

        // v1 decremented stock before its try block, so a later failure left
        // stock wrong with no order to explain it.
        $this->assertEquals(
            $stockBefore,
            Stock::where('seller_id', ApiTestingSeeder::SELLER_ID)->where('product_id', 90001)->value('stock')
        );
        $this->assertSame($orders, Order::count());
        $this->assertSame($transactions, Transection::count());
    }

    public function test_a_return_puts_stock_back_and_credits_the_customer(): void
    {
        $stockBefore   = Stock::where('seller_id', ApiTestingSeeder::SELLER_ID)
            ->where('product_id', 90001)->value('stock');
        $balanceBefore = Customer::find(90001)->balance;

        $this->asSeller()->postJson('/api/v2/orders', [
            'user_id'    => 90001,
            'order_type' => 7,
            'cart'       => $this->cart(3, 75),
            'collected_cash' => 225,
        ])->assertStatus(201)->assertJsonPath('data.type', 7);

        $this->assertEquals(
            $stockBefore + 3,
            Stock::where('seller_id', ApiTestingSeeder::SELLER_ID)->where('product_id', 90001)->value('stock')
        );
        $this->assertGreaterThan($balanceBefore, Customer::find(90001)->balance);
    }

    public function test_the_cart_is_validated(): void
    {
        $this->asSeller()->postJson('/api/v2/orders', ['user_id' => 90001])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['cart']]);

        $this->asSeller()->postJson('/api/v2/orders', [
            'user_id' => 90001, 'cart' => [['id' => 90001, 'quantity' => 0]],
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['cart.0.quantity']]);

        $this->asSeller()->postJson('/api/v2/orders', [
            'user_id' => 90001, 'cart' => [['id' => 99999999, 'quantity' => 1]],
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['cart.0.id']]);
    }

    public function test_a_json_encoded_cart_from_an_older_client_is_accepted(): void
    {
        // v1 clients send the cart as a string; prepareForValidation decodes it.
        $this->asSeller()->postJson('/api/v2/orders', [
            'user_id'    => 90001,
            'order_type' => 4,
            'cart'       => json_encode($this->cart(2, 75)),
        ])->assertStatus(201);
    }

    public function test_an_unknown_customer_is_rejected(): void
    {
        $this->asSeller()->postJson('/api/v2/orders', [
            'user_id' => 99999999, 'cart' => $this->cart(),
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['user_id']]);
    }

    public function test_the_customer_price_beats_the_price_the_client_sent(): void
    {
        \App\Models\CustomerPrice::create([
            'local_id' => 0, 'customer_id' => 90001, 'product_id' => 90001, 'price' => 40,
        ]);

        $response = $this->asSeller()->postJson('/api/v2/orders', [
            'user_id' => 90001, 'order_type' => 4,
            'cart'    => $this->cart(2, 999), // client claims 999
        ])->assertStatus(201);

        // The negotiated price wins, so a tampered client cannot set its own.
        $this->assertEquals(40, $response->json('data.details.0.price'));
    }

    public function test_orders_are_listed_and_filtered_for_this_seller_only(): void
    {
        $this->asSeller()->postJson('/api/v2/orders', [
            'user_id' => 90001, 'order_type' => 4, 'cart' => $this->cart(1),
        ])->assertStatus(201);

        $this->asSeller()->getJson('/api/v2/orders')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => [['id', 'type', 'order_amount']], 'meta']);

        $this->asSeller()->getJson('/api/v2/orders?type=4')->assertOk()->assertJsonPath('data.0.type', 4);
        // 99 is not an order type. It used to come back as an empty list,
        // which reads like "no orders" rather than "bad filter"; the type rule
        // now rejects it so the app can tell the two apart.
        $this->asSeller()->getJson('/api/v2/orders?type=99')
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['type']]);

        // A valid type with no matching rows is still an empty list.
        $this->asSeller()->getJson('/api/v2/orders?type=7')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_an_invoice_can_be_read_by_its_owner_only(): void
    {
        $id = $this->asSeller()->postJson('/api/v2/orders', [
            'user_id' => 90001, 'order_type' => 4, 'cart' => $this->cart(1),
        ])->json('data.id');

        $this->asSeller()->getJson("/api/v2/orders/{$id}")
            ->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonStructure(['data' => ['details', 'customer']]);

        // An order belonging to someone else is refused, not returned.
        $foreign = Order::create([
            'owner_id' => 999999, 'user_id' => 90001, 'type' => 4,
            'total_tax' => 0, 'order_amount' => 10, 'update_flag' => 0,
        ]);

        $this->asSeller()->getJson("/api/v2/orders/{$foreign->id}")
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_customer_order_history_requires_the_customer_to_be_assigned(): void
    {
        $this->asSeller()->getJson('/api/v2/orders/customers/90001')->assertOk();

        $foreign = Customer::create(['local_id' => 0, 'name' => 'Other', 'mobile' => '0111']);
        $this->asSeller()->getJson("/api/v2/orders/customers/{$foreign->id}")->assertStatus(403);
    }

    public function test_a_percentage_discount_is_applied_per_line(): void
    {
        $response = $this->asSeller()->postJson('/api/v2/orders', [
            'user_id' => 90001, 'order_type' => 4,
            'cart' => [[
                'id' => 90001, 'quantity' => 2, 'price' => 100,
                'discount' => 10, 'discount_type' => 'percent', 'tax' => 5,
            ]],
            'collected_cash' => 200,
        ])->assertStatus(201);

        // 10% of 100 is 10 per unit, and the type is echoed back.
        $this->assertEquals(10, $response->json('data.details.0.discount_on_product'));
        $this->assertSame('percent', $response->json('data.details.0.discount_type'));

        // 200 sold - 20 discount + 10 tax.
        $this->assertEquals(190, $response->json('data.order_amount'));
    }

    public function test_a_flat_discount_is_taken_as_given(): void
    {
        $response = $this->asSeller()->postJson('/api/v2/orders', [
            'user_id' => 90001, 'order_type' => 4,
            'cart' => [[
                'id' => 90001, 'quantity' => 2, 'price' => 100,
                'discount' => 15, 'discount_type' => 'amount', 'tax' => 0,
            ]],
            'collected_cash' => 200,
        ])->assertStatus(201);

        $this->assertEquals(15, $response->json('data.details.0.discount_on_product'));
        $this->assertEquals(170, $response->json('data.order_amount'));   // 200 - 30
    }

    public function test_a_line_without_a_discount_falls_back_to_the_products_own(): void
    {
        $response = $this->asSeller()->postJson('/api/v2/orders', [
            'user_id' => 90001, 'order_type' => 4,
            'cart' => [['id' => 90001, 'quantity' => 1, 'price' => 100]],
            'collected_cash' => 100,
        ])->assertStatus(201);

        // The fixture product carries tax 14 and no discount.
        $this->assertEquals(14, $response->json('data.details.0.tax_amount'));
        $this->assertEquals(0, $response->json('data.details.0.discount_on_product'));
    }

    public function test_a_negative_discount_is_rejected(): void
    {
        $this->asSeller()->postJson('/api/v2/orders', [
            'user_id' => 90001,
            'cart' => [['id' => 90001, 'quantity' => 1, 'price' => 100, 'discount' => -5]],
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['cart.0.discount']]);

        $this->asSeller()->postJson('/api/v2/orders', [
            'user_id' => 90001,
            'cart' => [['id' => 90001, 'quantity' => 1, 'discount_type' => 'sideways']],
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['cart.0.discount_type']]);
    }

    public function test_a_receipt_photo_is_stored_on_the_order_and_its_ledger_entry(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $response = $this->asSeller()->post('/api/v2/orders', [
            'user_id' => 90001, 'order_type' => 4, 'collected_cash' => 100,
            'cart' => [['id' => 90001, 'quantity' => 1, 'price' => 100]],
            'img'  => \Illuminate\Http\UploadedFile::fake()->image('slip.png'),
        ], ['Accept' => 'application/json'])->assertStatus(201);

        $image = $response->json('data.img');
        $this->assertNotNull($image, 'the order should carry the uploaded slip');

        // `img` was missing from Transection::$fillable, so the same slip never
        // reached the ledger entry.
        $this->assertDatabaseHas('transections', [
            'order_id' => $response->json('data.id'),
            'img'      => $image,
        ]);
    }

    public function test_an_order_without_a_photo_is_still_accepted(): void
    {
        $this->asSeller()->postJson('/api/v2/orders', [
            'user_id' => 90001, 'order_type' => 4,
            'cart' => [['id' => 90001, 'quantity' => 1, 'price' => 100]],
        ])->assertStatus(201)->assertJsonPath('data.img', null);
    }

    public function test_order_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v2/orders')->assertStatus(401);
        $this->postJson('/api/v2/orders', [])->assertStatus(401);
    }
}
