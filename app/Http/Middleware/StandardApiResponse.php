<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Marks a route as using the standardised API envelope.
 *
 * The exception handler checks for this flag and only then converts errors to
 * { success:false, message, errors }. Routes without it keep the legacy error
 * payloads exactly as they were, so existing clients are unaffected.
 *
 * Applied via the `api.standard` alias in Http/Kernel.php.
 */
class StandardApiResponse
{
    public const FLAG = 'api.standard_response';

    public function handle(Request $request, Closure $next)
    {
        $request->attributes->set(self::FLAG, true);

        return $next($request);
    }

    /**
     * Whether the current request opted into the standard envelope.
     *
     * The attribute is the primary signal, but it is only set once this
     * middleware has run. Authentication failures are raised from the guard
     * before that happens, so the route's own middleware list is checked as
     * well - that is available from the moment the route is matched.
     */
    public static function enabled(Request $request): bool
    {
        if ($request->attributes->get(self::FLAG, false)) {
            return true;
        }

        $route = $request->route();

        return $route !== null && in_array('api.standard', $route->gatherMiddleware(), true);
    }
}
