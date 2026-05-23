<?php

use Illuminate\Support\Facades\Route;

/*
 * Customer CRM — staff authenticated.
 * Create and read are available to all staff;
 * update and delete are admin-only.
 */

Route::get('/customers', [App\Modules\Customer\Http\Controllers\CustomerController::class, 'index']);
Route::get('/customers/{customer}', [App\Modules\Customer\Http\Controllers\CustomerController::class, 'show']);
Route::get('/customers/{customer}/orders', [App\Modules\Customer\Http\Controllers\CustomerController::class, 'orders']);
Route::post('/customers', [App\Modules\Customer\Http\Controllers\CustomerController::class, 'store']);
Route::put('/customers/{customer}', [App\Modules\Customer\Http\Controllers\CustomerController::class, 'update'])->middleware('admin');
Route::delete('/customers/{customer}', [App\Modules\Customer\Http\Controllers\CustomerController::class, 'destroy'])->middleware('admin');
