<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/*
 * Local-only asset shim.
 *
 * The views call asset('public/...'), which is right on the shared host where
 * the domain document root is the project root. `artisan serve` serves from
 * public/ already, so those URLs come out as /public/assets/... and 404 (then
 * hit the fallback below and redirect, leaving every page unstyled).
 *
 * Rather than rewrite 351 asset() calls - which would break production - map
 * the doubled path back onto the real file. Registered only outside production
 * so the live site is unaffected.
 */
if (!app()->environment('production')) {
    Route::get('public/{path}', function (string $path) {
        $file = public_path($path);

        if (!File::exists($file) || !File::isFile($file)) {
            // assets/admin/js/theme.min.js is excluded by .gitignore, so it is
            // absent from a fresh checkout. Answering with an empty script
            // keeps the console clean; a missing .js otherwise returns the HTML
            // 404 page, which the browser then tries to parse as JavaScript.
            if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'js') {
                return response('', 200, ['Content-Type' => 'application/javascript']);
            }

            abort(404);
        }

        // response()->file() guesses from content, which yields text/plain for
        // css and js - browsers then refuse to apply the stylesheet. Set the
        // type from the extension instead.
        $types = [
            'css' => 'text/css', 'js' => 'application/javascript',
            'svg' => 'image/svg+xml', 'png' => 'image/png', 'gif' => 'image/gif',
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp',
            'ico' => 'image/x-icon', 'json' => 'application/json',
            'woff' => 'font/woff', 'woff2' => 'font/woff2',
            'ttf' => 'font/ttf', 'eot' => 'application/vnd.ms-fontobject',
        ];
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        return response()->file($file, isset($types[$extension])
            ? ['Content-Type' => $types[$extension]]
            : []);
    })->where('path', '.*');
}

Route::fallback(function(){
    return redirect('admin/auth/login');
});

Route::get('authentication-failed', function () {
    $errors = [];
    array_push($errors, ['code' => 'auth-001', 'message' => 'Invalid credential! or unauthenticated.']);
    return response()->json([
        'errors' => $errors
    ], 401);
})->name('authentication-failed');
