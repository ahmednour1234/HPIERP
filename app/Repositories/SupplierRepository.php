<?php

namespace App\Repositories;

use App\Models\Supplier;
use App\Models\Transection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class SupplierRepository extends CrudRepository
{
    protected array $searchable = ['name', 'mobile', 'email', 'address'];

    public function __construct(Supplier $model)
    {
        parent::__construct($model);
    }

    public function inCity(string $city, int $perPage = 25, int $page = 1): LengthAwarePaginator
    {
        return $this->query()->where('city', 'LIKE', "%{$city}%")
            ->latest('id')->paginate($perPage, ['*'], 'page', $page);
    }

    /** A supplier's ledger, newest first, optionally bounded by date. */
    public function transactions(
        int $supplierId,
        ?string $from = null,
        ?string $to = null,
        int $perPage = 25,
        int $page = 1
    ): LengthAwarePaginator {
        return Transection::where('supplier_id', $supplierId)
            ->when($from, fn (Builder $q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('date', '<=', $to))
            ->latest('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
