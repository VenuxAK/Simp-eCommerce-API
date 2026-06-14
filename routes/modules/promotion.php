<?php

use App\Modules\Promotion\Http\Controllers\DiscountController;
use Illuminate\Support\Facades\Route;

/*
 * Discounts — staff authenticated.
 * Write operations require store manager level or higher.
 */
Route::get('/discounts/active', [DiscountController::class, 'active']);
Route::get('/discounts', [DiscountController::class, 'index']);
Route::get('/discounts/{discount}', [DiscountController::class, 'show']);

Route::middleware('role:root,store_owner,store_manager')->group(function () {
    Route::post('/discounts', [DiscountController::class, 'store']);
    Route::put('/discounts/{discount}', [DiscountController::class, 'update']);
    Route::delete('/discounts/{discount}', [DiscountController::class, 'destroy']);
});
