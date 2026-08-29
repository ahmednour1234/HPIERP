<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * أرشفة الفواتير المكتملة.
 *
 * «مكتملة» تعني إحدى حالتين فقط:
 *   1) محصَّلة بالكامل  — transaction_reference >= order_amount
 *   2) مرتجعة بالكامل   — لم يُحصَّل منها شيء وأُرجعت كل الكميات
 *
 * ما عدا ذلك (تحصيل جزئي، إرجاع جزئي، غير محصَّلة) لا يُؤرشف إطلاقًا، لأن
 * عليه إجراءً مفتوحًا. نفس منطق تحديد الحالة المستخدم في كشف المنتجات
 * المباعة والتقرير الشهري، حتى لا تختلف الأرقام بين الشاشات.
 *
 * الأرشفة وسم قابل للتراجع، لا حذف: الفاتورة تبقى في التقارير والأرصدة.
 */
class InvoiceArchiveService
{
    /** الحد الأدنى لعمر الفاتورة قبل أن تُقترح للأرشفة (بالأيام). */
    public const DEFAULT_MIN_AGE_DAYS = 180;

    /**
     * استعلام الفواتير المؤهَّلة للأرشفة.
     *
     * يُبنى بالكامل في SQL: الأرشفة الجماعية قد تمس آلاف الفواتير، وتحميلها
     * إلى PHP لفحصها واحدةً واحدة كان سيستغرق دقائق.
     */
    public function eligibleQuery(array $sellerIds = [], ?int $minAgeDays = null)
    {
        $query = Order::query()
            ->where('type', 4)
            ->whereNull('archived_at');

        if (!empty($sellerIds)) {
            $query->whereIn('owner_id', $sellerIds);
        }

        if ($minAgeDays !== null && $minAgeDays > 0) {
            $query->where('created_at', '<=', now()->subDays($minAgeDays));
        }

        $query->where(function ($q) {
            // 1) محصَّلة بالكامل
            $q->whereRaw('CAST(transaction_reference AS DECIMAL(20,4)) >= CAST(order_amount AS DECIMAL(20,4))');

            // 2) مرتجعة بالكامل: لا تحصيل، وكمية المرتجع >= كمية الفاتورة
            $q->orWhere(function ($q2) {
                $q2->whereRaw('CAST(transaction_reference AS DECIMAL(20,4)) = 0')
                   ->whereRaw('(
                        SELECT COALESCE(SUM(CAST(rd.quantity AS DECIMAL(20,4))), 0)
                        FROM orders r
                        JOIN order_details rd ON rd.order_id = r.id
                        WHERE r.parent_id = orders.id
                      ) >= (
                        SELECT COALESCE(SUM(CAST(d.quantity AS DECIMAL(20,4))), 0)
                        FROM order_details d
                        WHERE d.order_id = orders.id
                      )')
                   ->whereRaw('(
                        SELECT COALESCE(SUM(CAST(d.quantity AS DECIMAL(20,4))), 0)
                        FROM order_details d
                        WHERE d.order_id = orders.id
                      ) > 0');
            });
        });

        return $query;
    }

    /** سبب أهلية فاتورة بعينها، أو null إن كانت غير مؤهَّلة. */
    public function eligibilityReason(Order $order): ?string
    {
        if ((string) $order->type !== '4') {
            return null;
        }

        $amount = (float) $order->order_amount;
        $paid   = (float) $order->transaction_reference;

        if ($amount > 0 && $paid >= $amount) {
            return 'محصلة بالكامل';
        }

        $ordered = (float) $order->details()->sum(DB::raw('CAST(quantity AS DECIMAL(20,4))'));
        $returned = (float) Order::where('parent_id', $order->id)
            ->join('order_details', 'order_details.order_id', '=', 'orders.id')
            ->sum(DB::raw('CAST(order_details.quantity AS DECIMAL(20,4))'));

        if ($paid == 0.0 && $ordered > 0 && $returned >= $ordered) {
            return 'مرتجعة بالكامل';
        }

        return null;
    }

    /**
     * أرشفة فاتورة واحدة.
     *
     * تُرفض الفاتورة غير المؤهَّلة: الأرشفة اليدوية وسيلة راحة لا وسيلة
     * لإخفاء فاتورة عليها مستحقات.
     */
    public function archive(Order $order, ?string $reason = null): bool
    {
        if ($order->archived_at) {
            return false;
        }

        $reason ??= $this->eligibilityReason($order);

        if ($reason === null) {
            return false;
        }

        $order->archived_at    = now();
        $order->archived_by    = Auth::guard('admin')->id();
        $order->archive_reason = $reason;
        $order->save();

        return true;
    }

    /** إعادة فاتورة من الأرشيف إلى القوائم. */
    public function unarchive(Order $order): bool
    {
        if (!$order->archived_at) {
            return false;
        }

        $order->archived_at    = null;
        $order->archived_by    = null;
        $order->archive_reason = null;
        $order->save();

        return true;
    }

    /**
     * أرشفة جماعية لكل المؤهَّل.
     *
     * تحديث دفعة واحدة في SQL بدل حفظ كل فاتورة على حدة؛ الفرق بين ثوانٍ
     * ودقائق على آلاف الفواتير.
     */
    public function archiveAllEligible(array $sellerIds = [], ?int $minAgeDays = null): int
    {
        $ids = $this->eligibleQuery($sellerIds, $minAgeDays)->pluck('id');

        if ($ids->isEmpty()) {
            return 0;
        }

        $adminId = Auth::guard('admin')->id();
        $now     = now();
        $total   = 0;

        // على دفعات: تحديث آلاف المعرّفات في عبارة IN واحدة قد يتجاوز حدود
        // الخادم للاستعلام الواحد.
        foreach ($ids->chunk(500) as $chunk) {
            $total += Order::whereIn('id', $chunk)
                ->whereNull('archived_at')
                ->update([
                    'archived_at'    => $now,
                    'archived_by'    => $adminId,
                    'archive_reason' => 'أرشفة جماعية',
                ]);
        }

        return $total;
    }

    /** أعداد سريعة للعرض في الشاشة. */
    public function counts(array $sellerIds = [], ?int $minAgeDays = null): array
    {
        return [
            'eligible' => $this->eligibleQuery($sellerIds, $minAgeDays)->count(),
            'archived' => Order::where('type', 4)->whereNotNull('archived_at')
                ->when(!empty($sellerIds), fn ($q) => $q->whereIn('owner_id', $sellerIds))
                ->count(),
        ];
    }
}
