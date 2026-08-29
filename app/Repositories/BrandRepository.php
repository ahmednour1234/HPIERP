<?php

namespace App\Repositories;

use App\Models\Brand;

class BrandRepository extends CrudRepository
{
    protected array $searchable = ['name'];

    public function __construct(Brand $model)
    {
        parent::__construct($model);
    }
}
