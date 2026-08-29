<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Services\CrudService;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * The five lookup modules (brands, units, accounts, categories, coupons) expose
 * the same endpoints, so the wiring lives here and each subclass only names its
 * service, resource and form request.
 */
abstract class CrudController extends Controller
{
    use ApiResponse;

    protected CrudService $service;

    /** Resource class used to render one record. */
    protected string $resource;

    /** FormRequest class used for create and update. */
    protected string $request;

    /** Singular label used in response messages, e.g. "Brand". */
    protected string $label = 'Record';

    /** Whether the table has a boolean status column. */
    protected bool $hasStatus = false;

    public function index(Request $request): JsonResponse
    {
        $records = $this->service->list($request->only(['search', 'limit', 'offset']));

        return $this->ok(
            $this->resource::collection($records),
            // Str::plural, not $label . 's' - that produced "Categorys".
            Str::plural($this->label) . ' retrieved'
        );
    }

    public function show($id): JsonResponse
    {
        return $this->ok(
            new $this->resource($this->service->find($id)),
            $this->label . ' retrieved'
        );
    }

    public function store(): JsonResponse
    {
        $request = app($this->request);

        $record = $this->service->create($request->modelData(), $request->file('image'));

        return $this->created(new $this->resource($record), $this->label . ' created');
    }

    public function update(): JsonResponse
    {
        /** @var FormRequest $request */
        $request = app($this->request);

        $record = $this->service->update(
            $request->input('id'),
            $request->modelData(),
            $request->file('image')
        );

        return $this->ok(new $this->resource($record), $this->label . ' updated');
    }

    public function destroy($id): JsonResponse
    {
        $this->service->delete($id);

        return $this->noContent($this->label . ' deleted');
    }

    public function toggleStatus($id): JsonResponse
    {
        if (!$this->hasStatus) {
            return $this->fail($this->label . ' has no status to toggle', 400);
        }

        return $this->ok(
            new $this->resource($this->service->toggleStatus($id)),
            'Status updated'
        );
    }
}
