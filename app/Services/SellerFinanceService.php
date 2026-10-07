<?php

namespace App\Services;

use App\Models\Order;
use App\Models\TransactionSeller;
use Illuminate\Support\Facades\DB;

/**
 * الوضع المالي للمندوب: ما باعه، وما حصّله، وما بقي عليه وعنده.
 *
 * يُحسب من السجلّات لا من admins.credit: ذاك عمود تراكمي تكتبه نقاط
 * كثيرة عبر سنين وقد جانب الصواب في بعض الحسابات، وقياسه على البيانات
 * يُظهر فروقًا كبيرة عن مجموع ما حُصِّل ناقص ما وُرِّد.
 *
 * ثلاثة أرقام مختلفة يخلط بينها كثيرًا، وهي هنا مفصولة:
 *
 *   مديونية العملاء  = قيمة الفواتير − المحصَّل منها − المرتجع
 *                      مالٌ عند العملاء لم يُقبض بعد.
 *
 *   العهدة           = ما قبضه المندوب − ما ورّده
 *                      مالٌ في يده هو، يلزمه توريده.
 *
 *   المعلّق          = توريدٌ قدّمه وينتظر اعتماد الأدمن
 *                      خرج من يده ولم يدخل الخزنة بعد.
 */
class SellerFinanceService
{
    /** أنواع الفواتير التي تُنشئ مديونية على العميل. */
    private const SALE_TYPES = [4, 12, 24];

    private const TYPE_RETURN = 7;

    /** توريد معتمد / معلّق. */
    private const DEPOSIT_APPROVED = 1;
    private const DEPOSIT_PENDING  = 0;

    public function forSeller(int $sellerId): array
    {
        $invoiced  = $this->invoiced($sellerId);
        $collected = $this->collected($sellerId);
        $returned  = $this->returned($sellerId);

        // ما زال على العملاء: لا يقلّ عن صفر، فالتحصيل الزائد أو
        // المرتجع بعد السداد لا يجعل المديونية سالبة.
        $outstanding = (float) max(0, round($invoiced - $collected - $returned, 2));

        $deposited = $this->deposited($sellerId, self::DEPOSIT_APPROVED);
        $pending   = $this->deposited($sellerId, self::DEPOSIT_PENDING);

        // في يده: ما قبضه ولم يورّده. المعلّق خرج من يده فعلًا وإن لم
        // يُعتمد، فيُطرح كذلك ويُعرض وحده.
        $inHand = round($collected - $deposited - $pending, 2);

        return [
            'sales' => [
                'count'    => $this->invoiceCount($sellerId),
                'invoiced' => round($invoiced, 2),
                'returned' => round($returned, 2),
                'net'      => round($invoiced - $returned, 2),
            ],

            'collection' => [
                'collected'   => round($collected, 2),
                'outstanding' => $outstanding,
                // نسبة التحصيل من صافي المبيعات، لا من الإجمالي:
                // المرتجع لا يُحصَّل فلا يدخل المقام.
                // البسط هو التحصيل الباقي محقَّقًا: ما حُصِّل ثم رُدَّ
                // خرج من الطرفين، وإلا تجاوزت النسبة المئة.
                'rate'        => $invoiced - $returned > 0
                    ? min(100.0, round(
                        max(0, $collected - $this->collectedThenReturned($sellerId))
                            / ($invoiced - $returned) * 100,
                        1
                    ))
                    : 0.0,
            ],

            'custody' => [
                'in_hand'   => $inHand,
                'deposited' => round($deposited, 2),
                'pending'   => round($pending, 2),
            ],

            // غير المسدَّدة بالكامل، وهي ما يلاحقه المندوب.
            'open_invoices' => $this->openInvoiceCount($sellerId),
        ];
    }

    /**
     * العهدة لعدة مناديب دفعةً واحدة.
     *
     * forSeller يصلح لمندوبٍ واحد، واستدعاؤه في حلقةٍ على صفحة
     * القائمة يعني ستة استعلامات لكل صفّ. هنا ثلاثة استعلامات
     * مجمَّعة لا أكثر.
     *
     * @param  array<int,int>  $sellerIds
     * @return array<int,float>  seller_id => ما في يده
     */
    public function custodyFor(array $sellerIds): array
    {
        if (empty($sellerIds)) {
            return [];
        }

        $collected = Order::whereIn('owner_id', $sellerIds)
            ->whereIn('type', self::SALE_TYPES)
            ->selectRaw('owner_id, SUM(COALESCE(transaction_reference, 0) + 0) as total')
            ->groupBy('owner_id')
            ->pluck('total', 'owner_id');

        $deposited = TransactionSeller::whereIn('seller_id', $sellerIds)
            ->where('active', self::DEPOSIT_APPROVED)
            ->selectRaw('seller_id, SUM(amount + 0) as total')
            ->groupBy('seller_id')
            ->pluck('total', 'seller_id');

        $pending = TransactionSeller::whereIn('seller_id', $sellerIds)
            ->where('active', self::DEPOSIT_PENDING)
            ->selectRaw('seller_id, SUM(amount + 0) as total')
            ->groupBy('seller_id')
            ->pluck('total', 'seller_id');

        $out = [];

        foreach ($sellerIds as $id) {
            $id = (int) $id;

            // القاعدة نفسها التي يعرضها التطبيق، حرفًا بحرف.
            $out[$id] = round(
                (float) ($collected[$id] ?? 0)
                - (float) ($deposited[$id] ?? 0)
                - (float) ($pending[$id] ?? 0),
                2
            );
        }

        return $out;
    }

    /** إجمالي ما فوتره المندوب. */
    private function invoiced(int $sellerId): float
    {
        return (float) Order::where('owner_id', $sellerId)
            ->whereIn('type', self::SALE_TYPES)
            ->sum('order_amount');
    }

    /**
     * ما حُصِّل على فواتيره.
     *
     * transaction_reference هو السجلّ الكامل: تحصيل شاشة العميل يزيده،
     * وهو مضبوط على كل فاتورة فيها collected_cash بينما العكس غير صحيح.
     */
    private function collected(int $sellerId): float
    {
        return (float) Order::where('owner_id', $sellerId)
            ->whereIn('type', self::SALE_TYPES)
            ->sum(DB::raw('COALESCE(transaction_reference, 0) + 0'));
    }

    /**
     * ما حُصِّل ثم رُدَّ: تحصيلٌ لم يبقَ محقَّقًا.
     *
     * المرتجع يُطرح من صافي المبيعات (المقام) بينما يبقى ما حُصِّل
     * عليه في البسط، فتتجاوز نسبة التحصيل المئة. يُطرح هنا بنسبة
     * المردود من الفاتورة لا كاملًا: الردّ قد يكون جزئيًّا، وقد
     * تُردّ الفاتورة الواحدة على دفعات.
     */
    private function collectedThenReturned(int $sellerId): float
    {
        $returns = Order::query()
            ->where('type', self::TYPE_RETURN)
            ->whereNotNull('parent_id')
            ->selectRaw('parent_id, SUM(order_amount + 0) as returned')
            ->groupBy('parent_id');

        return (float) Order::query()
            ->from('orders as p')
            ->joinSub($returns, 'r', 'r.parent_id', '=', 'p.id')
            ->where('p.owner_id', $sellerId)
            ->whereIn('p.type', self::SALE_TYPES)
            ->where('p.order_amount', '>', 0)
            // بلا MIN/LEAST: الأولى تقبل عمودين في SQLite وحدها
            // والثانية في MySQL وحدها، فتنكسر إحداهما على الإنتاج.
            // CASE يعمل على المحرّكين، والنسبة محدودة بواحد لأن
            // المردود لا يتجاوز الفاتورة.
            ->sum(DB::raw(
                '(COALESCE(p.transaction_reference, 0) + 0) * '
                . 'CASE WHEN r.returned >= (p.order_amount + 0) THEN 1.0 '
                . 'ELSE r.returned / (p.order_amount + 0) END'
            ));
    }

    private function returned(int $sellerId): float
    {
        return (float) Order::where('owner_id', $sellerId)
            ->where('type', self::TYPE_RETURN)
            ->sum('order_amount');
    }

    private function deposited(int $sellerId, int $status): float
    {
        return (float) TransactionSeller::where('seller_id', $sellerId)
            ->where('active', $status)
            ->sum(DB::raw('amount + 0'));
    }

    private function invoiceCount(int $sellerId): int
    {
        return Order::where('owner_id', $sellerId)
            ->whereIn('type', self::SALE_TYPES)
            ->count();
    }

    /** فواتير لم تُسدَّد بالكامل بعد. */
    private function openInvoiceCount(int $sellerId): int
    {
        return Order::where('owner_id', $sellerId)
            ->whereIn('type', self::SALE_TYPES)
            ->whereRaw('(COALESCE(transaction_reference, 0) + 0) < (order_amount + 0)')
            ->count();
    }
}
