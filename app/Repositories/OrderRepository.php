<?php

namespace App\Repositories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderRepository extends BaseRepository
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
    }

    /**
     * A seller's orders, newest first.
     *
     * `type` distinguishes sales (4) from returns (7) and the installment
     * variants (12, 24).
     */
    public function forSeller(int $sellerId, array $filters, int $perPage = 25, int $page = 1): LengthAwarePaginator
    {
        return $this->query()
            ->where('owner_id', $sellerId)
            ->with(['details', 'customer:id,name,mobile'])
            ->select('orders.*')
            // مجموع المرتجع على الفاتورة في نفس الاستعلام: حسابه من علاقة
            // لكل صف يجعل عرض 25 فاتورة 26 استعلامًا.
            ->selectSub(
                fn ($q) => $q->from('orders as returns')
                    ->selectRaw('COALESCE(SUM(returns.order_amount), 0)')
                    ->whereColumn('returns.parent_id', 'orders.id')
                    ->where('returns.type', 7),
                'returned_amount'
            )
            ->tap(fn (Builder $q) => $this->applyFilters($q, $filters))
            ->when(
                in_array($filters['sort'] ?? '', ['amount_asc', 'amount_desc', 'oldest'], true),
                fn (Builder $q) => match ($filters['sort']) {
                    'amount_asc'  => $q->orderBy('order_amount'),
                    'amount_desc' => $q->orderByDesc('order_amount'),
                    'oldest'      => $q->oldest('id'),
                },
                fn (Builder $q) => $q->latest('id')
            )
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * The filter chain shared by forSeller() and totalsForSeller(), so the
     * summary above a list can never drift from the rows beneath it.
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['type'] ?? null, fn (Builder $q, $t) => $q->where('type', $t))
            ->when($filters['customer_id'] ?? null, fn (Builder $q, $c) => $q->where('user_id', $c))
            ->when($filters['from'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['to'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($filters['account_id'] ?? null, fn (Builder $q, $a) => $q->where('payment_id', $a))
            ->when(isset($filters['cash']) && $filters['cash'] !== '',
                fn (Builder $q) => $q->where('cash', (int) $filters['cash']))
            ->when($filters['min_amount'] ?? null, fn (Builder $q, $v) => $q->where('order_amount', '>=', $v))
            ->when($filters['max_amount'] ?? null, fn (Builder $q, $v) => $q->where('order_amount', '<=', $v))
            // Settlement state, derived rather than stored: an order is paid
            // when what was collected covers the total.
            ->when($filters['payment_status'] ?? null, function (Builder $q, $state) {
                match ($state) {
                    'paid'    => $q->whereColumn('collected_cash', '>=', 'order_amount'),
                    'partial' => $q->whereColumn('collected_cash', '<', 'order_amount')
                                   ->where('collected_cash', '>', 0),
                    'unpaid'  => $q->where(fn (Builder $i) => $i->whereNull('collected_cash')
                                                                ->orWhere('collected_cash', 0)),
                    default   => null,
                };
            })
            ->when($filters['product_id'] ?? null, fn (Builder $q, $p) =>
                $q->whereHas('details', fn (Builder $d) => $d->where('product_id', $p)))
            ->when($filters['search'] ?? null, function (Builder $q, $term) {
                $q->where(function (Builder $inner) use ($term) {
                    $inner->where('id', $term)
                          ->orWhere('transaction_reference', 'LIKE', "%{$term}%")
                          ->orWhereHas('customer', fn (Builder $c) => $c->where('name', 'LIKE', "%{$term}%"));
                });
            });
    }

    /** One order with everything an invoice needs. */
    public function withDetails(int $orderId): ?Order
    {
        return $this->query()->with(['details', 'customer'])->find($orderId);
    }

    /**
     * Totals for the same filter set as forSeller().
     *
     * Computed in SQL over the whole result, not the current page, so the
     * figure above the list is the total for the filter rather than for the
     * 25 rows that happen to be on screen.
     */
    public function totalsForSeller(int $sellerId, array $filters): array
    {
        $row = $this->applyFilters($this->query()->where('owner_id', $sellerId), $filters)
            ->selectRaw('COUNT(*) as orders,
                         COALESCE(SUM(order_amount), 0)   as total,
                         COALESCE(SUM(collected_cash), 0) as collected,
                         COALESCE(SUM(total_tax), 0)      as tax')
            ->first();

        $total     = (float) ($row->total ?? 0);
        $collected = (float) ($row->collected ?? 0);

        return [
            'orders'    => (int) ($row->orders ?? 0),
            'total'     => $total,
            'collected' => $collected,
            'tax'       => (float) ($row->tax ?? 0),
            // What is still owed on the orders this filter matched.
            'remaining' => round(max($total - $collected, 0), 2),
        ];
    }

    /** A customer's order history. */
    public function forCustomer(int $customerId, int $perPage = 25, int $page = 1): LengthAwarePaginator
    {
        return $this->query()
            ->where('user_id', $customerId)
            ->with('details')
            ->latest('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
