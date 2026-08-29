<?php

namespace App\Services;

use App\Repositories\BrandRepository;

class BrandService extends CrudService
{
    protected ?string $imageFolder = 'brand';

    // brands.image is NOT NULL with no default.
    protected array $defaults = ['local_id' => 0, 'image' => 'def.png'];

    public function __construct(BrandRepository $repository)
    {
        $this->repository = $repository;
    }
}
