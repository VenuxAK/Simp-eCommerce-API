<?php

use App\Modules\Inventory\Http\Controllers\StockMovementController;
use Illuminate\Support\Facades\Route;

Route::get('/stock-movements', [StockMovementController::class, 'index'])->middleware('permission:stock-movements.view');
