<?php

namespace App\Http;

use App\Http\Middleware\ActivationCheckMiddleware;
use App\Http\Middleware\InstallationMiddleware;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        \App\Http\Middleware\TrustProxies::class,
        \Fruitcake\Cors\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'admin' => \App\Http\Middleware\AdminMiddleware::class,
        'installation-check' => InstallationMiddleware::class,
        'actch' => ActivationCheckMiddleware::class,
        'api_token' => \App\Http\Middleware\EnsureTokenIsValid::class,
        'check.dashboard.access' => \App\Http\Middleware\CheckDashboardAccess::class,
        'check.pos.access' => \App\Http\Middleware\CheckPosAccess::class,
        'check.stock.access' => \App\Http\Middleware\CheckStockAccess::class,
        'check.store.access' => \App\Http\Middleware\CheckStoreAccess::class,
        'check.category.access' => \App\Http\Middleware\CheckCategoryAccess::class,
        'check.unit.access' => \App\Http\Middleware\CheckUnitAccess::class,
        'check.product.access' => \App\Http\Middleware\CheckProductAccess::class,
        'check.customer.access' => \App\Http\Middleware\CheckCustomerAccess::class,
        'check.seller.access' => \App\Http\Middleware\CheckSellerAccess::class,
        'check.admin.access' => \App\Http\Middleware\CheckAdminAccess::class,
        'check.supplier.access' => \App\Http\Middleware\CheckSupplierAccess::class,
        'check.setting.access' => \App\Http\Middleware\CheckSettingAccess::class,
        'check.storage.access' => \App\Http\Middleware\CheckStorageAccess::class,
        'check.notificaion.access' => \App\Http\Middleware\CheckNotificationAccess::class,






    ];
}
