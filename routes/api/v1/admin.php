<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\PosController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\UnitController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CouponController;
use App\Http\Controllers\Api\V1\IncomeController;
use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\StockController;
use App\Http\Controllers\Api\V1\SubCategoryController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\VisitorController;
use App\Http\Controllers\Api\V1\DeveloperSellerController;
use App\Http\Controllers\Api\V1\TransactionSellerController;
use App\Http\Controllers\Api\V1\AttendanceController;


Route::group(['prefix' => 'v1'], function () {

    Route::post('login', [AuthController::class, 'adminLogin']);
    Route::get('config', [SettingController::class, 'configuration']);
    Route::group(['middleware' => ['auth:admin-api']], function () {
               Route::get('seller/indexregions', [VisitorController::class, 'indexregions']);

                Route::get('dataadmin', [AuthController::class, 'dataadmin']);

        /**************** Admin Settings Route Starts Here ***********************/
        Route::post('change-password', [AuthController::class, 'passwordChange']);
        Route::post('update/shop', [SettingController::class, 'updateShop']);
        Route::get('profile', [AuthController::class, 'profile']);
        Route::post('update/user', [AuthController::class, 'update']);
        Route::get('storage', [AuthController::class, 'getAllStorages']);
        /**************** Dashboard Route Starts Here ***********************/
        Route::group(['prefix' => 'dashboard'], function () {
            Route::get('revenue-summary', [DashboardController::class, 'getIndex']);
            Route::get('revenue/filtering', [DashboardController::class, 'getFilter']);
            Route::get('product/limited-stock', [DashboardController::class, 'productLimitedStockList']);
            Route::get('monthly/revenue', [DashboardController::class, 'incomeRevenue']);
            Route::get('quantity/increase', [DashboardController::class, 'quantityIncrease']);
        });
        /**************** Category Route Starts Here ***********************/
        Route::group(['prefix' => 'category'], function () {
            Route::get('list', [CategoryController::class, 'getIndex']);
            Route::post('store', [CategoryController::class, 'postStore']);
            Route::post('update', [CategoryController::class, 'postUpdate']);
            Route::get('delete', [CategoryController::class, 'delete']);
            Route::get('search',  [CategoryController::class, 'getSearch']);
            Route::get('status', [CategoryController::class, 'updateStatus']);
        });
        /**************** Sub Category Route Starts Here ***********************/
        Route::group(['prefix' => 'sub/category'], function () {
            Route::get('list', [SubCategoryController::class, 'getIndex']);
            Route::post('store', [SubCategoryController::class, 'postStore']);
            Route::post('update', [SubCategoryController::class, 'postUpdate']);
            Route::get('delete', [SubCategoryController::class, 'delete']);
            Route::get('search',  [SubCategoryController::class, 'getSearch']);
        });
        /**************** Brand Route Starts Here ******************************/
        Route::group(['prefix' => 'brand'], function () {
            Route::get('list', [BrandController::class, 'getIndex']);
            Route::post('store', [BrandController::class, 'postStore']);
            Route::post('update', [BrandController::class, 'postUpdate']);
            Route::get('delete', [BrandController::class, 'delete']);
            Route::get('search',  [BrandController::class, 'getSearch']);
            Route::get('status',  [BrandController::class, 'updateStatus']);
        });
        /********************* Unit Route Starts Here **************************/
        Route::group(['prefix' => 'unit'], function () {
            Route::get('list', [UnitController::class, 'getIndex']);
            Route::post('store', [UnitController::class, 'postStore']);
            Route::put('update', [UnitController::class, 'postUpdate']);
            Route::get('delete', [UnitController::class, 'delete']);
            Route::get('search',  [UnitController::class, 'getSearch']);
        });
        /********************* Coupon Route Starts Here **************************/
        Route::group(['prefix' => 'coupon'], function () {
            Route::get('list', [CouponController::class, 'getIndex']);
            Route::post('store', [CouponController::class, 'postStore']);
            Route::put('update', [CouponController::class, 'postUpdate']);
            Route::get('delete', [CouponController::class, 'delete']);
            Route::get('status', [CouponController::class, 'updateStatus']);
            Route::get('check', [CouponController::class, 'checkCoupon']);
            Route::get('search', [CouponController::class, 'getSearch']);
        });
        /********************* Customer Route Starts Here **************************/
        Route::group(['prefix' => 'customer'], function () {
            Route::get('list', [CustomerController::class, 'getIndex']);
            Route::get('listcategory', [CustomerController::class, 'indexcategory']);
            Route::post('store', [CustomerController::class, 'postStore']);
            Route::get('details', [CustomerController::class, 'getDetails']);
            Route::put('update', [CustomerController::class, 'postUpdate']);
            Route::get('delete', [CustomerController::class, 'delete']);
            Route::post('add-balance', [CustomerController::class, 'addBalance']);
            Route::post('update/balance', [CustomerController::class, 'update_balance']);
            Route::get('search', [CustomerController::class, 'getSearch']);
            Route::get('filter', [CustomerController::class, 'dateWiseFilter']);
            Route::get('transaction', [CustomerController::class, 'totalTransaction']);
            Route::get('transaction/filter', [CustomerController::class, 'transactionFilter']);

        });
        /********************* Account Route Starts Here **************************/
        Route::group(['prefix' => 'account'], function () {
            Route::get('list', [AccountController::class, 'getIndex']);
            Route::post('save', [AccountController::class, 'accountStore']);
            Route::post('update', [AccountController::class, 'accountUpdate']);
            Route::get('delete', [AccountController::class, 'delete']);
            Route::get('search', [AccountController::class, 'getSearch']);
        });

        /********************* Account Route Starts Here **************************/
        Route::group(['prefix' => 'income'], function () {
            Route::post('store', [IncomeController::class, 'newIncome']);
            Route::get('list', [IncomeController::class, 'index']);
            Route::get('filter', [IncomeController::class, 'getFilter']);
        });
        /********************* Supplier Route Starts Here **************************/
        Route::group(['prefix' => 'supplier'], function () {
            Route::get('list', [SupplierController::class, 'getIndex']);
            Route::post('store', [SupplierController::class, 'postStore']);
            Route::get('details', [SupplierController::class, 'getDetails']);
            Route::put('update', [SupplierController::class, 'postUpdate']);
            Route::get('delete', [SupplierController::class, 'delete']);
            Route::get('search', [SupplierController::class, 'getSearch']);
            Route::get('filter', [SupplierController::class, 'filterByCity']);

            Route::get('transactions', [SupplierController::class, 'transactions']);
            Route::post('payment', [SupplierController::class, 'payment']);
            Route::post('new/purchase', [SupplierController::class, 'newPurchase']);
            Route::get('transactions/date/filter', [SupplierController::class, 'transactionsDateFilter']);

        });
        /********************* Expense Route Starts Here **************************/
        Route::group(['prefix' => 'transaction'], function () {
            Route::get('list', [TransactionController::class, 'getIndex']);
            Route::post('expense', [ExpenseController::class, 'storeExpenses']);
            Route::get('exp/list', [ExpenseController::class, 'getExpense']);
            Route::get('expense/search',  [ExpenseController::class, 'getSearch']);
            Route::post('transfer', [ExpenseController::class, 'storeTransfer']);

            Route::get('transfer-list', [ExpenseController::class, 'transferList']);
            Route::get('filter', [TransactionController::class, 'transactionFilter']);
            Route::get('transfer/accounts', [TransactionController::class, 'transferAccounts']);
            Route::post('fund/transfer', [TransactionController::class, 'fundTransfer']);
            Route::get('transfer/export', [TransactionController::class, 'transferListExport'])->withoutMiddleware('auth:admin-api');
            Route::get('types', [TransactionController::class, 'transactionTypes']);
        });
        /********************* Cart Route Starts Here **************************/
        Route::post('add/to/cart/{id}', [CartController::class, 'addToCart']);
        Route::post('remove/cart', [CartController::class, 'removeCart']);
        /********************* POS Route Starts Here **************************/
        Route::group(['prefix' => 'pos'], function () {
            Route::post('place/order', [PosController::class, 'placeOrder']);
            Route::get('order/list', [PosController::class, 'orderList']);
            Route::get('order/notinstall', [PosController::class, 'orderListnotinstall']);
            Route::get('order/install', [PosController::class, 'orderListinstall']);
            Route::get('invoice', [PosController::class, 'invoiceGenerate']);
            Route::get('order/search', [PosController::class, 'orderGetSearch']);
         Route::post('place/return', [PosController::class, 'processConfirmedReturn']);
            Route::get('customer/orders', [PosController::class, 'customerOrders']);
            Route::get('/productcode', [PosController::class, 'getProductCode']);
            Route::post('place/installment', [PosController::class, 'placeInstallment']);
            Route::get('installment/list', [PosController::class, 'installmentList']);
            Route::get('installment/invoice', [PosController::class, 'installmentInvoiceGenerate']);
        });

        Route::group(['prefix' => 'product'], function () {
            Route::get('list', [PosController::class, 'getSellerProducts']);
            Route::get('seller', [PosController::class, 'getSellerProducts']);
            Route::post('store', [PosController::class, 'storeProduct']);
            Route::post('update', [PosController::class, 'productUpdate']);
            Route::get('search',  [PosController::class, 'getSearch']);
            Route::get('code/search', [ProductController::class, 'codeSearch']);
            Route::get('delete', [PosController::class, 'delete']);
            Route::post('import', [ProductController::class, 'bulk_import_data']);
            Route::get('export', [ProductController::class, 'bulk_export_data'])->withoutMiddleware('auth:admin-api');
            Route::get('download/excel/sample', [ProductController::class, 'downloadExcelSample']);
            Route::get('barcode/generate', [ProductController::class, 'barcode_generate'])->withoutMiddleware('auth:admin-api');
            Route::get('category-wise', [ProductController::class, 'categoryWiseProduct']);
            Route::get('sort', [ProductController::class, 'productSort']);
            Route::get('popular/filter', [ProductController::class, 'propularProductSort']);
            Route::get('supplier/wise', [ProductController::class, 'supplierWiseProduct']);

            Route::post('customer/price', [ProductController::class, 'customerPrice']);
            Route::post('customer/price/change', [ProductController::class, 'changeCustomerPrice']);

            Route::group(['prefix' => 'reservation'], function () {
                Route::get('list/{type}', [PosController::class, 'reservations']);
                            Route::get('invoice/{id}', [PosController::class, 'reservationsInvoice']);
                Route::post('place', [PosController::class, 'reserveProduct']);
            });
        });

        Route::group(['prefix' => 'stocks'], function() {
            Route::get('list', [StockController::class, 'index']);
            Route::post('confirm', [StockController::class, 'confirm']);
            Route::get('history', [StockController::class, 'history']);
        });
                /********************* Result Visitors Route Starts Here **************************/
                            Route::post('storeseller', [VisitorController::class, 'storeseller']);
         Route::group(['prefix' => 'visitor'], function () {
            Route::get('list', [VisitorController::class, 'index']);
            Route::get('result', [VisitorController::class, 'indexresultvisitor']);
            Route::post('store', [VisitorController::class, 'store']);
        });
            Route::get('admin/sellers', [VisitorController::class, 'getAssignedSellers']);
        Route::post('develop-seller/{id}/approve', [VisitorController::class, 'approve']);
            Route::get('develop-seller/pending-type2', [VisitorController::class, 'getType2Pending']);
                        /********************* Result Visitors Route Starts Here **************************/
                            Route::get('/courses', [DeveloperSellerController::class, 'indexcourses']);

                                    Route::get('/salary', [DeveloperSellerController::class, 'indexsalary']);
  Route::prefix('attendance')->group(function () {
    // Route to store an attendance record (check-in or check-out)
    Route::post('/store', [AttendanceController::class, 'storeAttendance']);

    // Route to retrieve all attendance records
    Route::get('/', [AttendanceController::class, 'indexAttendance']);
});

Route::prefix('develop')->group(function () {
    Route::get('/{type}', [DeveloperSellerController::class, 'index']);
    Route::post('/', [DeveloperSellerController::class, 'store']);
                                                            Route::post('/storedevelop', [DeveloperSellerController::class, 'storedevelop']);
});

Route::prefix('transactionseller')->group(function () {
    Route::get('/', [TransactionSellerController::class, 'index']);
    Route::post('/', [TransactionSellerController::class, 'store']);
});
    Route::post('/uploadcertificates', [DeveloperSellerController::class, 'storeImages']);

                /********************* Result Visitors Route Starts Here **************************/

        Route::post('confirm_login', [AuthController::class, 'confirmLogin']);
    });
       Route::post('seller/rating', [VisitorController::class, 'storeRating']);
       Route::get('seller/result/visitor/{id}', [VisitorController::class, 'showResultVisitors']);
       Route::get('seller/indexdocument', [VisitorController::class, 'indexdocument']);

    // GET    /api/admin/sellers

});
 


// Fallback route
Route::fallback(function () {
    return response()->json(['message' => 'Not Found.'], 404);
});
