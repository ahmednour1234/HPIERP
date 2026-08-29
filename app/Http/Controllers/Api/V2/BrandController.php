<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\Api\V1\BrandRequest;
use App\Http\Resources\Api\V1\BrandResource;
use App\Services\BrandService;

class BrandController extends CrudController
{
    protected string $resource = BrandResource::class;
    protected string $request  = BrandRequest::class;
    protected string $label    = 'Brand';
    protected bool $hasStatus  = false;

    public function __construct(BrandService $service)
    {
        $this->service = $service;
    }
}
