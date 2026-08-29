<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoryRepository extends CrudRepository
{
    protected array $searchable = ['name'];

    public function __construct(Category $model)
    {
        parent::__construct($model);
    }

    /**
     * Sub-categories of a parent.
     *
     * A sub-category is a category row with position = 1 and the parent's id
     * in parent_id; there is no separate table.
     */
    public function children(int $parentId, int $perPage = 25, int $page = 1): LengthAwarePaginator
    {
        return $this->query()
            ->where('parent_id', $parentId)
            ->where('position', 1)
            ->latest('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
