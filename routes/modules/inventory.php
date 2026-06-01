<?php

use Illuminate\Support\Facades\Route;

/*
 * Stock movement history — admin only.
 */
Route::get('/stock-movements', [App\Modules\Inventory\Http\Controllers\StockMovementController::class, 'index'])->middleware('role:root,store_admin');
