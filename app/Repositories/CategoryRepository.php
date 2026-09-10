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
     * Categories, narrowed by type / status / parent.
     *
     * listing() on the base repository searches by name only, and it is shared
     * by the other lookup modules, so the extra filters live here: the app
     * needs to ask for medical specialties (type 0) apart from product
     * categories (type 1), which one flag on the shared method could not do.
     */
    public function filtered(array $filters, int $perPage = 25, int $page = 1): LengthAwarePaginator
    {
        $query = $this->query();

        if (($search = $filters['search'] ?? null) !== null && $search !== '') {
            $query->where('name', 'LIKE', "%{$search}%");
        }

        // type و status و parent_id قد تكون 0 وهي قيمة صالحة، فلا يصلح
        // فحص الامتلاء وحده.
        if (($type = $filters['type'] ?? null) !== null && $type !== '') {
            $query->where('type', (int) $type);
        }

        if (($status = $filters['status'] ?? null) !== null && $status !== '') {
            $query->where('status', (int) $status);
        }

        if (($parent = $filters['parent_id'] ?? null) !== null && $parent !== '') {
            $query->where('parent_id', (int) $parent);
        }

        return $query->latest('id')->paginate($perPage, ['*'], 'page', $page);
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
