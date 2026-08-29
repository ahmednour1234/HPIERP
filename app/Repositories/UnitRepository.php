<?php

namespace App\Repositories;

use App\Models\Unit;

class UnitRepository extends CrudRepository
{
    protected array $searchable = ['unit_type', 'symbol'];

    public function __construct(Unit $model)
    {
        parent::__construct($model);
    }
}
