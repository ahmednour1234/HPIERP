<?php

namespace App\Services;

use App\Repositories\CategoryRepository;

class CategoryService extends CrudService
{
    protected ?string $imageFolder = 'category';

    // categories.image / parent_id / position are NOT NULL with no default.
    protected array $defaults = [
        'local_id' => 0, 'image' => 'def.png', 'parent_id' => 0, 'position' => 0,
    ];

    public function __construct(CategoryRepository $repository)
    {
        $this->repository = $repository;
    }

    /** Categories with the type / status / parent filters applied. */
    public function list(array $filters): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->repository->filtered(
            $filters,
            (int) ($filters['limit'] ?? 25),
            (int) ($filters['offset'] ?? 1)
        );
    }

    public function children(int $parentId, array $filters): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->repository->children(
            $parentId,
            (int) ($filters['limit'] ?? 25),
            (int) ($filters['offset'] ?? 1)
        );
    }

    /** A sub-category is just a category carrying its parent and position 1. */
    public function createChild(int $parentId, array $data, $image = null): \Illuminate\Database\Eloquent\Model
    {
        return $this->create(array_merge($data, ['parent_id' => $parentId, 'position' => 1]), $image);
    }
}
