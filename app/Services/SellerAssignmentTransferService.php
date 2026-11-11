<?php

namespace App\Services;

use App\Models\SellerCategory;
use App\Models\SellerCustomer;
use App\Models\SellerRegion;
use Illuminate\Support\Facades\DB;

/**
 * نقل عملاء مندوب ومناطقه وأقسامه إلى مندوب آخر.
 *
 * يستعمله تسليم العهدة وشاشة النقل المستقلة، فلا يتفرّق الشرط.
 * لا يُنقل إلا ما هو معيَّن للأول فعلًا، ولا يتكرّر عند الثاني ما
 * عنده أصلًا، والحذف من الأول اختياري (نقل أو نسخ).
 */
class SellerAssignmentTransferService
{
    /** المجموعة => [جدول الربط، عمود المعرِّف، الموديل، جدول الأسماء]. */
    public const SETS = [
        'customers'  => ['seller_customers', 'customer_id', SellerCustomer::class, 'customers'],
        'regions'    => ['seller_regions', 'region_id', SellerRegion::class, 'regions'],
        'categories' => ['seller_categories', 'cat_id', SellerCategory::class, 'categories'],
    ];

    /**
     * تعيينات الأول بأسمائها، مع علامة على ما عند الثاني أصلًا.
     *
     * @return array<string, \Illuminate\Support\Collection>
     */
    public function listFor(int $from, ?int $to = null): array
    {
        $out = [];

        foreach (self::SETS as $key => [$pivot, $column, , $table]) {
            $toHas = $to
                ? DB::table($pivot)->where('seller_id', $to)->pluck($column)->map(fn ($v) => (int) $v)->flip()
                : collect();

            $out[$key] = DB::table($pivot)
                ->join($table, "$table.id", '=', "$pivot.$column")
                ->where("$pivot.seller_id", $from)
                ->orderBy("$table.name")
                ->distinct()
                ->get(["$table.id", "$table.name"])
                ->map(fn ($r) => ['id' => (int) $r->id, 'name' => $r->name, 'to_has' => $toHas->has((int) $r->id)]);
        }

        return $out;
    }

    /**
     * ينفّذ النقل ويعيد ما نُقل فعلًا لكل مجموعة.
     *
     * @param  array<string, array<int,int>>  $selected  المجموعة => المعرِّفات المختارة
     * @return array<string, array<int,int>>
     */
    public function move(int $from, int $to, array $selected, bool $remove = true): array
    {
        if ($from === $to) {
            throw new \InvalidArgumentException('لا يُنقل المندوب إلى نفسه.');
        }

        $moved = [];

        DB::transaction(function () use ($from, $to, $selected, $remove, &$moved) {
            foreach (self::SETS as $key => [, $column, $model]) {
                $ids = $model::where('seller_id', $from)
                    ->whereIn($column, array_map('intval', $selected[$key] ?? []))
                    ->pluck($column)
                    ->map(fn ($v) => (int) $v)
                    ->unique()
                    ->values();

                if ($ids->isEmpty()) {
                    continue;
                }

                $already = $model::where('seller_id', $to)->whereIn($column, $ids)->pluck($column)
                    ->map(fn ($v) => (int) $v)->all();

                foreach ($ids->diff($already) as $value) {
                    $row = new $model();
                    $row->seller_id = $to;
                    $row->{$column} = $value;
                    $row->save();
                }

                if ($remove) {
                    $model::where('seller_id', $from)->whereIn($column, $ids)->delete();
                }

                $moved[$key] = $ids->all();
            }
        });

        return $moved;
    }
}
