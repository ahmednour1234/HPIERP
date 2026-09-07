<?php

use App\Http\Controllers\Api\V2\AccountController;
use App\Http\Controllers\Api\V2\AttendanceController;
use App\Http\Controllers\Api\V2\BrandController;
use App\Http\Controllers\Api\V2\CategoryController;
use App\Http\Controllers\Api\V2\CouponController;
use App\Http\Controllers\Api\V2\CustomerController;
use App\Http\Controllers\Api\V2\DashboardController;
use App\Http\Controllers\Api\V2\ProductController;
use App\Http\Controllers\Api\V2\OrderController;
use App\Http\Controllers\Api\V2\ProfileController;
use App\Http\Controllers\Api\V2\ReferenceController;
use App\Http\Controllers\Api\V2\ReservationController;
use App\Http\Controllers\Api\V2\SalaryController;
use App\Http\Controllers\Api\V2\SellerDepositController;
use App\Http\Controllers\Api\V2\StockController;
use App\Http\Controllers\Api\V2\SupplierController;
use App\Http\Controllers\Api\V2\TransactionController;
use App\Http\Controllers\Api\V2\UnitController;
use App\Http\Controllers\Api\V2\VisitController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v2
|--------------------------------------------------------------------------
|
| Endpoints refactored onto the service/repository split, FormRequest
| validation, API Resources and the standard { success, message, data }
| envelope. v1 is untouched, so existing clients keep their current payloads
| and can migrate module by module.
|
| The `api.standard` middleware is what opts these routes into the unified
| error rendering in App\Exceptions\Handler.
|
*/

Route::group(['prefix' => 'v2', 'middleware' => ['api.standard']], function () {

    Route::group(['middleware' => ['auth:admin-api']], function () {

        Route::group(['prefix' => 'stocks'], function () {
            Route::get('/', [StockController::class, 'index']);
            Route::post('confirm', [StockController::class, 'confirm']);
            Route::get('history', [StockController::class, 'history']);
        });


        // The lookup tables all expose the same CRUD surface (see CrudController).
        foreach ([
            'brands'     => BrandController::class,
            'units'      => UnitController::class,
            'accounts'   => AccountController::class,
            'categories' => CategoryController::class,
            'coupons'    => CouponController::class,
        ] as $prefix => $controller) {
            Route::group(['prefix' => $prefix], function () use ($controller) {
                Route::get('/', [$controller, 'index']);
                Route::post('/', [$controller, 'store']);
                Route::put('/', [$controller, 'update']);
                Route::get('{id}', [$controller, 'show'])->whereNumber('id');
                Route::delete('{id}', [$controller, 'destroy'])->whereNumber('id');
                Route::patch('{id}/status', [$controller, 'toggleStatus'])->whereNumber('id');
            });
        }


        Route::group(['prefix' => 'suppliers'], function () {
            Route::get('/', [SupplierController::class, 'index']);
            Route::post('/', [SupplierController::class, 'store']);
            Route::put('/', [SupplierController::class, 'update']);
            Route::get('by-city', [SupplierController::class, 'byCity']);
            Route::post('pay', [SupplierController::class, 'pay']);
            Route::get('{id}', [SupplierController::class, 'show'])->whereNumber('id');
            Route::get('{id}/transactions', [SupplierController::class, 'transactions'])->whereNumber('id');
            Route::delete('{id}', [SupplierController::class, 'destroy'])->whereNumber('id');
        });

        Route::group(['prefix' => 'transactions'], function () {
            Route::get('/', [TransactionController::class, 'index']);
            Route::get('types', [TransactionController::class, 'types']);
            Route::get('totals', [TransactionController::class, 'totals']);
            Route::post('transfer', [TransactionController::class, 'transfer']);
            Route::post('expense', [TransactionController::class, 'expense']);
            Route::post('income', [TransactionController::class, 'income']);
        });


        Route::group(['prefix' => 'visits'], function () {
            Route::get('/', [VisitController::class, 'index']);
            Route::post('/', [VisitController::class, 'store']);
            Route::get('results', [VisitController::class, 'results']);
            Route::post('results', [VisitController::class, 'storeResult']);
            Route::get('customers/{id}/results', [VisitController::class, 'customerResults'])->whereNumber('id');
        });

        Route::get('attendance', [AttendanceController::class, 'index']);

        // شؤون المندوب: كانت متاحة في اللوحة فقط ولا يراها المندوب.
        Route::group(['prefix' => 'hr'], function () {
            Route::get('ratings', [\App\Http\Controllers\Api\V2\SellerHrController::class, 'ratings']);
            Route::get('development', [\App\Http\Controllers\Api\V2\SellerHrController::class, 'development']);
            Route::get('courses', [\App\Http\Controllers\Api\V2\SellerHrController::class, 'courses']);

            Route::get('requests', [\App\Http\Controllers\Api\V2\SellerHrController::class, 'requests']);
            Route::post('requests', [\App\Http\Controllers\Api\V2\SellerHrController::class, 'storeRequest']);

            Route::get('leaves', [\App\Http\Controllers\Api\V2\SellerHrController::class, 'leaves']);
            Route::post('leaves', [\App\Http\Controllers\Api\V2\SellerHrController::class, 'storeLeave']);
        });

        // Stock requests a seller files from the app.
        Route::group(['prefix' => 'reservations'], function () {
            Route::get('/', [ReservationController::class, 'index']);
            Route::post('/', [ReservationController::class, 'store']);
            Route::get('{id}', [ReservationController::class, 'show'])->whereNumber('id');
        });

        // The seller's own payslips.
        Route::get('salary', [SalaryController::class, 'show']);
        Route::get('salary/history', [SalaryController::class, 'history']);

        Route::get('profile', [ProfileController::class, 'show']);
        Route::post('profile/change-password', [ProfileController::class, 'changePassword']);



        // Sub-categories are categories with a parent, so they hang off the
        // category module rather than duplicating its CRUD.
        Route::get('categories/{id}/children', [CategoryController::class, 'children'])->whereNumber('id');
        Route::post('categories/{id}/children', [CategoryController::class, 'storeChild'])->whereNumber('id');


        // Read-only pickers for the mobile app.
        // التخصصات الطبية (categories.type=0) وفئات المنتجات (type=1).
        Route::get('specialties', [ReferenceController::class, 'specialties']);
        Route::get('product-categories', [ReferenceController::class, 'productCategories']);
        Route::get('regions', [ReferenceController::class, 'regions']);
        Route::get('regions/mine', [ReferenceController::class, 'myRegions']);
        Route::get('storages', [ReferenceController::class, 'storages']);
        Route::get('documents', [ReferenceController::class, 'documents']);

        Route::group(['prefix' => 'dashboard'], function () {
            Route::get('summary', [DashboardController::class, 'summary']);
            Route::get('monthly-revenue', [DashboardController::class, 'monthlyRevenue']);
            Route::get('top-products', [DashboardController::class, 'topProducts']);
            Route::get('low-stock', [DashboardController::class, 'lowStock']);
        });


        // Cash the seller collected, handed in to a company account. Filed as
        // pending; an admin approves it before any balance moves.
        Route::group(['prefix' => 'deposits'], function () {
            Route::get('/', [SellerDepositController::class, 'index']);
            Route::post('/', [SellerDepositController::class, 'store']);
            Route::get('summary', [SellerDepositController::class, 'summary']);
            Route::get('{id}', [SellerDepositController::class, 'show'])->whereNumber('id');
        });

        Route::group(['prefix' => 'orders'], function () {
            Route::get('/', [OrderController::class, 'index']);
            Route::post('/', [OrderController::class, 'store']);
            // Returns are filed against the invoice they reverse.
            Route::get('{id}/returnable', [OrderController::class, 'returnable'])->whereNumber('id');
            Route::post('returns', [OrderController::class, 'storeReturn']);
            // Collecting against a specific invoice, as opposed to
            // customers/add-balance which settles an overall balance.
            Route::post('{id}/collect', [OrderController::class, 'collect'])->whereNumber('id');

            Route::get('totals', [OrderController::class, 'totals']);
            Route::get('customers/{id}', [OrderController::class, 'forCustomer'])->whereNumber('id');
            Route::get('{id}', [OrderController::class, 'show'])->whereNumber('id');
        });

        Route::group(['prefix' => 'products'], function () {
            Route::get('/', [ProductController::class, 'index']);
            Route::post('/', [ProductController::class, 'store']);
            Route::put('/', [ProductController::class, 'update']);
            Route::get('by-code', [ProductController::class, 'byCode']);
            Route::get('low-stock', [ProductController::class, 'lowStock']);
            Route::post('customer-prices', [ProductController::class, 'customerPrices']);
            Route::post('customer-price', [ProductController::class, 'setCustomerPrice']);
            Route::get('{id}', [ProductController::class, 'show'])->whereNumber('id');
            Route::delete('{id}', [ProductController::class, 'destroy'])->whereNumber('id');
        });

        Route::group(['prefix' => 'customers'], function () {
            Route::get('/', [CustomerController::class, 'index']);
            Route::post('/', [CustomerController::class, 'store']);
            Route::post('add-balance', [CustomerController::class, 'addBalance']);
            Route::get('{id}', [CustomerController::class, 'show'])->whereNumber('id');
            Route::put('/', [CustomerController::class, 'update']);
            Route::delete('{id}', [CustomerController::class, 'destroy'])->whereNumber('id');
        });
    });
});
