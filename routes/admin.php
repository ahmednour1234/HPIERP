<?php

use App\Models\Product;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\StoresController;
use App\Http\Controllers\Admin\StorageController;
use App\Http\Controllers\Admin\TaxController;
use App\Http\Controllers\Admin\StorageSellerController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\SalaryController;
use App\Http\Controllers\Admin\DevelopSellerController;
use App\Http\Controllers\Admin\CourseSellerController;
use App\Http\Controllers\Admin\TransactionSellerController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\POSSessionController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\StockReturnRequestController;
use App\Http\Controllers\Admin\MaterialController;
use App\Http\Controllers\Admin\PurchaseController;
use App\Http\Controllers\Admin\SupplyOrderController;
use App\Http\Controllers\Admin\ProductionOrderController;



Route::get('/api/materials/{material}/units', [PurchaseController::class, 'units']);
Route::get('/api/materials/{material}/batches', [SupplyOrderController::class, 'getBatches'])
     ->name('admin.materials.batches');
Route::get('/api/materials/units/{material}', [SupplyOrderController::class, 'units']);

Route::get('/privacy', function () {
    return view('admin-views.privacy');
})->name('privacy');
Route::group(['namespace'=>'Admin', 'as' => 'admin.', 'prefix'=>'admin'] ,function(){
    Route::group(['namespace' => 'Auth', 'prefix' => 'auth', 'as' => 'auth.'], function(){
        Route::get('login', 'LoginController@login')->name('login');
        Route::post('login', 'LoginController@submit');
        Route::get('logout', 'LoginController@logout')->name('logout');
    });

    Route::group(['middleware' => ['admin']], function(){
Route::get('/', function () {
    return view('admin-views.welcome');
})->name('welcome');
Route::prefix('/factories')->name('factories.')->group(function () {
    Route::get('/', 'App\Http\Controllers\Admin\FactoryController@index')->name('index');
    Route::get('/create', 'App\Http\Controllers\Admin\FactoryController@create')->name('create');
    Route::post('/', 'App\Http\Controllers\Admin\FactoryController@store')->name('store');
    Route::get('/{id}', 'App\Http\Controllers\Admin\FactoryController@show')->name('show');
    Route::get('/{id}/edit', 'App\Http\Controllers\Admin\FactoryController@edit')->name('edit');
    Route::put('/{id}', 'App\Http\Controllers\Admin\FactoryController@update')->name('update');
        Route::post('/update_credit', 'App\Http\Controllers\Admin\FactoryController@update_credit')->name('update_credit');
    Route::post('/update_debit', 'App\Http\Controllers\Admin\FactoryController@update_debit')->name('update_debit');
    Route::patch('/{id}/toggle-active', 'App\Http\Controllers\Admin\FactoryController@toggleActive')->name('toggle-active');
});
Route::post('/supply_orders/issue/{order}', [SupplyOrderController::class, 'issue'])
     ->name('supply_orders.issue');

// راوتات المورد الافتراضية
Route::resource('/supply_orders', SupplyOrderController::class)
     ->only(['index','create','store','show','edit','update']);
// edit/update are not implemented on the controller, so they are not exposed.
Route::resource('/production_orders', ProductionOrderController::class)
     ->only(['index','create','store','show']);

Route::prefix('purchases')->name('purchases.')->group(function(){
    Route::get('/', [PurchaseController::class, 'index'])->name('index');
        Route::post('/', [PurchaseController::class, 'store'])->name('store');
                Route::post('/', [PurchaseController::class, 'store'])->name('store');
                    Route::post('/{purchase}/execute', [PurchaseController::class, 'executeDraft'])
        ->whereNumber('purchase')
        ->name('execute');
    Route::get('/create', [PurchaseController::class, 'create'])->name('create');

    Route::get('/{purchase}', [PurchaseController::class, 'show'])->name('show');
});


Route::prefix('/materials')->name('materials.')->group(function () {
    Route::get('/type/{type}', [MaterialController::class, 'index'])->name('byType');
    Route::get('/create', [MaterialController::class, 'create'])->name('create');
    Route::post('/store', [MaterialController::class, 'store'])->name('store');
    Route::get('/{id}/show', [MaterialController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [MaterialController::class, 'edit'])->name('edit');
    Route::put('/{id}/update', [MaterialController::class, 'update'])->name('update');
});
        Route::get('/dashboard', 'DashboardController@dashboard')->name('dashboard')->middleware('check.dashboard.access');
        Route::post('account-status','DashboardController@account_stats')->name('account-status')->middleware('check.dashboard.access');
        Route::get('settings', 'SystemController@settings')->name('settings');
        Route::post('settings', 'SystemController@settings_update');
        Route::get('settings-password', 'SystemController@settings')->name('settings.password');
        Route::post('settings-password', 'SystemController@settings_password_upospdate')->name('settings-password');
   Route::get('/stores', [StoresController::class, 'index'])->name('stores.index')->middleware('check.store.access');
    Route::get('/stores/create', [StoresController::class, 'create'])->name('stores.create')->middleware('check.store.access');
    Route::post('/stores', [StoresController::class, 'store'])->name('stores.store')->middleware('check.store.access');
Route::get('/stores/{store_id}/edit', [StoresController::class, 'edit'])->name('stores.edit')->middleware('check.store.access');
    Route::post('/stores/{store_id}/update', [StoresController::class, 'update'])->name('stores.update')->middleware('check.store.access');
    Route::delete('/stores/{store_id}', [StoresController::class, 'destroy'])->name('stores.destroy')->middleware('check.store.access');
        
        
        Route::group(['prefix' => 'category', 'as' => 'category.', 'middleware' => 'check.category.access'], function () {
            Route::get('add', 'CategoryController@index')->name('add');
            Route::get('add-sub-category', 'CategoryController@sub_index')->name('add-sub-category');
        Route::get('add-special-category', 'CategoryController@indexspecial')->name('indexspecial');
            //Route::get('add-sub-sub-category', 'CategoryController@sub_sub_index')->name('add-sub-sub-category');
            Route::post('store', 'CategoryController@store')->name('store');
            Route::get('edit/{id}', 'CategoryController@edit')->name('edit');
            Route::get('sub-edit/{id}', 'CategoryController@edit_sub')->name('sub-edit');
            Route::post('update/{id}', 'CategoryController@update')->name('update');
            Route::post('update-sub/{id}', 'CategoryController@update_sub')->name('update-sub');
            Route::post('store', 'CategoryController@store')->name('store');
            Route::get('status/{id}/{status}', 'CategoryController@status')->name('status');
            Route::delete('delete/{id}', 'CategoryController@delete')->name('delete');
            //Route::post('search', 'CategoryController@search')->name('search');
        });

        Route::group(['prefix' => 'brand', 'as' => 'brand.'], function () {
            Route::get('add', 'BrandController@index')->name('add');
            Route::post('store','BrandController@store')->name('store');
            Route::get('edit/{id}', 'BrandController@edit')->name('edit');
            Route::post('update/{id}', 'BrandController@update')->name('update');
            Route::delete('delete/{id}', 'BrandController@delete')->name('delete');
        });
        //unit
        Route::group(['prefix' => 'unit', 'as' => 'unit.', 'middleware' => 'check.unit.access'], function () {
            Route::get('index', 'UnitController@index')->name('index');
            Route::post('store', 'UnitController@store')->name('store');
            Route::get('edit/{id}', 'UnitController@edit')->name('edit');
            Route::post('update/{id}', 'UnitController@update')->name('update');
             Route::delete('delete/{id}', 'UnitController@delete')->name('delete');
        });

        Route::group(['prefix' => 'product', 'as' => 'product.', 'middleware' => 'check.unit.access'], function () {
            Route::get('add', 'ProductController@index')->name('add');
            Route::get('getreportProducts', 'ProductController@getreportProducts')->name('getreportProducts');
            // التصدير يحترم نفس فلاتر الشاشة.
            Route::get('getreportProducts/export', 'ProductController@exportReportProducts')->name('getreportProducts.export');
            Route::post('store', 'ProductController@store')->name('store');
           Route::get('addexpire', 'ProductController@indexexpire')->name('addexpire');
            Route::post('storeexpire', 'ProductController@storeexpire')->name('storeexpire');
            Route::get('list', 'ProductController@list')->name('list');
            Route::get('listreportexpire', 'ProductController@listreportexpire')->name('listreportexpire');
            // التصدير يحترم نفس فلاتر الشاشة.
            Route::get('listreportexpire/export', 'ProductController@exportReportExpire')->name('listreportexpire.export');
            Route::get('listProductsByOrderType', 'ProductController@listProductsByOrderType')->name('listProductsByOrderType');
            Route::get('edit/{id}', 'ProductController@edit')->name('edit');
            Route::post('update/{id}', 'ProductController@update')->name('update');
            Route::delete('delete/{id}', 'ProductController@delete')->name('delete');
            Route::get('barcode-generate/{id}', 'ProductController@barcode_generate')->name('barcode-generate');
            Route::get('barcode/{id}', 'ProductController@barcode')->name('barcode');
            Route::get('bulk-import', 'ProductController@bulk_import_index')->name('bulk-import');
            Route::post('bulk-import', 'ProductController@bulk_import_data');
            Route::get('bulk-export', 'ProductController@bulk_export_data')->name('bulk-export');

            //ajax request
            Route::get('get-categories', 'ProductController@get_categories')->name('get-categories');
            Route::get('remove-image/{id}/{name}', 'ProductController@remove_image')->name('remove-image');
        });

// تقرير ملخص المبيعات الشهري
Route::group(['prefix' => 'reports', 'as' => 'reports.'], function () {
    Route::get('monthly-sales', 'MonthlySalesReportController@index')->name('monthly-sales');
    Route::get('monthly-sales/export', 'MonthlySalesReportController@export')->name('monthly-sales.export');
});

Route::group(['prefix' => 'pos', 'as' => 'pos.', 'middleware' => 'check.pos.access'], function () {
            Route::get('/pos/{type}', 'POSController@index')->name('index');
            Route::get('quick-view', 'POSController@quick_view')->name('quick-view');
            Route::post('variant_price', 'POSController@variant_price')->name('variant_price');
            Route::post('add-to-cart/{type}', 'POSController@addToCart')->name('add-to-cart');
            Route::post('remove-from-cart', 'POSController@removeFromCart')->name('remove-from-cart');
            Route::post('cart-items', 'POSController@cart_items')->name('cart_items');
            Route::post('update-quantity', 'POSController@updateQuantity')->name('updateQuantity');
            Route::post('empty-cart', 'POSController@emptyCart')->name('emptyCart');
            Route::post('tax', 'POSController@update_tax')->name('tax');
            Route::post('discount', 'POSController@update_discount')->name('discount');
            Route::get('customers', 'POSController@get_customers')->name('customers');
            Route::get('customer-balance', 'POSController@customer_balance')->name('customer-balance');
            Route::post('order', 'POSController@place_order')->name('order');
            Route::post('storeplaceorder', 'POSController@storeplaceorder')->name('storeplaceorder');
            Route::get('orders', 'POSController@order_list')->name('orders');
            // Export and collection-reversal honour the same filters as the listing.
            Route::get('orders/export','POSController@order_export')->name('orders.export');

            // أرشيف الفواتير المكتملة. الأرشفة وسم قابل للتراجع لا حذف.
            Route::get('orders/archive', 'InvoiceArchiveController@index')->name('orders.archive');
            Route::get('orders/archive/export', 'InvoiceArchiveController@export')->name('orders.archive.export');
            Route::post('orders/archive/all', 'InvoiceArchiveController@archiveAll')->name('orders.archive.all');
            Route::post('orders/{id}/archive', 'InvoiceArchiveController@archiveOne')->name('orders.archive.one');
            Route::post('orders/{id}/unarchive', 'InvoiceArchiveController@unarchiveOne')->name('orders.unarchive');
            Route::post('orders/reverse-collection/{id}','POSController@order_reverse_collection')->name('orders.reverse');
            // تحصيل فاتورة من الويب، بنفس خدمة التطبيق.
            Route::post('orders/collect/{id}','POSController@collect_payment')->name('orders.collect');
            // مسار معطَّل: POSController::order_details خاصية لا دالة، ولا يشير
            // إليه أي رابط في التطبيق، فكان يعطي 500 لمن يفتحه مباشرة.
            // Route::get('order-details/{id}', 'POSController@order_details')->name('order-details');
            Route::get('refunds', 'POSController@refund_list')->name('refunds');
            // Exports honour the same filters as their listings.
            Route::get('refunds/export','POSController@refund_export')->name('refunds.export');
            Route::get('installments/export','POSController@installment_export')->name('installments.export');
            Route::get('sample', 'POSController@sample_list')->name('sample');
            // التصدير يحترم نفس فلاتر الشاشة.
            Route::get('sample/export', 'POSController@sample_export')->name('sample.export');
            Route::get('donations', 'POSController@donation_list')->name('donations');
            // التصدير يحترم نفس فلاتر الشاشة.
            Route::get('donations/export', 'POSController@donation_export')->name('donations.export');
            Route::get('installments', 'POSController@installment_list')->name('installments');
                        Route::post('installments/reserve/{id}', 'POSController@cancelInstallment')->name('cancelInstallment');
            Route::get('stocks', 'StockController@history')->name('stocks');
            // التصدير يحترم نفس فلاتر الشاشة.
            Route::get('stocks/export', 'POSController@stock_history_export')->name('stocks.export');
            Route::post('reserveProduct', 'POSController@reserveProduct')->name('reserveProduct');
            // Route::get('installment', 'POSController@installment_list')->name('installment');
           Route::get('reservations/{type}/{active}','POSController@reservation_list')->name('reservations');
           // Export honours the same filters as the listing.
           Route::get('reservations/export/{type}/{active}','POSController@reservation_export')->name('reservations.export');
            Route::get('reservations_notification/{type}/{active}', 'POSController@reservation_list_notification')->name('reservation_list_notification');
            Route::get('invoice/{id}', 'POSController@generate_invoice');
                        Route::get('generate_invoice_purchase/{id}', 'POSController@generate_invoice_purchase');
            Route::get('refund/invoice/{id}', 'POSController@refund_generate_invoice');
            Route::get('sample/invoice/{id}', 'POSController@sample_generate_invoice');
            Route::get('donation/invoice/{id}', 'POSController@donation_generate_invoice');
            Route::post('deactivateReservedProductsByReservationId/{id}', 'POSController@deactivateReservedProductsByReservationId')->name('deactivateReservedProductsByReservationId');
            // رد مخزون تم صرفه: يعكس أثر الصرف على المندوب والمخزن معًا.
            Route::post('return-dispatch/{id}', 'StockController@return_dispatch')->name('return-dispatch');
            Route::get('we/reservations/invoice/{id}', 'POSController@generate_reservation_invoice')->name('generate_reservation_invoice');
                        Route::get('we/reservations/invoicea2/{id}', 'POSController@generate_reservation_invoicea2')->name('generate_reservation_invoicea2');

            Route::get('requestsewq/invoice/{id}', 'POSController@generate_reservation_invoice_notification')->name('generate_reservation_invoice_notification');
            Route::get('reservationsnotification/invoice/{id}', 'POSController@generate_reservation_notification_invoice');
            Route::get('installments/invoice/{id}', 'POSController@generate_installments_invoice');
            Route::get('stocks/invoice/{id}', 'POSController@generate_stocks_invoice');
            Route::get('search-products','POSController@search_product')->name('search-products');
            Route::get('search-by-add','POSController@search_by_add_product')->name('search-by-add');

            Route::post('coupon-discount', 'POSController@coupon_discount')->name('coupon-discount');
            Route::post('remove-coupon','POSController@remove_coupon')->name('remove-coupon');
            Route::get('change-cart','POSController@change_cart')->name('change-cart');
            Route::get('new-cart-id','POSController@new_cart_id')->name('new-cart-id');
            Route::get('clear-cart-ids','POSController@clear_cart_ids')->name('clear-cart-ids');
            Route::get('get-cart-ids','POSController@get_cart_ids')->name('get-cart-ids');
        });

        Route::group(['prefix' => 'vehicle-stock', 'as' => 'stock.', 'middleware' => 'check.stock.access'], function () {
            // Export honours the same filters as the listing.
            Route::get('export', 'StockController@export')->name('export');
            // Give part of what a seller is carrying back to the warehouse.
            Route::post('return/{id}', 'StockController@returnToWarehouse')->name('return');
            Route::get('/', 'StockController@index')->name('index');
            Route::get('products/{seller_id}', 'StockController@stock_products')->name('products');
            Route::get('vehicles', 'StockController@vehicles')->name('vehicles');
            Route::get('vehicles/products/{seller_id}', 'StockController@vehicle_products')->name('vehicles.products');
            Route::get('create', 'StockController@create')->name('create');
            Route::post('store', 'StockController@store')->name('store');
            Route::get('edit/{id}', 'StockController@edit')->name('edit');
            Route::post('update/{id}', 'StockController@update')->name('update');
            Route::delete('delete/{id}', 'StockController@delete')->name('delete');
        });

 Route::group(['prefix' => 'visitors', 'as' => 'visitor.'], function () {
            Route::get('/', 'VisitorController@index')->name('index');
                        Route::get('/indexresult', 'VisitorController@indexresult')->name('indexresult');
            // التصدير يحترم نفس فلاتر شاشة الزيارات المنفذة.
            Route::get('/indexresult/export', 'VisitorController@exportResult')->name('indexresult.export');
            Route::get('/showResultVisitors/{seller_id}', 'VisitorController@showResultVisitors')->name('showResultVisitors');
            Route::get('visitor/{seller_id}', 'VisitorController@stock_products')->name('products');
            Route::get('visitor', 'VisitorController@vehicles')->name('vehicles');
            Route::get('visitor/products/{seller_id}', 'VisitorController@vehicle_products')->name('vehicles.products');
            Route::get('create', 'VisitorController@create')->name('create');
            Route::post('store', 'VisitorController@store')->name('store');
            Route::get('edit/{id}', 'VisitorController@edit')->name('edit');
            Route::post('update/{id}', 'VisitorController@update')->name('update');
            Route::delete('delete/{id}', 'VisitorController@delete')->name('delete');
            Route::get('admin/visitors/export','VisitorController@export')->name('export');

        });

        // account
        Route::group(['prefix' => 'account', 'as' => 'account.'], function () {
            Route::get('add','AccountController@add')->name('add');
            Route::post('store', 'AccountController@store')->name('store');
            Route::get('list', 'AccountController@list')->name('list');
            Route::get('edit/{id}', 'AccountController@edit')->name('edit');
            Route::post('update/{id}', 'AccountController@update')->name('update');
            Route::delete('delete/{id}', 'AccountController@delete')->name('delete');

            //expense
            Route::get('add-expense','ExpenseController@add')->name('add-expense');
            Route::post('store-expense', 'ExpenseController@store')->name('store-expense');
            // التصدير يحترم نفس فلاتر الشاشة.
            Route::get('export-expense', 'ExpenseController@export')->name('export-expense');
            // تعديل المصروف وحذفه، مع عكس أثرهما على رصيد الحساب.
            Route::get('edit-expense/{id}', 'ExpenseController@edit')->name('edit-expense');
            Route::post('update-expense/{id}', 'ExpenseController@update')->name('update-expense');
            Route::delete('delete-expense/{id}', 'ExpenseController@delete')->name('delete-expense');

            //income
            Route::get('add-income', 'IncomeController@add')->name('add-income');
            Route::post('store-income', 'IncomeController@store')->name('store-income');
            // التصدير يحترم نفس فلاتر الشاشة.
            Route::get('export-income', 'IncomeController@export')->name('export-income');
            //transfer
            Route::get('add-transfer', 'TransferController@add')->name('add-transfer');
            Route::post('store-transfer', 'TransferController@store')->name('store-transfer');
            //transection
            Route::get('list-transection', 'TransectionController@list')->name('list-transection');
            Route::get('transection-export', 'TransectionController@export')->name('transection-export');

            //payable
            Route::get('add-payable', 'PayableController@add')->name('add-payable');
            Route::post('store-payable', 'PayableController@store')->name('store-payable');
            Route::post('payable-transfer','PayableController@transfer')->name('payable-transfer');

            //receivable
            Route::get('add-receivable', 'ReceivableController@add')->name('add-receivable');
            Route::post('store-receivable', 'ReceivableController@store')->name('store-receivable');
            Route::post('receivable-transfer','ReceivableController@transfer')->name('receivable-transfer');
        });

        //customer
        Route::group(['prefix' => 'customer', 'as' => 'customer.', 'middleware' => 'check.customer.access'], function () {
            Route::get('add','CustomerController@index')->name('add');
                Route::get ('customers/updateexport', [CustomerController::class,'exportupdate'])
         ->name('updateexport');
    Route::post('customers/import', [CustomerController::class,'import'])
         ->name('import');
            Route::post('store', 'CustomerController@store')->name('store');
            Route::get('status/{id}/{status}', 'CustomerController@status')->name('status');
            Route::get('list', 'CustomerController@list')->name('list');
            Route::any('prices/{id}', 'CustomerController@prices')->name('prices');
            Route::any('prices/edit/{customer_id}/{price_id}', 'CustomerController@edit_price')->name('prices.edit');
            Route::delete('prices/delete/{id}', 'CustomerController@delete_price')->name('prices.delete');
            Route::get('view/{id}', 'CustomerController@view')->name('view');
            Route::get('edit/{id}', 'CustomerController@edit')->name('edit');
                        Route::get('editexport', 'CustomerController@editexport')->name('editexport');
            Route::post('update/{id}', 'CustomerController@update')->name('update');
            Route::delete('delete/{id}', 'CustomerController@delete')->name('delete');
            Route::post('update-balance','CustomerController@update_balance')->name('update-balance');
            Route::post('update-credit','CustomerController@update_credit')->name('update-credit');
            Route::get('transaction-list/{id}', 'CustomerController@transaction_list')->name('transaction-list');
            Route::get('/admin/customers/export', 'CustomerController@export')->name('export');

        });
                Route::group(['prefix' => 'shift', 'as' => 'shift.'], function () {
            Route::get('add', 'ShiftController@add')->name('add');
                        Route::get('list', 'ShiftController@index')->name('list');

            Route::post('store', 'ShiftController@store')->name('store');
            Route::get('edit/{id}', 'ShiftController@edit')->name('edit');
            Route::post('update/{id}', 'ShiftController@update')->name('update');
            Route::post('store', 'ShiftController@store')->name('store');
            Route::get('status/{id}/{status}', 'ShiftController@status')->name('status');
            Route::delete('delete/{id}', 'ShiftController@delete')->name('delete');
        });

        //seller
        Route::group(['prefix' => 'seller', 'as' => 'seller.', 'middleware' => 'check.seller.access'], function () {
            Route::get('add','SellerController@index')->name('add');
                        Route::get('getAvailableAdmins','SellerController@getAvailableAdmins')->name('getAvailableAdmins');
            Route::post('store', 'SellerController@store')->name('store');
            Route::get('list', 'SellerController@list')->name('list');
            Route::any('prices/{id}', 'SellerController@prices')->name('prices');
            Route::any('prices/edit/{seller_id}/{price_id}', 'SellerController@edit_price')->name('prices.edit');
            Route::delete('prices/delete/{id}', 'SellerController@delete_price')->name('prices.delete');
            Route::get('edit/{id}', 'SellerController@edit')->name('edit');
            Route::post('update/{id}', 'SellerController@update')->name('update');
            Route::post('update-balance','SellerController@update_balance')->name('update-balance');
            Route::post('update-credit','SellerController@update_credit')->name('update-credit');
            Route::delete('delete/{id}', 'SellerController@delete')->name('delete');
        });
        //seller
        Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => 'check.admin.access'], function () {
            Route::get('add','AdminController@index')->name('add');
            Route::get('showmap','AdminController@showmap')->name('showmap');
            Route::post('store', 'AdminController@store')->name('store');
            Route::get('list', 'AdminController@list')->name('list');
            Route::get('edit/{id}', 'AdminController@edit')->name('edit');
            Route::post('update/{id}', 'AdminController@update')->name('update');
            Route::delete('delete/{id}', 'AdminController@delete')->name('delete');
            
        });

        //supplier
        Route::group(['prefix' => 'supplier', 'as' => 'supplier.', 'middleware' => 'check.supplier.access'], function () {
            Route::get('add','SupplierController@index')->name('add');
            Route::post('store', 'SupplierController@store')->name('store');
            Route::get('list', 'SupplierController@list')->name('list');
            Route::get('status/{id}/{status}', 'SupplierController@status')->name('status');
            Route::get('view/{id}', 'SupplierController@view')->name('view');
            Route::get('edit/{id}', 'SupplierController@edit')->name('edit');
            Route::post('update/{id}', 'SupplierController@update')->name('update');
            Route::delete('delete/{id}', 'SupplierController@delete')->name('delete');
            Route::get('products/{id}', 'SupplierController@product_list')->name('products');
            Route::get('transaction-list/{id}', 'SupplierController@transaction_list')->name('transaction-list');
            Route::post('update-balance','SupplierController@update_balance')->name('update-balance');
            Route::post('update-credit','SupplierController@update_credit')->name('update-credit');
            Route::post('add-new-purchase','SupplierController@add_new_purchase')->name('add-new-purchase');
            Route::post('pay-due','SupplierController@pay_due')->name('pay-due');
        });
        //stock limit
        Route::group(['prefix' => 'stock', 'as' => 'stock.'], function () {
            Route::get('stock-limit', 'StocklimitController@stock_limit')->name('stock-limit');
            Route::post('update-quantity', 'StocklimitController@update_quantity')->name('update-quantity');
        });
        //business settings
        Route::group(['prefix' => 'business-settings', 'as' => 'business-settings.','middleware'=>['actch'],'middleware' => 'check.setting.access'], function () {
            Route::get('shop-setup', 'BusinessSettingsController@shop_index')->name('shop-setup');
            Route::post('update-setup', 'BusinessSettingsController@shop_setup')->name('update-setup');
            Route::get('shortcut-keys', 'BusinessSettingsController@shortcut_key')->name('shortcut-keys');
        });

        //coupon
        Route::group(['prefix' => 'coupon', 'as' => 'coupon.'], function () {
            Route::get('add-new', 'CouponController@add_new')->name('add-new');
            Route::post('store', 'CouponController@store')->name('store');
            Route::get('edit/{id}', 'CouponController@edit')->name('edit');
            Route::post('update/{id}', 'CouponController@update')->name('update');
            Route::get('status/{id}/{status}', 'CouponController@status')->name('status');
            Route::delete('delete/{id}', 'CouponController@delete')->name('delete');
        });
        
        Route::group(['prefix' => 'regions', 'as' => 'regions.', 'middleware' => 'check.seller.access'], function() {
            Route::get('list', 'DashboardController@regionList')->name('list');
            Route::post('store', 'DashboardController@regionStore')->name('store');
            Route::get('edit/{id}', 'DashboardController@regionEdit')->name('edit');
            Route::post('update/{id}', 'DashboardController@regionUpdate')->name('update');
            Route::delete('delete/{id}', 'DashboardController@regionDelete')->name('delete');
        });
    Route::group(['prefix' => 'storages', 'as' => 'storage.', 'middleware' => 'check.storage.access', 'namespace' => 'App\Http\Controllers\Admin'], function() {
    Route::get('list', [StorageController::class, 'index'])->name('list');
    Route::get('create', [StorageController::class, 'create'])->name('create');
    Route::post('store', [StorageController::class, 'store'])->name('store');
    Route::get('edit/{id}', [StorageController::class, 'edit'])->name('edit');
    Route::put('update/{id}', [StorageController::class, 'update'])->name('update');
    Route::delete('delete/{id}', [StorageController::class, 'delete'])->name('delete');
});
    Route::group(['prefix' => 'tax', 'as' => 'taxe.'], function() {
    Route::get('list', [TaxController::class, 'index'])->name('list');
    Route::get('create', [TaxController::class, 'create'])->name('create');
    Route::post('store', [TaxController::class, 'store'])->name('store');
    Route::get('edit/{id}', [TaxController::class, 'edit'])->name('edit');
    Route::put('update/{id}', [TaxController::class, 'update'])->name('update');
    Route::get('status/{id}/{status}',[TaxController::class, 'status'])->name('status');
    Route::delete('delete/{id}', [TaxController::class, 'delete'])->name('delete');
});
  Route::group(['prefix' => 'storagesseller', 'as' => 'storageseller.','middleware' => 'check.storage.access', 'namespace' => 'App\Http\Controllers\Admin'], function() {
    Route::get('list', [StorageSellerController::class, 'index'])->name('list');
    Route::get('create', [StorageSellerController::class, 'create'])->name('create');
    Route::post('store', [StorageSellerController::class, 'store'])->name('store');
    Route::get('edit/{id}', [StorageSellerController::class, 'edit'])->name('edit');
Route::put('admin/storage-sellers/update/{id}', [StorageSellerController::class, 'update'])->name('update');
    Route::delete('delete/{id}', [StorageSellerController::class, 'delete'])->name('delete');
});
Route::get('/admin/notifications/{id}/{type}', [NotificationController::class, 'showItemById'])->name('admin.notifications.show');
Route::get('/admin/notifications', [NotificationController::class, 'listItems'])->name('admin.notifications.listItems');

        //order notification
        Route::middleware('auth:admin')->group(function () {
                    Route::get('/ordernotification', 'OrderNotificationController@index')->name('ordernotification.index')->middleware('check.pos.access');
                    Route::get('/ordernotification/{order_id}', 'OrderNotificationController@show')->name('ordernotification.show')->middleware('check.pos.access');
        Route::post('/ordernotification/store', 'OrderNotificationController@placeOrder')->name('ordernotification.placeOrder')->middleware('check.pos.access');
        Route::get('/admin/orders/search', 'OrderNotificationController@search')->name('orders.search')->middleware('check.pos.access');
                    Route::get('/productsunlike', 'OrderNotificationController@Productunlike')->name('ordernotification.Productunlike')->middleware('check.product.access');


});

Route::prefix('admin/salaries')->group(function () {
    Route::get('/', [SalaryController::class, 'index'])->name('salaries.index');
    Route::get('/create', [SalaryController::class, 'create'])->name('salaries.create');
        Route::get('/createrating', [SalaryController::class, 'createrating'])->name('salaries.createrating');
    Route::post('/', [SalaryController::class, 'store'])->name('salaries.store');
        Route::post('/rating', [SalaryController::class, 'storerating'])->name('salaries.storerating');

    Route::get('/{id}', [SalaryController::class, 'show'])->name('salaries.show');
    Route::get('admin/salary/show/{id}', [SalaryController::class, 'showsalary'])->name('salaries.showsalary');
});
Route::prefix('admin/developsellers')->group(function () {
    Route::get('/{type}', [DevelopSellerController::class, 'index'])->name('developsellers.index');
    Route::get('/create/{type}', [DevelopSellerController::class, 'create'])->name('developsellers.create');
    Route::post('/', [DevelopSellerController::class, 'store'])->name('developsellers.store');
    Route::get('/{id}/edit', [DevelopSellerController::class, 'edit'])->name('developsellers.edit');
    Route::put('/{id}', [DevelopSellerController::class, 'update'])->name('developsellers.update');
        Route::put('status/{id}', [DevelopSellerController::class, 'status'])->name('developsellers.status');
Route::put('admin/developsellers/status/{id}', [DevelopSellerController::class, 'status'])->name('developsellers.status');

        Route::delete('/{id}', [DevelopSellerController::class, 'destroy'])->name('developsellers.destroy');

});
Route::prefix('admin/TransactionSeller')->group(function () {
    Route::get('/', [TransactionSellerController::class, 'index'])->name('TransactionSeller.index');
        Route::put('status/{id}', [TransactionSellerController::class, 'status'])->name('TransactionSeller.status');

}); 
                 Route::resource('coursesellers', CourseSellerController::class)->except(['show']);

    // ملاحظة: مجموعة middleware admin كانت تُغلق هنا، فتخرج مسارات
    // الحضور والوثائق من الحماية ويصل إليها أي زائر بلا تسجيل دخول.
    
Route::prefix('/attendance')->name('attendance.')->group(function () {

    
    // Route to show attendance records, with filtering by date and employee, and summary data
    Route::get('/', [AttendanceController::class, 'showAttendances'])
         ->name('index');
});
       // Documents
        Route::get    ('documents',                 [DocumentController::class, 'index'])->name('documents.index');
        Route::get    ('documents/create',          [DocumentController::class, 'create'])->name('documents.create');
        Route::post   ('documents',                 [DocumentController::class, 'store'])->name('documents.store');
        Route::get    ('documents/{document}',      [DocumentController::class, 'show'])->name('documents.show');
        Route::get    ('documents/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
        Route::put    ('documents/{document}',      [DocumentController::class, 'update'])->name('documents.update');
        Route::delete ('documents/{document}',      [DocumentController::class, 'destroy'])->name('documents.destroy');

        // طلبات إرجاع البضاعة من عربيات المناديب: الاعتماد وحده ينقل الكميات.
        Route::get  ('stock-returns',              [StockReturnRequestController::class, 'index'])->name('stock-returns.index');
        Route::get  ('stock-returns/{id}',         [StockReturnRequestController::class, 'show'])->whereNumber('id')->name('stock-returns.show');
        Route::post ('stock-returns/{id}/approve', [StockReturnRequestController::class, 'approve'])->whereNumber('id')->name('stock-returns.approve');
        Route::post ('stock-returns/{id}/reject',  [StockReturnRequestController::class, 'reject'])->whereNumber('id')->name('stock-returns.reject');
    });   // نهاية مجموعة middleware admin
});
