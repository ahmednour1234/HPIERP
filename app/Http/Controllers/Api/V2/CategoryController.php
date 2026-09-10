<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\Api\V1\CategoryRequest;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends CrudController
{
    protected string $resource = CategoryResource::class;
    protected string $request  = CategoryRequest::class;
    protected string $label    = 'Category';
    protected bool $hasStatus  = true;

    public function __construct(CategoryService $service)
    {
        $this->service = $service;
    }

    /**
     * Categories, with the filters the app needs.
     *
     * The shared CrudController::index forwards search/limit/offset only, so
     * this overrides it to pass type, status and parent_id through as well.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'type'      => ['nullable', 'integer'],
            'status'    => ['nullable', 'boolean'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        return $this->ok(
            CategoryResource::collection($this->service->list(
                $request->only(['search', 'limit', 'offset', 'type', 'status', 'parent_id'])
            )),
            'Categories retrieved'
        );
    }

    /** Sub-categories of a parent category. */
    public function children(Request $request, int $id): JsonResponse
    {
        return $this->ok(
            CategoryResource::collection($this->service->children($id, $request->only(['limit', 'offset']))),
            'Sub-categories retrieved'
        );
    }

    /** Create a sub-category under a parent. */
    public function storeChild(int $id): JsonResponse
    {
        $request = app($this->request);

        return $this->created(
            new CategoryResource($this->service->createChild($id, $request->modelData(), $request->file('image'))),
            'Sub-category created'
        );
    }
}
