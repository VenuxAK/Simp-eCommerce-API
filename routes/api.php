<?php

use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [App\Modules\Identity\Http\Controllers\AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::post('/auth/logout', [App\Modules\Identity\Http\Controllers\AuthController::class, 'logout']);
    Route::get('/auth/me', [App\Modules\Identity\Http\Controllers\AuthController::class, 'me']);

    Route::get('/profile', [App\Modules\Identity\Http\Controllers\ProfileController::class, 'show']);
    Route::put('/profile', [App\Modules\Identity\Http\Controllers\ProfileController::class, 'update']);

    Route::get('/dashboard/summary', [App\Modules\Report\Http\Controllers\DashboardController::class, 'summary']);

    Route::get('/customers', [App\Modules\Customer\Http\Controllers\CustomerController::class, 'index']);
    Route::get('/customers/{customer}', [App\Modules\Customer\Http\Controllers\CustomerController::class, 'show']);
    Route::get('/customers/{customer}/orders', [App\Modules\Customer\Http\Controllers\CustomerController::class, 'orders']);
    Route::post('/customers', [App\Modules\Customer\Http\Controllers\CustomerController::class, 'store']);
    Route::put('/customers/{customer}', [App\Modules\Customer\Http\Controllers\CustomerController::class, 'update'])->middleware('admin');
    Route::delete('/customers/{customer}', [App\Modules\Customer\Http\Controllers\CustomerController::class, 'destroy'])->middleware('admin');

    Route::get('/products', [App\Modules\Catalog\Http\Controllers\ProductController::class, 'index']);
    Route::get('/products/{product}', [App\Modules\Catalog\Http\Controllers\ProductController::class, 'show']);
    Route::get('/products/export/csv', [App\Modules\Catalog\Http\Controllers\ProductController::class, 'exportCsv']);
    Route::get('/products/{product}/labels', [App\Modules\Catalog\Http\Controllers\ProductController::class, 'labels']);
    Route::post('/products', [App\Modules\Catalog\Http\Controllers\ProductController::class, 'store'])->middleware('admin');
    Route::put('/products/{product}', [App\Modules\Catalog\Http\Controllers\ProductController::class, 'update'])->middleware('admin');
    Route::delete('/products/{product}', [App\Modules\Catalog\Http\Controllers\ProductController::class, 'destroy'])->middleware('admin');
    Route::post('/products/import/csv', [App\Modules\Catalog\Http\Controllers\ProductController::class, 'importCsv'])->middleware('admin');
    Route::post('/products/{product}/image', [App\Modules\Catalog\Http\Controllers\ProductController::class, 'uploadImage']);
    Route::patch('/variants/{variant}/stock', [App\Modules\Catalog\Http\Controllers\ProductVariantController::class, 'updateStock']);
    Route::get('/variants/by-sku/{sku}', [App\Modules\Catalog\Http\Controllers\ProductVariantController::class, 'bySku']);
    Route::post('/variants/{variant}/image', [App\Modules\Catalog\Http\Controllers\ProductVariantController::class, 'uploadImage']);

    Route::get('/orders', [App\Modules\Sales\Http\Controllers\OrderController::class, 'index']);
    Route::get('/orders/{order}', [App\Modules\Sales\Http\Controllers\OrderController::class, 'show']);
    Route::post('/orders', [App\Modules\Sales\Http\Controllers\OrderController::class, 'store']);
    Route::patch('/orders/{order}/status', [App\Modules\Sales\Http\Controllers\OrderController::class, 'updateStatus'])->middleware('admin');
    Route::post('/orders/{order}/return', [App\Modules\Sales\Http\Controllers\OrderController::class, 'returnItems'])->middleware('admin');

    Route::get('/invoices', [App\Modules\Sales\Http\Controllers\InvoiceController::class, 'index']);
    Route::get('/invoices/{invoice}', [App\Modules\Sales\Http\Controllers\InvoiceController::class, 'show']);
    Route::get('/invoices/{invoice}/print', [App\Modules\Sales\Http\Controllers\InvoiceController::class, 'print']);
    Route::get('/invoices/{invoice}/pdf', [App\Modules\Sales\Http\Controllers\InvoiceController::class, 'pdf']);
    Route::get('/invoices/{invoice}/receipt', [App\Modules\Sales\Http\Controllers\InvoiceController::class, 'receipt']);

    Route::get('/reports/sales', [App\Modules\Report\Http\Controllers\ReportController::class, 'sales']);
    Route::get('/reports/best-sellers', [App\Modules\Report\Http\Controllers\ReportController::class, 'bestSellers']);
    Route::get('/reports/payment-methods', [App\Modules\Report\Http\Controllers\ReportController::class, 'paymentMethods']);

    Route::get('/discounts/active', [App\Modules\Promotion\Http\Controllers\DiscountController::class, 'active']);
    Route::get('/discounts', [App\Modules\Promotion\Http\Controllers\DiscountController::class, 'index']);
    Route::get('/discounts/{discount}', [App\Modules\Promotion\Http\Controllers\DiscountController::class, 'show']);
    Route::post('/discounts', [App\Modules\Promotion\Http\Controllers\DiscountController::class, 'store'])->middleware('admin');
    Route::put('/discounts/{discount}', [App\Modules\Promotion\Http\Controllers\DiscountController::class, 'update'])->middleware('admin');
    Route::delete('/discounts/{discount}', [App\Modules\Promotion\Http\Controllers\DiscountController::class, 'destroy'])->middleware('admin');

    Route::get('/categories', [App\Modules\Catalog\Http\Controllers\CategoryController::class, 'index']);
    Route::get('/categories/{category}', [App\Modules\Catalog\Http\Controllers\CategoryController::class, 'show']);
    Route::post('/categories', [App\Modules\Catalog\Http\Controllers\CategoryController::class, 'store'])->middleware('admin');
    Route::put('/categories/{category}', [App\Modules\Catalog\Http\Controllers\CategoryController::class, 'update'])->middleware('admin');
    Route::delete('/categories/{category}', [App\Modules\Catalog\Http\Controllers\CategoryController::class, 'destroy'])->middleware('admin');

    Route::get('/suppliers', [App\Modules\Supplier\Http\Controllers\SupplierController::class, 'index']);
    Route::get('/suppliers/{supplier}', [App\Modules\Supplier\Http\Controllers\SupplierController::class, 'show']);
    Route::post('/suppliers', [App\Modules\Supplier\Http\Controllers\SupplierController::class, 'store'])->middleware('admin');
    Route::put('/suppliers/{supplier}', [App\Modules\Supplier\Http\Controllers\SupplierController::class, 'update'])->middleware('admin');
    Route::delete('/suppliers/{supplier}', [App\Modules\Supplier\Http\Controllers\SupplierController::class, 'destroy'])->middleware('admin');

    Route::get('/cash-sessions', [App\Modules\Cash\Http\Controllers\CashSessionController::class, 'index']);
    Route::get('/cash-sessions/active', [App\Modules\Cash\Http\Controllers\CashSessionController::class, 'active']);
    Route::post('/cash-sessions/open', [App\Modules\Cash\Http\Controllers\CashSessionController::class, 'open']);
    Route::post('/cash-sessions/close', [App\Modules\Cash\Http\Controllers\CashSessionController::class, 'close']);

    Route::get('/stock-movements', [App\Modules\Inventory\Http\Controllers\StockMovementController::class, 'index'])->middleware('admin');

    Route::post('/backups', [App\Modules\System\Http\Controllers\BackupController::class, 'create'])->middleware('admin');
    Route::get('/backups', [App\Modules\System\Http\Controllers\BackupController::class, 'list'])->middleware('admin');
    Route::get('/backups/{filename}/download', [App\Modules\System\Http\Controllers\BackupController::class, 'download'])->middleware('admin')->where('filename', '[A-Za-z0-9._-]+');

    Route::get('/audit-logs', [App\Modules\Audit\Http\Controllers\AuditLogController::class, 'index'])->middleware('admin');

    Route::middleware('admin')->group(function () {
        Route::apiResource('users', App\Modules\Identity\Http\Controllers\UserController::class);
        Route::apiResource('stores', App\Modules\Store\Http\Controllers\StoreController::class);
    });
});
