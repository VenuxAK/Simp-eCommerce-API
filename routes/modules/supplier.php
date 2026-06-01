<?php

use Illuminate\Support\Facades\Route;

/*
 * Suppliers — staff authenticated.
 * Write operations require admin role.
 */
Route::get('/suppliers', [App\Modules\Supplier\Http\Controllers\SupplierController::class, 'index']);
Route::get('/suppliers/{supplier}', [App\Modules\Supplier\Http\Controllers\SupplierController::class, 'show']);
Route::post('/suppliers', [App\Modules\Supplier\Http\Controllers\SupplierController::class, 'store'])->middleware('role:root,store_admin');
Route::put('/suppliers/{supplier}', [App\Modules\Supplier\Http\Controllers\SupplierController::class, 'update'])->middleware('role:root,store_admin');
Route::delete('/suppliers/{supplier}', [App\Modules\Supplier\Http\Controllers\SupplierController::class, 'destroy'])->middleware('role:root,store_admin');
