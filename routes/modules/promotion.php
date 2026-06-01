<?php

use Illuminate\Support\Facades\Route;

/*
 * Discounts — staff authenticated.
 * Write operations require admin role.
 */
Route::get('/discounts/active', [App\Modules\Promotion\Http\Controllers\DiscountController::class, 'active']);
Route::get('/discounts', [App\Modules\Promotion\Http\Controllers\DiscountController::class, 'index']);
Route::get('/discounts/{discount}', [App\Modules\Promotion\Http\Controllers\DiscountController::class, 'show']);
Route::post('/discounts', [App\Modules\Promotion\Http\Controllers\DiscountController::class, 'store'])->middleware('role:root,store_admin');
Route::put('/discounts/{discount}', [App\Modules\Promotion\Http\Controllers\DiscountController::class, 'update'])->middleware('role:root,store_admin');
Route::delete('/discounts/{discount}', [App\Modules\Promotion\Http\Controllers\DiscountController::class, 'destroy'])->middleware('role:root,store_admin');
