<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderOwnerLog;
use App\Models\SellerHandover;
use Illuminate\Support\Facades\DB;

/**
 * ترحيل فواتير مندوب إلى آخر.
 *
 * حين تنتهي خدمة مندوب تُنقل فواتيره المفتوحة إلى من يخلفه: يُكتب
 * orders.owner_id فعلًا، فيراها الخلف في التطبيق ويحصّلها دون أي
 * تعديل في التطبيق نفسه.
 *
 * ولأن العمود يُكتب فوقه، يُسجَّل الأصل في order_owner_logs قبل كل
 * كتابة. بغير ذلك يضيع من أصدر الفاتورة بلا رجعة.
 */
class OrderOwnershipTransferService
{
    /** المرتجع لا يُرحَّل: لا تحصيل عليه. */
    private const TYPE_RETURN = 7;

    /**
     * فواتير المندوب القابلة للترحيل.
     *
     * المسدَّدة بالكامل لا تُرحَّل افتراضيًّا: الترحيل لأجل التحصيل،
     * ونقل ما لا يُحصَّل يبدّل أرقام تقارير بلا فائدة.
     */
    public function transferable(int $fromSeller, bool $unpaidOnly = true)
    {
        return Order::query()
            ->where('owner_id', $fromSeller)
            ->where('type', '!=', self::TYPE_RETURN)
            ->when($unpaidOnly, fn ($q) => $q->whereRaw(
                '(COALESCE(transaction_reference, 0) + 0) < (order_amount + 0)'
            ));
    }

    /**
     * ينفّذ الترحيل ويعيد عدد ما رُحِّل.
     *
     * @param  array<int,int>|null  $orderIds  فواتير بعينها، أو null لكل القابل للترحيل.
     */
    public function transfer(
        int $fromSeller,
        int $toSeller,
        ?array $orderIds = null,
        ?int $handoverId = null,
        ?int $movedBy = null,
        ?string $reason = null,
        bool $unpaidOnly = true
    ): int {
        if ($fromSeller === $toSeller) {
            throw new \InvalidArgumentException('لا يُرحَّل المندوب إلى نفسه');
        }

        return DB::transaction(function () use (
            $fromSeller, $toSeller, $orderIds, $handoverId, $movedBy, $reason, $unpaidOnly
        ) {
            $query = $this->transferable($fromSeller, $unpaidOnly);

            if ($orderIds !== null) {
                $query->whereIn('id', $orderIds);
            }

            // تُقرأ المعرِّفات أولًا: بعد الكتابة لا يبقى ما يدلّ عليها.
            $ids = $query->lockForUpdate()->pluck('id')->all();

            if (empty($ids)) {
                return 0;
            }

            $now = now();

            // الأصل يُسجَّل قبل الكتابة فوقه، وإلا ضاع.
            OrderOwnerLog::insert(array_map(fn ($id) => [
                'order_id'       => $id,
                'from_seller_id' => $fromSeller,
                'to_seller_id'   => $toSeller,
                'handover_id'    => $handoverId,
                'moved_by'       => $movedBy,
                'reason'         => $reason,
                'created_at'     => $now,
                'updated_at'     => $now,
            ], $ids));

            Order::whereIn('id', $ids)->update(['owner_id' => $toSeller]);

            return count($ids);
        });
    }

    /**
     * يعيد الفواتير إلى أصحابها قبل ترحيلٍ بعينه.
     *
     * الترحيل عمليةٌ بشرية تقع بالخطأ، ويجب أن يكون لها تراجع.
     */
    public function undo(int $handoverId): int
    {
        return DB::transaction(function () use ($handoverId) {
            $logs = OrderOwnerLog::where('handover_id', $handoverId)
                ->orderByDesc('id')
                ->get();

            foreach ($logs as $log) {
                Order::where('id', $log->order_id)
                    // لا تُعاد إلا إن كانت ما تزال عند من رُحِّلت إليه:
                    // ترحيلٌ لاحق يعني أن هذا لم يعد آخر نقل.
                    ->where('owner_id', $log->to_seller_id)
                    ->update(['owner_id' => $log->from_seller_id]);
            }

            $count = $logs->count();

            OrderOwnerLog::where('handover_id', $handoverId)->delete();

            return $count;
        });
    }

    /** تسليمٌ قائم بين الطرفين، إن وُجد، ليُربط به الترحيل. */
    public function activeHandover(int $fromSeller, int $toSeller): ?SellerHandover
    {
        return SellerHandover::query()
            ->active()
            ->where('from_seller_id', $fromSeller)
            ->where('to_seller_id', $toSeller)
            ->latest('id')
            ->first();
    }
}
