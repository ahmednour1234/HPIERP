<?php

namespace App\Repositories;

use App\Models\ResultVisitor;
use App\Models\Visitor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Planned visits (visitors) and their outcomes (result_visitors).
 */
class VisitRepository extends BaseRepository
{
    public function __construct(Visitor $model)
    {
        parent::__construct($model);
    }

    /** A seller's planned visits, newest first. */
    public function planned(int $sellerId, array $filters, int $perPage = 25, int $page = 1): LengthAwarePaginator
    {
        return $this->query()
            ->where('seller_id', $sellerId)
            ->with(['customer:id,name,mobile,address'])
            ->when($filters['date'] ?? null, fn (Builder $q, $d) => $q->whereDate('date', $d))
            ->when($filters['customer_id'] ?? null, fn (Builder $q, $c) => $q->where('customer_id', $c))
            ->latest('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /** Recorded visit outcomes for a seller. */
    public function results(int $adminId, array $filters, int $perPage = 25, int $page = 1): LengthAwarePaginator
    {
        return ResultVisitor::where('admin_id', $adminId)
            ->with(['customer:id,name,mobile'])
            ->when($filters['customer_id'] ?? null, fn (Builder $q, $c) => $q->where('customer_id', $c))
            ->latest('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /** Outcomes recorded against one customer, whoever visited. */
    public function resultsForCustomer(int $customerId, int $perPage = 25, int $page = 1): LengthAwarePaginator
    {
        return ResultVisitor::where('customer_id', $customerId)
            ->latest('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function recordResult(array $data): ResultVisitor
    {
        return ResultVisitor::create($data);
    }
}
