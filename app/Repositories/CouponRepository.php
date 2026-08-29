<?php

namespace App\Repositories;

use App\Models\Coupon;

class CouponRepository extends CrudRepository
{
    protected array $searchable = ['title', 'code'];

    public function __construct(Coupon $model)
    {
        parent::__construct($model);
    }
}
