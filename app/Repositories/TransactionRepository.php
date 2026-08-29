<?php

namespace App\Repositories;

use App\Models\Transection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class TransactionRepository extends BaseRepository
{
    public function __construct(Transection $model)
    {
        parent::__construct($model);
    }

    /** The ledger with the filters the reporting screens use. */
    public function listing(array $filters, int $perPage = 25, int $page = 1): LengthAwarePaginator
    {
        return $this->query()
            ->tap(fn (Builder $q) => $this->applyFilters($q, $filters))
            ->latest('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * The filter chain shared by list() and totals(), so a summary can never
     * disagree with the rows it sits above.
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['type'] ?? null, fn (Builder $q, $t) => $q->where('tran_type', $t))
            ->when($filters['account_id'] ?? null, fn (Builder $q, $a) => $q->where('account_id', $a))
            ->when($filters['seller_id'] ?? null, fn (Builder $q, $s) => $q->where('seller_id', $s))
            ->when($filters['customer_id'] ?? null, fn (Builder $q, $c) => $q->where('customer_id', $c))
            ->when($filters['supplier_id'] ?? null, fn (Builder $q, $s) => $q->where('supplier_id', $s))
            ->when($filters['from'] ?? null, fn (Builder $q, $d) => $q->whereDate('date', '>=', $d))
            ->when($filters['to'] ?? null, fn (Builder $q, $d) => $q->whereDate('date', '<=', $d))
            ->when($filters['order_id'] ?? null, fn (Builder $q, $o) => $q->where('order_id', $o))
            ->when($filters['min_amount'] ?? null, fn (Builder $q, $v) => $q->where('amount', '>=', $v))
            ->when($filters['max_amount'] ?? null, fn (Builder $q, $v) => $q->where('amount', '<=', $v))
            ->when(isset($filters['cash']) && $filters['cash'] !== '',
                fn (Builder $q) => $q->where('cash', (int) $filters['cash']))
            // debit = money in, credit = money out.
            ->when($filters['direction'] ?? null, fn (Builder $q, $d) => match ($d) {
                'in'    => $q->where('debit', 1),
                'out'   => $q->where('credit', 1),
                default => null,
            })
            ->when($filters['search'] ?? null, fn (Builder $q, $term) =>
                $q->where(fn (Builder $i) => $i->where('description', 'LIKE', "%{$term}%")
                                               ->orWhere('id', $term)
                                               ->orWhere('order_id', $term)));
    }

    /** Money in, money out and the net, for the current filter. */
    public function totals(array $filters): array
    {
        $row = $this->applyFilters($this->query(), $filters)
            ->selectRaw("COUNT(*) as entries,
                         COALESCE(SUM(CASE WHEN debit  = 1 THEN amount ELSE 0 END), 0) as money_in,
                         COALESCE(SUM(CASE WHEN credit = 1 THEN amount ELSE 0 END), 0) as money_out")
            ->first();

        $in  = (float) ($row->money_in ?? 0);
        $out = (float) ($row->money_out ?? 0);

        return [
            'entries'   => (int) ($row->entries ?? 0),
            'money_in'  => $in,
            'money_out' => $out,
            'net'       => round($in - $out, 2),
        ];
    }

    /** The distinct transaction types present in the ledger. */
    public function types(): array
    {
        return $this->query()
            ->whereNotNull('tran_type')
            ->distinct()
            ->orderBy('tran_type')
            ->pluck('tran_type')
            ->all();
    }

    /** Sum of a type over an optional date window. */
    public function total(string $type, ?string $from = null, ?string $to = null): float
    {
        return (float) $this->query()
            ->where('tran_type', $type)
            ->when($from, fn (Builder $q, $d) => $q->whereDate('date', '>=', $d))
            ->when($to, fn (Builder $q, $d) => $q->whereDate('date', '<=', $d))
            ->sum('amount');
    }
}
