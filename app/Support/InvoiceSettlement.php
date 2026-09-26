<?php

namespace App\Support;

/**
 * حالة تحصيل الفاتورة.
 *
 * المنطق نفسه الذي يستعمله تقرير المنتجات، مجموعًا هنا ليقرأه أكثر من
 * شاشة دون أن يتفرّع بينها.
 *
 * المحصَّل يُقرأ من transaction_reference لا من collected_cash:
 * الأولى مجموع ما حُصِّل حتى الآن — تزيد مع كل تحصيل لاحق من شاشة
 * العميل — والثانية تُكتب عند البيع وحده، فلا تعرف التحصيل المتأخّر.
 */
class InvoiceSettlement
{
    public const PAID             = 'paid';
    public const UNPAID           = 'unpaid';
    public const RETURNED_FULLY   = 'returned_fully';
    public const PARTIAL_PAID     = 'partial_paid';
    public const PARTIAL_RETURNED = 'partial_returned';
    public const PARTIAL_BOTH     = 'partial_both';

    /** التسميات كما تظهر في الشاشات والتصدير. */
    public static function labels(): array
    {
        return [
            self::PAID             => 'محصلة بالكامل',
            self::PARTIAL_PAID     => 'تحصيل جزئي',
            self::UNPAID           => 'غير محصلة',
            self::RETURNED_FULLY   => 'إرجاع كامل',
            self::PARTIAL_RETURNED => 'إرجاع جزئي',
            self::PARTIAL_BOTH     => 'تحصيل وإرجاع جزئي',
        ];
    }

    public static function label(?string $status): string
    {
        return self::labels()[$status] ?? '—';
    }

    /**
     * تصنيف فاتورة واحدة.
     *
     * @param float $amount    قيمة الفاتورة
     * @param float $collected المحصَّل منها (transaction_reference)
     * @param float $orderQty  إجمالي كميات الفاتورة
     * @param float $returnQty إجمالي الكميات المرتجعة
     */
    public static function for(float $amount, float $collected, float $orderQty, float $returnQty): string
    {
        // التحصيل الزائد يبقى محصَّلًا بالكامل، لا حالة رابعة.
        if ($collected >= $amount && $amount > 0) {
            return self::PAID;
        }

        if ($collected <= 0 && $returnQty >= $orderQty && $orderQty > 0) {
            return self::RETURNED_FULLY;
        }

        if ($collected > 0 && $returnQty > 0) {
            return self::PARTIAL_BOTH;
        }

        if ($collected > 0 && $amount - $collected > 0) {
            return self::PARTIAL_PAID;
        }

        if ($collected <= 0 && $returnQty > 0 && $returnQty < $orderQty) {
            return self::PARTIAL_RETURNED;
        }

        return self::UNPAID;
    }

    /**
     * قيد الاستعلام على حالة بعينها.
     *
     * الكميات المرتجعة تأتي من فواتير النوع 7 المعلّقة بـparent_id، وهو
     * ما تفعله بقية الشاشات.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    public static function scope($query, string $status)
    {
        $hasReturn = function ($q) {
            $q->select('parent_id')->from('orders')
              ->where('type', 7)->whereNotNull('parent_id');
        };

        return match ($status) {
            self::PAID => $query
                ->whereRaw('(COALESCE(transaction_reference, 0) + 0) >= order_amount')
                ->where('order_amount', '>', 0),

            // فاتورة بصفر جنيه وبلا تحصيل ليست محصَّلة ولا مرتجعة؛ تقع
            // في «غير محصلة» وإلا سقطت من التصنيف كله.

            self::PARTIAL_PAID => $query
                ->whereRaw('(COALESCE(transaction_reference, 0) + 0) > 0')
                ->whereRaw('(COALESCE(transaction_reference, 0) + 0) < order_amount')
                ->whereNotIn('id', $hasReturn),

            self::UNPAID => $query
                ->whereRaw('(COALESCE(transaction_reference, 0) + 0) <= 0')
                ->whereNotIn('id', $hasReturn),

            // الكامل من الجزئي يُفرَّق بمقارنة الكميات، وهي في جدول آخر،
            // فيُقيَّد هنا على "بلا تحصيل وعليه مرتجع" ويُفصل بعد الجلب.
            self::RETURNED_FULLY, self::PARTIAL_RETURNED => $query
                ->whereRaw('(COALESCE(transaction_reference, 0) + 0) <= 0')
                ->whereIn('id', $hasReturn),

            self::PARTIAL_BOTH => $query
                ->whereRaw('(COALESCE(transaction_reference, 0) + 0) > 0')
                ->whereRaw('(COALESCE(transaction_reference, 0) + 0) < order_amount')
                ->whereIn('id', $hasReturn),

            default => $query,
        };
    }
}
