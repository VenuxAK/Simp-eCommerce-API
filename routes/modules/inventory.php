<?php

use App\Modules\Inventory\Http\Controllers\StockMovementController;
use Illuminate\Support\Facades\Route;

/*
 * Stock movement history — users with inventory or higher access.
 */

Route::get('/stock-movements', [StockMovementController::class, 'index'])->middleware('role:root,store_owner,store_manager,inventory_staff');
