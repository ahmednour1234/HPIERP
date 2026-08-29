<?php

namespace App\Repositories;

use App\Models\Account;

class AccountRepository extends CrudRepository
{
    protected array $searchable = ['account', 'account_number', 'description'];

    public function __construct(Account $model)
    {
        parent::__construct($model);
    }
}
