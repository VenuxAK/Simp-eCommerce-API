<?php

use App\Modules\Supplier\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::get('/suppliers', [SupplierController::class, 'index']);
Route::get('/suppliers/{supplier}', [SupplierController::class, 'show']);
Route::post('/suppliers', [SupplierController::class, 'store'])->middleware('permission:suppliers.create');
Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->middleware('permission:suppliers.update');
Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->middleware('permission:suppliers.delete');
