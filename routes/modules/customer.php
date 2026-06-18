<?php

use App\Modules\Customer\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

Route::get('/customers', [CustomerController::class, 'index']);
Route::get('/customers/{customer}', [CustomerController::class, 'show']);
Route::get('/customers/{customer}/orders', [CustomerController::class, 'orders']);
Route::post('/customers', [CustomerController::class, 'store']);
Route::put('/customers/{customer}', [CustomerController::class, 'update'])->middleware('permission:customers.update');
Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->middleware('permission:customers.delete');
