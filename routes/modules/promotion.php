<?php

use App\Modules\Promotion\Http\Controllers\DiscountController;
use Illuminate\Support\Facades\Route;

Route::get('/discounts/active', [DiscountController::class, 'active']);
Route::get('/discounts', [DiscountController::class, 'index']);
Route::get('/discounts/{discount}', [DiscountController::class, 'show']);
Route::post('/discounts', [DiscountController::class, 'store'])->middleware('permission:discounts.create');
Route::put('/discounts/{discount}', [DiscountController::class, 'update'])->middleware('permission:discounts.update');
Route::delete('/discounts/{discount}', [DiscountController::class, 'destroy'])->middleware('permission:discounts.delete');
