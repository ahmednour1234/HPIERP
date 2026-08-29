<?php

namespace App\Services;

use App\Repositories\AccountRepository;

class AccountService extends CrudService
{
    // accounts has no local_id column.
    protected array $defaults = [];

    public function __construct(AccountRepository $repository)
    {
        $this->repository = $repository;
    }
}
