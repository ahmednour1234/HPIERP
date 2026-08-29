<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\Api\V1\UnitRequest;
use App\Http\Resources\Api\V1\UnitResource;
use App\Services\UnitService;

class UnitController extends CrudController
{
    protected string $resource = UnitResource::class;
    protected string $request  = UnitRequest::class;
    protected string $label    = 'Unit';
    protected bool $hasStatus  = false;

    public function __construct(UnitService $service)
    {
        $this->service = $service;
    }
}
