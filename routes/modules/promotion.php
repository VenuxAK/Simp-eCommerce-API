<?php

use App\Modules\Promotion\Http\Controllers\DiscountController;
use Illuminate\Support\Facades\Route;

/*
 * Discounts — staff authenticated.
 * Write operations require admin role.
 */
Route::get('/discounts/active', [DiscountController::class, 'active']);
Route::get('/discounts', [DiscountController::class, 'index']);
Route::get('/discounts/{discount}', [DiscountController::class, 'show']);
Route::post('/discounts', [DiscountController::class, 'store'])->middleware('role:root,store_admin');
Route::put('/discounts/{discount}', [DiscountController::class, 'update'])->middleware('role:root,store_admin');
Route::delete('/discounts/{discount}', [DiscountController::class, 'destroy'])->middleware('role:root,store_admin');
