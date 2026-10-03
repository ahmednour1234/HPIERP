<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Models\Seller;
use App\Models\SellerHandover;
use App\Models\Transection;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * العهدة المستلَمة: فواتير مندوبٍ انتهت خدمته يحصّلها غيره.
 *
 * مسار مستقلّ عن فواتير المندوب نفسه عمدًا. لو وُسِّعت نقاط النهاية
 * القائمة لتشمل فواتير العهدة لتبدّلت أرقام تقارير سابقة بأثر رجعي،
 * فتُقرأ هنا وحدها، وتبقى /orders و/dashboard على ما كانتا حرفيًّا.
 *
 * الفاتورة تبقى محسوبةً لصاحبها (orders.owner_id)، والتحصيل يُسجَّل
 * باسم القابض (transections.seller_id)، فلا يأخذ أحدٌ مجهود الآخر.
 */
class HandoverController extends Controller
{
    use ApiResponse;

    /** التسليمات القائمة على هذا المندوب: من استلم عهدتهم. */
    public function index(Request $request): JsonResponse
    {
        $sellerId = (int) $request->user()->id;

        $handovers = SellerHandover::query()
            ->active()
            ->where('to_seller_id', $sellerId)
            ->with('fromSeller:id,f_name,l_name,email,mandob_code')
            ->latest('id')
            ->get()
            ->map(fn (SellerHandover $h) => [
                'id'         => $h->id,
                'from'       => [
                    'id'          => (int) $h->from_seller_id,
                    'name'        => trim(
                        optional($h->fromSeller)->f_name . ' ' . optional($h->fromSeller)->l_name
                    ) ?: (optional($h->fromSeller)->email ?? ''),
                    'mandob_code' => optional($h->fromSeller)->mandob_code,
                ],
                'started_at' => optional($h->started_at)->toDateTimeString(),
                'note'       => $h->note,
            ]);

        return $this->ok($handovers, 'العهد المستلمة');
    }

    /**
     * فواتير العهدة غير المسدَّدة بالكامل.
     *
     * المسدَّدة لا شأن للمستلم بها: مهمّته التحصيل.
     */
    public function orders(Request $request): JsonResponse
    {
        $sellerId = (int) $request->user()->id;
        $sources  = SellerHandover::sourcesFor($sellerId);

        if (empty($sources)) {
            return $this->ok([], 'لا توجد عهدة مستلمة');
        }

        $request->validate([
            'from_seller_id' => ['nullable', 'integer'],
            'customer_id'    => ['nullable', 'integer'],
            'limit'          => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset'         => ['nullable', 'integer', 'min:1'],
        ]);

        // فلترة على مصدرٍ بعينه، ما دام ضمن المسموح.
        if ($one = (int) $request->input('from_seller_id')) {
            $sources = in_array($one, $sources, true) ? [$one] : [];
        }

        $orders = Order::query()
            ->whereIn('owner_id', $sources)
            // المرتجع لا يُحصَّل.
            ->where('type', '!=', 7)
            ->whereRaw('(COALESCE(transaction_reference, 0) + 0) < (order_amount + 0)')
            ->when($request->input('customer_id'), fn ($q, $c) => $q->where('user_id', $c))
            ->with(['details', 'customer:id,name,mobile', 'seller:id,f_name,l_name,email'])
            ->latest('id')
            ->paginate(
                (int) $request->input('limit', 25),
                ['*'],
                'page',
                (int) $request->input('offset', 1)
            );

        return $this->ok(OrderResource::collection($orders), 'فواتير العهدة');
    }

    /**
     * ما حصّله هذا المندوب من فواتير العهدة.
     *
     * منفصل عن تحصيلاته من فواتيره هو، ليبقى الكشفان مميَّزين.
     */
    public function collections(Request $request): JsonResponse
    {
        $sellerId = (int) $request->user()->id;
        $sources  = SellerHandover::sourcesFor($sellerId);

        if (empty($sources)) {
            return $this->ok([], 'لا توجد عهدة مستلمة');
        }

        $request->validate([
            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $rows = Transection::query()
            // قبضها هو، من فاتورةٍ صاحبها غيره.
            ->where('seller_id', $sellerId)
            ->whereIn('order_id', Order::whereIn('owner_id', $sources)->select('id'))
            ->when($request->input('from'), fn ($q, $d) => $q->whereDate('date', '>=', $d))
            ->when($request->input('to'), fn ($q, $d) => $q->whereDate('date', '<=', $d))
            ->latest('id')
            ->get(['id', 'order_id', 'customer_id', 'amount', 'date', 'description', 'created_at']);

        $owners = Order::whereIn('id', $rows->pluck('order_id')->filter())
            ->pluck('owner_id', 'id');

        return $this->ok([
            'total' => round((float) $rows->sum(fn ($r) => (float) $r->amount), 2),
            'items' => $rows->map(fn ($r) => [
                'id'          => $r->id,
                'order_id'    => (int) $r->order_id,
                // أصل الفاتورة: لمن كانت.
                'owner_id'    => (int) ($owners[$r->order_id] ?? 0),
                'customer_id' => (int) $r->customer_id,
                'amount'      => (float) $r->amount,
                'date'        => $r->date,
                'description' => $r->description,
            ])->values(),
        ], 'تحصيلات العهدة');
    }
}
