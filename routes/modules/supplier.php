<?php

use App\Modules\Supplier\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

/*
 * Suppliers — staff authenticated.
 * Write operations require inventory access or higher.
 */

Route::get('/suppliers', [SupplierController::class, 'index']);
Route::get('/suppliers/{supplier}', [SupplierController::class, 'show']);

Route::middleware('role:root,store_owner,store_manager,inventory_staff')->group((function () {
    Route::post('/suppliers', [SupplierController::class, 'store']);
    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update']);
    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy']);
}));
