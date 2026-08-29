<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\Api\V1\AccountRequest;
use App\Http\Resources\Api\V1\AccountResource;
use App\Services\AccountService;

class AccountController extends CrudController
{
    protected string $resource = AccountResource::class;
    protected string $request  = AccountRequest::class;
    protected string $label    = 'Account';
    protected bool $hasStatus  = false;

    public function __construct(AccountService $service)
    {
        $this->service = $service;
    }
}
