<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\Api\V1\CouponRequest;
use App\Http\Resources\Api\V1\CouponResource;
use App\Services\CouponService;

class CouponController extends CrudController
{
    protected string $resource = CouponResource::class;
    protected string $request  = CouponRequest::class;
    protected string $label    = 'Coupon';
    protected bool $hasStatus  = true;

    public function __construct(CouponService $service)
    {
        $this->service = $service;
    }
}
