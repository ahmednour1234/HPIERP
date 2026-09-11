<?php

namespace Tests\Feature\Api\V2;

use Database\Seeders\ApiTestingSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\ApiTestCase;

/**
 * GET /categories/mine — الفئات المسندة للمندوب عبر seller_categories.
 */
class SellerCategoriesTest extends ApiTestCase
{
    public function test_it_returns_only_the_categories_assigned_to_the_seller(): void
    {
        // فئة ثانية غير مسندة: يجب ألا تظهر.
        $this->makeCategory(90002, 'Unassigned Category');

        $this->asSeller()
            ->getJson('/api/v2/categories/mine')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 90001);
    }

    public function test_it_carries_the_image_url(): void
    {
        $this->asSeller()
            ->getJson('/api/v2/categories/mine')
            ->assertOk()
            ->assertJsonPath('data.0.image', 'def.png')
            ->assertJsonPath(
                'data.0.image_url',
                fn ($url) => is_string($url) && str_contains($url, 'storage/category/def.png')
            );
    }

    public function test_it_can_be_filtered_by_type(): void
    {
        // نوع الفئة المسندة في البيانات التجريبية هو 0.
        $assignedType = (int) DB::table('categories')->where('id', 90001)->value('type');

        $this->asSeller()
            ->getJson('/api/v2/categories/mine?type=' . $assignedType)
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->asSeller()
            ->getJson('/api/v2/categories/mine?type=' . ($assignedType === 1 ? 0 : 1))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_it_can_be_filtered_by_status(): void
    {
        $this->asSeller()
            ->getJson('/api/v2/categories/mine?status=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        // فئة معطَّلة ومسندة: تُستبعد بالفلتر رغم إسنادها.
        $this->makeCategory(90003, 'Disabled Category', status: 0);
        DB::table('seller_categories')->updateOrInsert(
            ['id' => 90003],
            ['cat_id' => 90003, 'seller_id' => ApiTestingSeeder::SELLER_ID]
        );

        $this->asSeller()->getJson('/api/v2/categories/mine')
            ->assertOk()->assertJsonCount(2, 'data');

        $this->asSeller()->getJson('/api/v2/categories/mine?status=1')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->asSeller()->getJson('/api/v2/categories/mine?status=0')
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 90003);
    }

    public function test_a_seller_with_no_assignment_gets_an_empty_list(): void
    {
        DB::table('seller_categories')->where('seller_id', ApiTestingSeeder::SELLER_ID)->delete();

        $this->asSeller()
            ->getJson('/api/v2/categories/mine')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    public function test_it_requires_authentication(): void
    {
        $this->getJson('/api/v2/categories/mine')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    /** لا يحجب المسار CRUD الفئات: mine ليست معرِّفًا رقميًا. */
    public function test_it_does_not_shadow_the_category_show_route(): void
    {
        $this->asSeller()
            ->getJson('/api/v2/categories/90001')
            ->assertOk()
            ->assertJsonPath('data.id', 90001);
    }

    private function makeCategory(int $id, string $name, int $status = 1): void
    {
        DB::table('categories')->updateOrInsert(
            ['id' => $id],
            ['local_id' => $id, 'name' => $name, 'parent_id' => 0, 'position' => 1,
             'status' => $status, 'image' => 'def.png',
             'created_at' => now(), 'updated_at' => now()]
        );
    }
}
