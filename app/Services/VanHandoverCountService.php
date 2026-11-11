<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Stock;
use App\Models\VanHandoverCount;
use App\Models\VanHandoverCountItem;
use Illuminate\Support\Facades\DB;

/**
 * جرد تسليم عربية مندوب انتهت خدمته.
 *
 * طلب الإرجاع القائم يقدّمه المندوب من التطبيق، ومن انتهت خدمته لا
 * يفعل، فتبقى بضاعته على عربيته إلى الأبد. هنا يَجرد الأدمن العربية
 * من اللوحة: يكتب المسلَّم فعلًا صنفًا صنفًا، ويُحسب الفرق.
 *
 * الفرق كميةٌ فقط ولا يُقوَّم بمال: التسعير قرار إداري خارج النظام.
 *
 * البضاعة لا تتحرك إلا بالاعتماد، كطلب الإرجاع تمامًا.
 */
class VanHandoverCountService
{
    /**
     * ما تقوله السجلات إنه في العربية الآن.
     *
     * الأساس الذي يُكتب أمامه المسلَّم فعلًا.
     */
    public function vanStock(int $sellerId)
    {
        return Stock::where('seller_id', $sellerId)
            ->where('stock', '>', 0)
            ->with('product:id,name,product_code')
            ->orderBy('product_id')
            ->get(['id', 'product_id', 'stock']);
    }

    /**
     * يفتح جردًا معلّقًا.
     *
     * @param  array<int,int>  $counted  product_id => الكمية المسلَّمة
     */
    public function open(
        int $sellerId,
        array $counted,
        ?int $handoverId = null,
        ?int $countedBy = null,
        ?string $note = null
    ): VanHandoverCount {
        if ($this->pendingFor($sellerId)) {
            throw new \RuntimeException('يوجد جرد معلّق لهذا المندوب بالفعل.');
        }

        return DB::transaction(function () use ($sellerId, $counted, $handoverId, $countedBy, $note) {
            $count = VanHandoverCount::create([
                'seller_id'   => $sellerId,
                'handover_id' => $handoverId,
                'status'      => VanHandoverCount::STATUS_PENDING,
                'note'        => $note,
                'counted_by'  => $countedBy,
            ]);

            foreach ($this->vanStock($sellerId) as $row) {
                $productId = (int) $row->product_id;
                $expected  = (int) $row->stock;

                // صنفٌ لم يُكتب له رقم يُعدّ غير مسلَّم: الصفر رقمٌ
                // صريح، وتخطّي الصنف لا يعني أنه سُلِّم كاملًا.
                $actual = (int) ($counted[$productId] ?? 0);

                if ($actual < 0) {
                    throw new \InvalidArgumentException('الكمية المسلَّمة لا تكون سالبة.');
                }

                VanHandoverCountItem::create([
                    'count_id'   => $count->id,
                    'product_id' => $productId,
                    'expected'   => $expected,
                    'counted'    => $actual,
                    'difference' => $actual - $expected,
                ]);
            }

            return $count->load('items');
        });
    }

    /**
     * الاعتماد: المسلَّم فعلًا يعود للمخزن، والعربية تُفرَّغ.
     *
     * العربية تُصفَّر لا تُنقَص بالمسلَّم: العجز بضاعةٌ مفقودة لا
     * بضاعةٌ باقية في عربية مندوبٍ انتهت خدمته، وتركها مسجَّلةً
     * عليه يعني أنها ستظهر في كل جرد لاحق.
     */
    public function approve(int $countId, int $adminId, ?string $adminNote = null): VanHandoverCount
    {
        return DB::transaction(function () use ($countId, $adminId, $adminNote) {
            $count = VanHandoverCount::with('items')
                ->lockForUpdate()
                ->findOrFail($countId);

            if (!$count->isPending()) {
                throw new \RuntimeException('هذا الجرد روجع من قبل.');
            }

            foreach ($count->items as $item) {
                // المسلَّم فعلًا هو ما يدخل المخزن. العجز لا يدخل
                // لأنه لم يُسلَّم، والزيادة تدخل لأنها سُلِّمت.
                if ($item->counted > 0 && $product = Product::whereKey($item->product_id)->lockForUpdate()->first()) {
                    $product->quantity += $item->counted;
                    $product->save();
                }

                Stock::where('seller_id', $count->seller_id)
                    ->where('product_id', $item->product_id)
                    ->update(['stock' => 0]);
            }

            $count->update([
                'status'      => VanHandoverCount::STATUS_APPROVED,
                'admin_note'  => $adminNote,
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
            ]);

            return $count->load('items.product:id,name,product_code');
        });
    }

    /**
     * تصفير العربية وإرجاع كل ما عليها للمخزن، بلا جرد.
     *
     * كاعتماد جرد سُلِّم فيه كل شيء كاملًا: الكمية المسجَّلة على
     * العربية تُضاف لمخزون المنتج، والعربية تُصفَّر.
     *
     * @return array{products:int, units:int}
     */
    public function returnAllToWarehouse(int $sellerId): array
    {
        $this->assertNoPendingCount($sellerId);

        return DB::transaction(function () use ($sellerId) {
            $rows = Stock::where('seller_id', $sellerId)->where('stock', '>', 0)->lockForUpdate()->get();
            $units = 0;

            foreach ($rows as $row) {
                if ($product = Product::whereKey($row->product_id)->lockForUpdate()->first()) {
                    $product->quantity += (int) $row->stock;
                    $product->save();
                }
                $units += (int) $row->stock;
                $row->stock = 0;
                $row->save();
            }

            return ['products' => $rows->count(), 'units' => $units];
        });
    }

    /**
     * نقل بضاعة العربية كلها إلى عربية مندوب آخر.
     *
     * الصنف الذي عند المستلم تُضاف كميته لصفّه؛ وغيره يُنقل صفّه نفسه
     * إليه، فتبقى بياناته (local_id, main_stock) كما هي.
     *
     * @return array{products:int, units:int}
     */
    public function moveAllTo(int $fromSellerId, int $toSellerId): array
    {
        if ($fromSellerId === $toSellerId) {
            throw new \InvalidArgumentException('لا تُنقل البضاعة إلى نفس المندوب.');
        }

        $this->assertNoPendingCount($fromSellerId);

        return DB::transaction(function () use ($fromSellerId, $toSellerId) {
            $rows = Stock::where('seller_id', $fromSellerId)->where('stock', '>', 0)->lockForUpdate()->get();
            $units = 0;

            foreach ($rows as $row) {
                $qty    = (int) $row->stock;
                $target = Stock::where('seller_id', $toSellerId)
                    ->where('product_id', $row->product_id)
                    ->lockForUpdate()
                    ->first();

                if ($target) {
                    $target->stock = (int) $target->stock + $qty;
                    $target->save();
                    $row->stock = 0;
                    $row->save();
                } else {
                    $row->seller_id = $toSellerId;
                    $row->save();
                }

                $units += $qty;
            }

            return ['products' => $rows->count(), 'units' => $units];
        });
    }

    /** جرد معلّق يقرأ أرقام العربية، فلا تُحرَّك بضاعتها حتى يُبَتّ فيه. */
    private function assertNoPendingCount(int $sellerId): void
    {
        if ($this->pendingFor($sellerId)) {
            throw new \RuntimeException('يوجد جرد معلّق لهذا المندوب: اعتمده أو ارفضه أولًا.');
        }
    }

    /** الرفض: المخزون لا يتغير. */
    public function reject(int $countId, int $adminId, ?string $adminNote = null): VanHandoverCount
    {
        $count = VanHandoverCount::lockForUpdate()->findOrFail($countId);

        if (!$count->isPending()) {
            throw new \RuntimeException('هذا الجرد روجع من قبل.');
        }

        $count->update([
            'status'      => VanHandoverCount::STATUS_REJECTED,
            'admin_note'  => $adminNote,
            'reviewed_by' => $adminId,
            'reviewed_at' => now(),
        ]);

        return $count;
    }

    public function pendingFor(int $sellerId): ?VanHandoverCount
    {
        return VanHandoverCount::where('seller_id', $sellerId)
            ->where('status', VanHandoverCount::STATUS_PENDING)
            ->latest('id')
            ->first();
    }
}
