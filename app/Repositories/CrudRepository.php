<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * A repository for the plain lookup tables (brands, units, categories,
 * coupons, accounts...). They all list, search, create, update and delete the
 * same way, so the behaviour lives here once and each subclass only declares
 * which columns it searches.
 */
abstract class CrudRepository extends BaseRepository
{
    /** Columns a free-text search should cover. */
    protected array $searchable = ['name'];

    /** Relations to eager load on reads. */
    protected array $with = [];

    public function listing(?string $search = null, int $perPage = 25, int $page = 1): LengthAwarePaginator
    {
        $query = $this->query()->with($this->with);

        if ($search !== null && $search !== '') {
            $query->where(function (Builder $q) use ($search) {
                foreach ($this->searchable as $i => $column) {
                    $i === 0
                        ? $q->where($column, 'LIKE', "%{$search}%")
                        : $q->orWhere($column, 'LIKE', "%{$search}%");
                }
            });
        }

        return $query->latest($this->model->getKeyName())->paginate($perPage, ['*'], 'page', $page);
    }

    /** Flip a boolean status column. */
    public function toggleStatus($id, string $column = 'status')
    {
        $record = $this->findOrFail($id);
        $record->{$column} = !$record->{$column};
        $record->save();

        return $record;
    }
}
