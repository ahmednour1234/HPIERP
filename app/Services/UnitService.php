<?php

namespace App\Services;

use App\Repositories\UnitRepository;

class UnitService extends CrudService
{

    public function __construct(UnitRepository $repository)
    {
        $this->repository = $repository;
    }
}
