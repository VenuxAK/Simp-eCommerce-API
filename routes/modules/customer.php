<?php

use App\Modules\Customer\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

/*
 * Customer CRM — staff authenticated.
 * Create and read are available to all staff;
 * update and delete require store manager level or higher.
 */

Route::get('/customers', [CustomerController::class, 'index']);
Route::get('/customers/{customer}', [CustomerController::class, 'show']);
Route::get('/customers/{customer}/orders', [CustomerController::class, 'orders']);
Route::post('/customers', [CustomerController::class, 'store']);

Route::middleware('role:root,store_owner,store_manager')->group(function () {
    Route::put('/customers/{customer}', [CustomerController::class, 'update']);
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy']);
});
