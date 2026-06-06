<?php

use App\Modules\Catalog\Http\Controllers\CategoryController;
use App\Modules\Catalog\Http\Controllers\ProductController;
use App\Modules\Catalog\Http\Controllers\ProductVariantController;
use Illuminate\Support\Facades\Route;

/*
 * Product catalog — staff authenticated.
 * Read operations are available to all staff;
 * write operations (create, update, delete, import) require admin.
 */

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::get('/products/export/csv', [ProductController::class, 'exportCsv']);
Route::get('/products/{product}/labels', [ProductController::class, 'labels']);
Route::post('/products', [ProductController::class, 'store'])->middleware('role:root,store_admin');
Route::put('/products/{product}', [ProductController::class, 'update'])->middleware('role:root,store_admin');
Route::delete('/products/{product}', [ProductController::class, 'destroy'])->middleware('role:root,store_admin');
Route::post('/products/import/csv', [ProductController::class, 'importCsv'])->middleware('role:root,store_admin');
Route::post('/products/{product}/image', [ProductController::class, 'uploadImage']);

Route::patch('/variants/{variant}/stock', [ProductVariantController::class, 'updateStock']);
Route::get('/variants/by-sku/{sku}', [ProductVariantController::class, 'bySku']);
Route::post('/variants/{variant}/image', [ProductVariantController::class, 'uploadImage']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);
Route::post('/categories', [CategoryController::class, 'store'])->middleware('role:root,store_admin');
Route::put('/categories/{category}', [CategoryController::class, 'update'])->middleware('role:root,store_admin');
Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->middleware('role:root,store_admin');
