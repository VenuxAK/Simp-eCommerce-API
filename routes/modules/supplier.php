<?php

use App\Modules\Supplier\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

/*
 * Suppliers — staff authenticated.
 * Write operations require admin role.
 */
Route::get('/suppliers', [SupplierController::class, 'index']);
Route::get('/suppliers/{supplier}', [SupplierController::class, 'show']);
Route::post('/suppliers', [SupplierController::class, 'store'])->middleware('role:root,store_admin');
Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->middleware('role:root,store_admin');
Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->middleware('role:root,store_admin');
