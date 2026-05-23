<?php

use Illuminate\Support\Facades\Route;

/*
 * Dashboard and analytics — staff authenticated.
 */
Route::get('/dashboard/summary', [App\Modules\Report\Http\Controllers\DashboardController::class, 'summary']);
Route::get('/reports/sales', [App\Modules\Report\Http\Controllers\ReportController::class, 'sales']);
Route::get('/reports/best-sellers', [App\Modules\Report\Http\Controllers\ReportController::class, 'bestSellers']);
Route::get('/reports/payment-methods', [App\Modules\Report\Http\Controllers\ReportController::class, 'paymentMethods']);
