<?php

use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [App\Http\Controllers\Api\AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::get('/auth/me', [App\Http\Controllers\Api\AuthController::class, 'me']);

    Route::apiResource('categories', App\Http\Controllers\Api\CategoryController::class);
    Route::apiResource('products', App\Http\Controllers\Api\ProductController::class);
    Route::apiResource('suppliers', App\Http\Controllers\Api\SupplierController::class);
    Route::post('/products/{product}/image', [App\Http\Controllers\Api\ProductController::class, 'uploadImage']);
    Route::patch('/variants/{variant}/stock', [App\Http\Controllers\Api\ProductVariantController::class, 'updateStock']);
    Route::get('/variants/by-sku/{sku}', [App\Http\Controllers\Api\ProductVariantController::class, 'bySku']);
    Route::post('/variants/{variant}/image', [App\Http\Controllers\Api\ProductVariantController::class, 'uploadImage']);

    Route::apiResource('customers', App\Http\Controllers\Api\CustomerController::class);
    Route::get('/customers/{customer}/orders', [App\Http\Controllers\Api\CustomerController::class, 'orders']);

    Route::apiResource('orders', App\Http\Controllers\Api\OrderController::class);
    Route::patch('/orders/{order}/status', [App\Http\Controllers\Api\OrderController::class, 'updateStatus']);
    Route::post('/orders/{order}/return', [App\Http\Controllers\Api\OrderController::class, 'returnItems']);

    Route::get('/invoices', [App\Http\Controllers\Api\InvoiceController::class, 'index']);
    Route::get('/invoices/{invoice}', [App\Http\Controllers\Api\InvoiceController::class, 'show']);
    Route::get('/invoices/{invoice}/print', [App\Http\Controllers\Api\InvoiceController::class, 'print']);
    Route::get('/invoices/{invoice}/pdf', [App\Http\Controllers\Api\InvoiceController::class, 'pdf']);
    Route::get('/invoices/{invoice}/receipt', [App\Http\Controllers\Api\InvoiceController::class, 'receipt']);

    Route::get('/dashboard/summary', [App\Http\Controllers\Api\DashboardController::class, 'summary']);
    Route::get('/reports/sales', [App\Http\Controllers\Api\ReportController::class, 'sales']);
    Route::get('/reports/best-sellers', [App\Http\Controllers\Api\ReportController::class, 'bestSellers']);

    Route::get('/discounts/active', [App\Http\Controllers\Api\DiscountController::class, 'active']);
    Route::apiResource('discounts', App\Http\Controllers\Api\DiscountController::class);
    Route::get('/stock-movements', [App\Http\Controllers\Api\StockMovementController::class, 'index']);
    Route::get('/cash-sessions', [App\Http\Controllers\Api\CashSessionController::class, 'index']);
    Route::get('/cash-sessions/active', [App\Http\Controllers\Api\CashSessionController::class, 'active']);
    Route::post('/cash-sessions/open', [App\Http\Controllers\Api\CashSessionController::class, 'open']);
    Route::post('/cash-sessions/close', [App\Http\Controllers\Api\CashSessionController::class, 'close']);

    Route::post('/backup', [App\Http\Controllers\Api\BackupController::class, 'create']);
    Route::get('/backups', [App\Http\Controllers\Api\BackupController::class, 'list']);
    Route::get('/backups/{filename}/download', [App\Http\Controllers\Api\BackupController::class, 'download'])->where('filename', '.*');

    Route::get('/products/export/csv', [App\Http\Controllers\Api\ProductController::class, 'exportCsv']);
    Route::post('/products/import/csv', [App\Http\Controllers\Api\ProductController::class, 'importCsv']);
    Route::get('/products/{product}/labels', [App\Http\Controllers\Api\ProductController::class, 'labels']);

    Route::get('/reports/payment-methods', [App\Http\Controllers\Api\ReportController::class, 'paymentMethods']);

    Route::get('/audit-logs', [App\Http\Controllers\Api\AuditLogController::class, 'index'])->middleware('admin');

    Route::get('/profile', [App\Http\Controllers\Api\ProfileController::class, 'show']);
    Route::put('/profile', [App\Http\Controllers\Api\ProfileController::class, 'update']);

    Route::middleware('admin')->group(function () {
        Route::apiResource('users', App\Http\Controllers\Api\UserController::class);
    });
});
