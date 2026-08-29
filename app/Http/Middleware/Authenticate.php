<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param \Illuminate\Http\Request $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        // Routes on the standard envelope let the exception handler answer, so
        // they get { success:false, message:"Unauthenticated" } like every
        // other error. Legacy API routes keep their original auth-001 payload.
        if (StandardApiResponse::enabled($request)) {
            return null;
        }

        if ($request->is('api/*')) {
            $errors[] = ['code' => 'auth-001', 'message' => 'Unauthorized.'];
            abort(response()->json([
                'errors' => $errors
            ], 401));
        } else if (!$request->expectsJson()) {
            return route('login');
        }
    }
}
