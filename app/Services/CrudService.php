<?php

namespace App\Services;

use App\CPU\Helpers;
use App\Repositories\CrudRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Shared behaviour for the lookup-table modules. A subclass only declares its
 * repository, and optionally the image folder and any column defaults the
 * schema requires.
 */
abstract class CrudService
{
    protected CrudRepository $repository;

    /** Storage folder for an uploaded image, or null if the table has none. */
    protected ?string $imageFolder = null;

    /**
     * Columns that are NOT NULL without a database default. Several of these
     * legacy tables carry a `local_id` used for offline sync; rows created
     * server-side get 0.
     */
    protected array $defaults = ['local_id' => 0];

    public function list(array $filters): LengthAwarePaginator
    {
        return $this->repository->listing(
            $filters['search'] ?? null,
            (int) ($filters['limit'] ?? 25),
            (int) ($filters['offset'] ?? 1)
        );
    }

    public function find($id): Model
    {
        return $this->repository->findOrFail($id);
    }

    public function create(array $data, ?UploadedFile $image = null): Model
    {
        if ($image && $this->imageFolder) {
            $data['image'] = Helpers::upload($this->imageFolder . '/', 'png', $image);
        }

        foreach ($this->defaults as $column => $value) {
            $data[$column] = $data[$column] ?? $value;
        }

        // refresh(): a column filled by a database default is not on the
        // in-memory model, so the response would report null/0 for a value the
        // database actually stored.
        return $this->repository->create($data)->refresh();
    }

    public function update($id, array $data, ?UploadedFile $image = null): Model
    {
        if ($image && $this->imageFolder) {
            $data['image'] = Helpers::upload($this->imageFolder . '/', 'png', $image);
        }

        return $this->repository->update($id, $data);
    }

    public function delete($id): void
    {
        $this->repository->delete($id);
    }

    public function toggleStatus($id): Model
    {
        return $this->repository->toggleStatus($id);
    }
}
