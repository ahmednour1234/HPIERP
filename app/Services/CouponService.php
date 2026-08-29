<?php

namespace App\Services;

use App\Repositories\CouponRepository;

class CouponService extends CrudService
{
    // coupons has no local_id column.
    protected array $defaults = [];

    public function __construct(CouponRepository $repository)
    {
        $this->repository = $repository;
    }
}
