<?php

use Illuminate\Support\Facades\Route;

/*
 * Product catalog — staff authenticated.
 * Read operations are available to all staff;
 * write operations (create, update, delete, import) require admin.
 */

Route::get('/products', [App\Modules\Catalog\Http\Controllers\ProductController::class, 'index']);
Route::get('/products/{product}', [App\Modules\Catalog\Http\Controllers\ProductController::class, 'show']);
Route::get('/products/export/csv', [App\Modules\Catalog\Http\Controllers\ProductController::class, 'exportCsv']);
Route::get('/products/{product}/labels', [App\Modules\Catalog\Http\Controllers\ProductController::class, 'labels']);
Route::post('/products', [App\Modules\Catalog\Http\Controllers\ProductController::class, 'store'])->middleware('role:root,store_admin');
Route::put('/products/{product}', [App\Modules\Catalog\Http\Controllers\ProductController::class, 'update'])->middleware('role:root,store_admin');
Route::delete('/products/{product}', [App\Modules\Catalog\Http\Controllers\ProductController::class, 'destroy'])->middleware('role:root,store_admin');
Route::post('/products/import/csv', [App\Modules\Catalog\Http\Controllers\ProductController::class, 'importCsv'])->middleware('role:root,store_admin');
Route::post('/products/{product}/image', [App\Modules\Catalog\Http\Controllers\ProductController::class, 'uploadImage']);

Route::patch('/variants/{variant}/stock', [App\Modules\Catalog\Http\Controllers\ProductVariantController::class, 'updateStock']);
Route::get('/variants/by-sku/{sku}', [App\Modules\Catalog\Http\Controllers\ProductVariantController::class, 'bySku']);
Route::post('/variants/{variant}/image', [App\Modules\Catalog\Http\Controllers\ProductVariantController::class, 'uploadImage']);

Route::get('/categories', [App\Modules\Catalog\Http\Controllers\CategoryController::class, 'index']);
Route::get('/categories/{category}', [App\Modules\Catalog\Http\Controllers\CategoryController::class, 'show']);
Route::post('/categories', [App\Modules\Catalog\Http\Controllers\CategoryController::class, 'store'])->middleware('role:root,store_admin');
Route::put('/categories/{category}', [App\Modules\Catalog\Http\Controllers\CategoryController::class, 'update'])->middleware('role:root,store_admin');
Route::delete('/categories/{category}', [App\Modules\Catalog\Http\Controllers\CategoryController::class, 'destroy'])->middleware('role:root,store_admin');
