<?php

use App\Modules\Inventory\Http\Controllers\StockMovementController;
use Illuminate\Support\Facades\Route;

/*
 * Stock movement history — admin only.
 */
Route::get('/stock-movements', [StockMovementController::class, 'index'])->middleware('role:root,store_admin');
