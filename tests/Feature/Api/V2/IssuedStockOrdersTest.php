<?php

namespace Tests\Feature\Api\V2;

use Database\Seeders\ApiTestingSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\ApiTestCase;

/**
 * GET /reservations/issued — أوامر الصرف المنفَّذة للمندوب.
 *
 * أمر الصرف صف type = 3 يكتبه الأدمن بعد نقل الكميات إلى العربية، فهو
 * سجل لما نُفِّذ لا طلب معلّق قدّمه المندوب.
 */
class IssuedStockOrdersTest extends ApiTestCase
{
    public function test_it_lists_the_orders_issued_to_this_seller(): void
    {
        $id = $this->makeIssue([['product_id' => 90001, 'name' => 'Test Product', 'qty' => 120, 'price' => 75]]);

        $this->asSeller()
            ->getJson('/api/v2/reservations/issued')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $id)
            ->assertJsonPath('data.0.type_text', 'issue')
            ->assertJsonPath('data.0.status_text', 'executed')
            ->assertJsonPath('data.0.items_count', 1)
            ->assertJsonPath('data.0.items.0.product_id', 90001)
            ->assertJsonPath('data.0.items.0.quantity', fn ($v) => (float) $v === 120.0)
            ->assertJsonPath('data.0.total', fn ($v) => (float) $v === 9000.0);
    }

    public function test_it_does_not_list_another_sellers_orders(): void
    {
        $mine = $this->makeIssue([['product_id' => 90001, 'name' => 'Mine', 'qty' => 10, 'price' => 5]]);
        $this->makeIssue([['product_id' => 90001, 'name' => 'Theirs', 'qty' => 10, 'price' => 5]], sellerId: 999777);

        $this->asSeller()
            ->getJson('/api/v2/reservations/issued')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine);
    }

    /** الحجوزات والردود التي يقدّمها المندوب ليست أوامر صرف. */
    public function test_it_excludes_the_sellers_own_reservations(): void
    {
        $issue = $this->makeIssue([['product_id' => 90001, 'name' => 'Issued', 'qty' => 10, 'price' => 5]]);
        $this->makeReservation(type: 4);
        $this->makeReservation(type: 7);

        $this->asSeller()
            ->getJson('/api/v2/reservations/issued')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $issue);

        // والعكس: قائمة الحجوزات لا تعرض أمر الصرف.
        $ids = collect($this->asSeller()->getJson('/api/v2/reservations')->json('data'))->pluck('id');
        $this->assertFalse($ids->contains($issue), 'أمر الصرف ظهر في قائمة الحجوزات');
    }

    public function test_it_can_be_filtered_by_date(): void
    {
        $old = $this->makeIssue([['product_id' => 90001, 'name' => 'Old', 'qty' => 5, 'price' => 1]],
            createdAt: '2026-01-15 10:00:00');
        $new = $this->makeIssue([['product_id' => 90001, 'name' => 'New', 'qty' => 5, 'price' => 1]],
            createdAt: '2026-09-10 10:00:00');

        $this->asSeller()
            ->getJson('/api/v2/reservations/issued?from=2026-09-01&to=2026-09-30')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $new);

        $this->asSeller()
            ->getJson('/api/v2/reservations/issued?to=2026-02-01')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $old);
    }

    public function test_it_can_be_filtered_by_product(): void
    {
        $a = $this->makeIssue([['product_id' => 90001, 'name' => 'A', 'qty' => 5, 'price' => 1]]);
        $this->makeIssue([['product_id' => 90002, 'name' => 'B', 'qty' => 5, 'price' => 1]]);

        $this->asSeller()
            ->getJson('/api/v2/reservations/issued?product_id=90001')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $a);
    }

    /**
     * الأسطر JSON في عمود، فالمطابقة نصية: "product_id":9000 يجب ألا
     * يطابق 90001 لأنه بادئة له.
     */
    public function test_a_product_id_that_prefixes_another_does_not_match_it(): void
    {
        $this->makeIssue([['product_id' => 90001, 'name' => 'A', 'qty' => 5, 'price' => 1]]);

        $this->asSeller()
            ->getJson('/api/v2/reservations/issued?product_id=9000')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->asSeller()
            ->getJson('/api/v2/reservations/issued?product_id=90001')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_a_seller_with_no_issued_orders_gets_an_empty_list(): void
    {
        $this->asSeller()
            ->getJson('/api/v2/reservations/issued')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    public function test_it_requires_authentication(): void
    {
        $this->getJson('/api/v2/reservations/issued')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    /** المسار ليس معرِّفًا رقميًا، فلا يبتلعه /reservations/{id}. */
    public function test_issued_does_not_resolve_as_a_reservation_id(): void
    {
        $this->asSeller()
            ->getJson('/api/v2/reservations/issued')
            ->assertOk()
            ->assertJsonPath('message', 'Issued stock orders retrieved');
    }

    /**
     * @param array<int, array{product_id:int, name:string, qty:float, price:float}> $lines
     */
    private function makeIssue(array $lines, ?int $sellerId = null, ?string $createdAt = null): int
    {
        $id = (int) (DB::table('reserve_products')->max('id') ?? 0) + 1;

        DB::table('reserve_products')->insert([
            'id'         => $id,
            'seller_id'  => $sellerId ?? ApiTestingSeeder::SELLER_ID,
            'data'       => json_encode(array_map(fn ($l) => [
                'product_name' => $l['name'],
                'product_id'   => $l['product_id'],
                'stock'        => $l['qty'],
                'balance'      => 1000,
                'price'        => $l['price'],
            ], $lines), JSON_UNESCAPED_UNICODE),
            'type'        => 3,
            'active'      => 2,
            'update_flag' => 0,
            'created_at'  => $createdAt ?? now(),
            'updated_at'  => $createdAt ?? now(),
        ]);

        return $id;
    }

    private function makeReservation(int $type): int
    {
        $id = (int) (DB::table('reserve_products')->max('id') ?? 0) + 1;

        DB::table('reserve_products')->insert([
            'id'         => $id,
            'seller_id'  => ApiTestingSeeder::SELLER_ID,
            'data'       => json_encode([[
                'product_name' => 'Requested', 'product_id' => 90001,
                'stock' => 5, 'balance' => 100, 'price' => 10,
            ]], JSON_UNESCAPED_UNICODE),
            'type'        => $type,
            'active'      => 1,
            'update_flag' => 0,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return $id;
    }
}
