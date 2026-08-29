<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;
use Symfony\Component\HttpFoundation\Response as Status;

/**
 * One shape for every JSON response this API returns.
 *
 *   success: { "success": true,  "message": "...", "data": ... }
 *   error:   { "success": false, "message": "...", "errors": {...} }
 *
 * Applied to newly refactored endpoints only — the legacy controllers keep
 * their original payloads so existing mobile/web clients are unaffected.
 */
trait ApiResponse
{
    /**
     * A successful response. `$data` may be a Resource, a paginator, or a
     * plain array; paginators are unwrapped into a `meta` block.
     */
    protected function ok($data = null, string $message = 'OK', int $status = Status::HTTP_OK): JsonResponse
    {
        $payload = ['success' => true, 'message' => $message];

        if ($data instanceof ResourceCollection) {
            $resolved = $data->response()->getData(true);
            $payload['data'] = $resolved['data'] ?? [];

            // Report our own compact meta rather than Laravel's, which carries
            // a rendered "links" array meant for server-side pagination views.
            if ($data->resource instanceof AbstractPaginator) {
                $payload['meta'] = $this->paginationMeta($data->resource);
            }
        } elseif ($data instanceof AbstractPaginator) {
            $payload['data'] = $data->items();
            $payload['meta'] = $this->paginationMeta($data);
        } elseif ($data instanceof JsonResource) {
            $payload['data'] = $data->resolve();
        } elseif ($data !== null) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status);
    }

    /** A resource was created. */
    protected function created($data = null, string $message = 'Created'): JsonResponse
    {
        return $this->ok($data, $message, Status::HTTP_CREATED);
    }

    /** A successful action with nothing to return. */
    protected function noContent(string $message = 'Done'): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message], Status::HTTP_OK);
    }

    /**
     * An error response. `$errors` is the field-keyed bag used by validation
     * failures; omit it for plain messages.
     */
    protected function fail(
        string $message,
        int $status = Status::HTTP_BAD_REQUEST,
        array $errors = []
    ): JsonResponse {
        $payload = ['success' => false, 'message' => $message];
        if ($errors) $payload['errors'] = $errors;

        return response()->json($payload, $status);
    }

    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->fail($message, Status::HTTP_NOT_FOUND);
    }

    protected function unauthorized(string $message = 'Unauthenticated'): JsonResponse
    {
        return $this->fail($message, Status::HTTP_UNAUTHORIZED);
    }

    protected function forbidden(string $message = 'This action is not allowed'): JsonResponse
    {
        return $this->fail($message, Status::HTTP_FORBIDDEN);
    }

    protected function invalid(array $errors, string $message = 'The given data was invalid'): JsonResponse
    {
        return $this->fail($message, Status::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }

    private function paginationMeta(AbstractPaginator $p): array
    {
        $meta = [
            'current_page' => $p->currentPage(),
            'per_page'     => $p->perPage(),
        ];
        if (method_exists($p, 'total')) {
            $meta['total']     = $p->total();
            $meta['last_page'] = $p->lastPage();
        }
        return $meta;
    }
}
