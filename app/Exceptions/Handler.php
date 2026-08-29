<?php

namespace App\Exceptions;

use App\Http\Middleware\StandardApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var string[]
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var string[]
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into a response.
     *
     * Routes carrying the `api.standard` middleware get the unified envelope.
     * Every other route falls through to Laravel's default rendering, so the
     * legacy API payloads are untouched.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        if ($this->shouldUseStandardEnvelope($request)) {
            return $this->renderStandard($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Unauthenticated requests on a standardised route answer in the envelope
     * rather than redirecting to a login page.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($this->shouldUseStandardEnvelope($request)) {
            return $this->envelope('Unauthenticated', JsonResponse::HTTP_UNAUTHORIZED);
        }

        return parent::unauthenticated($request, $exception);
    }

    private function shouldUseStandardEnvelope(Request $request): bool
    {
        return StandardApiResponse::enabled($request);
    }

    private function renderStandard(Request $request, Throwable $e): JsonResponse
    {
        // Validation — surface the field-keyed bag so clients can map errors
        // back onto their form inputs.
        if ($e instanceof ValidationException) {
            return $this->envelope(
                'The given data was invalid',
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                $e->errors()
            );
        }

        if ($e instanceof AuthenticationException) {
            return $this->envelope('Unauthenticated', JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Domain rules the services enforce. They throw rather than return an
        // HTTP status, so the rule stays testable outside a request.
        if ($e instanceof \App\Services\Exceptions\InsufficientBalanceException
            || $e instanceof \App\Services\Exceptions\InsufficientStockException) {
            return $this->envelope($e->getMessage(), JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        // A service rejecting its arguments (e.g. transferring to the same
        // account) is a client error, not a crash.
        if ($e instanceof \InvalidArgumentException) {
            return $this->envelope($e->getMessage(), JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($e instanceof AuthorizationException) {
            return $this->envelope(
                $e->getMessage() ?: 'This action is not allowed',
                JsonResponse::HTTP_FORBIDDEN
            );
        }

        // A missing model reads better as "Customer not found" than as the
        // framework's class-name-and-id message.
        if ($e instanceof ModelNotFoundException) {
            $model = class_basename($e->getModel());
            return $this->envelope($model . ' not found', JsonResponse::HTTP_NOT_FOUND);
        }

        if ($e instanceof NotFoundHttpException) {
            return $this->envelope('Endpoint not found', JsonResponse::HTTP_NOT_FOUND);
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return $this->envelope(
                'This endpoint does not accept ' . $request->method() . ' requests',
                JsonResponse::HTTP_METHOD_NOT_ALLOWED
            );
        }

        if ($e instanceof TooManyRequestsHttpException) {
            return $this->envelope('Too many requests. Please slow down.', JsonResponse::HTTP_TOO_MANY_REQUESTS);
        }

        if ($e instanceof HttpExceptionInterface) {
            return $this->envelope(
                $e->getMessage() ?: 'Request failed',
                $e->getStatusCode()
            );
        }

        // Anything unhandled: never leak internals in production, but keep the
        // detail while debugging.
        $this->report($e);

        if (config('app.debug')) {
            return $this->envelope('Server error: ' . $e->getMessage(), JsonResponse::HTTP_INTERNAL_SERVER_ERROR, [
                'exception' => get_class($e),
                'file'      => $e->getFile() . ':' . $e->getLine(),
            ]);
        }

        return $this->envelope('Server error. Please try again later.', JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
    }

    private function envelope(string $message, int $status, array $errors = []): JsonResponse
    {
        $payload = ['success' => false, 'message' => $message];
        if ($errors) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
