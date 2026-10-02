<?php

namespace Tests\Feature\Admin;

use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * صفوف تقرير المنتجات مستوية لا متداخلة.
 *
 * groupBy ثم map يعيد مجموعةً من المجموعات. صنفٌ له سطر واحد في الشهر
 * يبدو سليمًا، فمرّ الأمر في التطوير؛ وصنفٌ له سطران يجعل القالب يمرّ
 * على مجموعة لا على صفّ، فسقطت الصفحة في الإنتاج بـ
 * "Trying to access array offset on value of type int" — وقبل السقوط
 * كانت تبتلع الأسطر الزائدة بصمت.
 */
class ProductReportRowsTest extends TestCase
{
    public function test_grouping_then_mapping_nests_the_rows(): void
    {
        $grouped = $this->lines()
            ->groupBy('product_details->id')
            ->map(fn (Collection $rows) => $rows->map(fn () => ['order_type' => 4]));

        // المهم أن كل عنصر مجموعة لا صفّ، فالقالب يمرّ على مجموعة.
        $this->assertLessThan(3, $grouped->count(), 'لم يحدث تجميع أصلًا');
        $this->assertInstanceOf(Collection::class, $grouped->first());
    }

    public function test_flattening_restores_one_row_per_line(): void
    {
        $rows = $this->lines()
            ->groupBy('product_details->id')
            ->map(fn (Collection $group) => $group->map(fn () => ['order_type' => 4]))
            ->flatten(1)
            ->values();

        $this->assertSame(3, $rows->count(), 'سطر مبيعات ضاع في التجميع');

        foreach ($rows as $row) {
            $this->assertIsArray($row);
            $this->assertArrayHasKey('order_type', $row);
        }
    }

    /** صنف له سطران وآخر له سطر، كما يقع في شهر حقيقي. */
    private function lines(): Collection
    {
        return collect([
            (object) ['product_details' => json_encode(['id' => 1])],
            (object) ['product_details' => json_encode(['id' => 1])],
            (object) ['product_details' => json_encode(['id' => 2])],
        ]);
    }
}
