<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Transection;
use App\Models\Visitor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Figures for the seller's dashboard.
 *
 * Everything is scoped to the signed-in seller. The v1 dashboard summed the
 * whole company's transactions regardless of who was asking, so every seller
 * saw the same numbers. The date grouping also used MySQL's YEAR()/MONTH(),
 * which is why those endpoints could not run anywhere else; this uses
 * portable date ranges instead.
 */
class DashboardService
{
    public function summary(int $sellerId, ?string $from = null, ?string $to = null): array
    {
        $from = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->startOfMonth();
        $to   = $to   ? Carbon::parse($to)->endOfDay()     : Carbon::now()->endOfMonth();

        $sales   = $this->orderTotal($sellerId, OrderService::TYPE_SALE, $from, $to);
        $returns = $this->orderTotal($sellerId, OrderService::TYPE_RETURN, $from, $to);

        return [
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'sales' => [
                'count'  => $this->orderCount($sellerId, OrderService::TYPE_SALE, $from, $to),
                'amount' => $sales,
            ],
            'returns' => [
                'count'  => $this->orderCount($sellerId, OrderService::TYPE_RETURN, $from, $to),
                'amount' => $returns,
            ],
            'net_sales'  => $sales - $returns,
            'collected'  => (float) Transection::where('seller_id', $sellerId)
                ->whereBetween('created_at', [$from, $to])->sum('amount'),
            'visits'     => Visitor::where('seller_id', $sellerId)
                ->whereBetween('created_at', [$from, $to])->count(),
            // المستهدف مأخوذ من كشف راتب الشهر (number_of_visitors)، وهو نفس
            // المصدر الذي يقرأ منه /salary. صفر يعني لا مستهدف محدَّد لهذا
            // الشهر، فيعرض التطبيق العدد وحده بلا نسبة.
            'visits_target' => $this->visitsTarget($sellerId, $from),
            'stock_value' => $this->stockValue($sellerId),
            'low_stock_products' => $this->lowStockCount($sellerId),
        ];
    }

    /**
     * المستهدف الشهري للزيارات.
     *
     * كشف الراتب هو المرجع حين يكون موجودًا، لكنه يُدخل بعد انتهاء الشهر،
     * فالشهر الجاري بلا كشف غالبًا. ولأن اللوحة تملأ number_of_visitors من
     * إعداد المندوب نفسه (admins.visitors) فهو المصدر الأصلي، ونرجع إليه
     * حين لا يوجد كشف. صفر يعني أن المندوب بلا مستهدف محدَّد.
     */
    private function visitsTarget(int $sellerId, Carbon $from): int
    {
        $fromSalary = (int) \App\Models\Salary::where('seller_id', $sellerId)
            ->where('month', $from->format('Y-m'))
            ->value('number_of_visitors');

        if ($fromSalary > 0) {
            return $fromSalary;
        }

        return (int) \App\Models\Seller::where('id', $sellerId)->value('visitors');
    }

    /**
     * Sales per month over the last N months.
     *
     * Grouped in PHP over a set of explicit ranges rather than with YEAR() and
     * MONTH(), so it runs on any database.
     */
    public function monthlyRevenue(int $sellerId, int $months = 12): array
    {
        $months = max(1, min($months, 36));
        $series = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $start = Carbon::now()->startOfMonth()->subMonthsNoOverflow($i);
            $end   = (clone $start)->endOfMonth();

            $series[] = [
                'month'  => $start->format('Y-m'),
                'sales'  => $this->orderTotal($sellerId, OrderService::TYPE_SALE, $start, $end),
                'returns'=> $this->orderTotal($sellerId, OrderService::TYPE_RETURN, $start, $end),
                'orders' => $this->orderCount($sellerId, OrderService::TYPE_SALE, $start, $end),
            ];
        }

        return $series;
    }

    /** The seller's best sellers over a period. */
    public function topProducts(int $sellerId, int $limit = 10, ?string $from = null, ?string $to = null): array
    {
        $from = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->startOfMonth();
        $to   = $to   ? Carbon::parse($to)->endOfDay()     : Carbon::now()->endOfMonth();

        return DB::table('order_details')
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->join('products', 'products.id', '=', 'order_details.product_id')
            ->where('orders.owner_id', $sellerId)
            ->where('orders.type', OrderService::TYPE_SALE)
            ->whereBetween('orders.created_at', [$from, $to])
            ->groupBy('products.id', 'products.name', 'products.product_code')
            ->orderByDesc(DB::raw('SUM(order_details.quantity)'))
            ->limit(max(1, min($limit, 50)))
            ->get([
                'products.id',
                'products.name',
                'products.product_code',
                DB::raw('SUM(order_details.quantity) as quantity'),
                DB::raw('SUM(order_details.price * order_details.quantity) as amount'),
            ])
            ->map(fn ($row) => [
                'id'           => (int) $row->id,
                'name'         => $row->name,
                'product_code' => $row->product_code,
                'quantity'     => (float) $row->quantity,
                'amount'       => (float) $row->amount,
            ])
            ->all();
    }

    /** Products the seller is carrying at or below their threshold. */
    public function lowStock(int $sellerId): array
    {
        return Stock::where('stocks.seller_id', $sellerId)
            ->join('products', 'products.id', '=', 'stocks.product_id')
            ->whereColumn('stocks.stock', '<=', 'products.limit_stock')
            ->orderBy('stocks.stock')
            ->get(['products.id', 'products.name', 'products.product_code', 'stocks.stock', 'products.limit_stock'])
            ->map(fn ($row) => [
                'id'           => (int) $row->id,
                'name'         => $row->name,
                'product_code' => $row->product_code,
                'stock'        => (float) $row->stock,
                'limit_stock'  => (float) $row->limit_stock,
            ])
            ->all();
    }

    private function orderTotal(int $sellerId, int $type, Carbon $from, Carbon $to): float
    {
        return (float) Order::where('owner_id', $sellerId)
            ->where('type', $type)
            ->whereBetween('created_at', [$from, $to])
            ->sum('order_amount');
    }

    private function orderCount(int $sellerId, int $type, Carbon $from, Carbon $to): int
    {
        return Order::where('owner_id', $sellerId)
            ->where('type', $type)
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }

    private function stockValue(int $sellerId): float
    {
        return (float) Stock::where('stocks.seller_id', $sellerId)
            ->join('products', 'products.id', '=', 'stocks.product_id')
            ->sum(DB::raw('stocks.stock * products.selling_price'));
    }

    private function lowStockCount(int $sellerId): int
    {
        return Stock::where('stocks.seller_id', $sellerId)
            ->join('products', 'products.id', '=', 'stocks.product_id')
            ->whereColumn('stocks.stock', '<=', 'products.limit_stock')
            ->count();
    }
}
