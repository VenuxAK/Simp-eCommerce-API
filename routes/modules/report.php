<?php

use App\Modules\Report\Http\Controllers\DashboardController;
use App\Modules\Report\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

/*
 * Dashboard and analytics — staff authenticated.
 */
Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
Route::get('/reports/sales', [ReportController::class, 'sales']);
Route::get('/reports/best-sellers', [ReportController::class, 'bestSellers']);
Route::get('/reports/payment-methods', [ReportController::class, 'paymentMethods']);
