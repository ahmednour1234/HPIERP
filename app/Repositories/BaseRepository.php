<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Shared Eloquent access for the repositories below it.
 *
 * Repositories own data access only: querying, writing, and eager-loading.
 * Business rules belong in the matching Service.
 */
abstract class BaseRepository
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function query(): Builder
    {
        return $this->model->newQuery();
    }

    public function all(array $with = []): Collection
    {
        return $this->query()->with($with)->get();
    }

    public function paginate(int $perPage = 25, array $with = []): LengthAwarePaginator
    {
        return $this->query()->with($with)->latest($this->model->getKeyName())->paginate($perPage);
    }

    public function find($id, array $with = []): ?Model
    {
        return $this->query()->with($with)->find($id);
    }

    /** @throws ModelNotFoundException handled centrally by the exception handler. */
    public function findOrFail($id, array $with = []): Model
    {
        return $this->query()->with($with)->findOrFail($id);
    }

    public function create(array $attributes): Model
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function update($id, array $attributes): Model
    {
        $record = $this->findOrFail($id);
        $record->fill($attributes)->save();

        return $record->refresh();
    }

    public function delete($id): bool
    {
        return (bool) $this->findOrFail($id)->delete();
    }

    public function exists($id): bool
    {
        return $this->query()->whereKey($id)->exists();
    }
}
