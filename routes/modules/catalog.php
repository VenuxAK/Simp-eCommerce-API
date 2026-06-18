<?php

use App\Modules\Catalog\Http\Controllers\BrandController;
use App\Modules\Catalog\Http\Controllers\CategoryController;
use App\Modules\Catalog\Http\Controllers\ProductController;
use App\Modules\Catalog\Http\Controllers\ProductVariantController;
use Illuminate\Support\Facades\Route;

/*
 * Product catalog — permission-gated via Spatie RBAC.
 */
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::get('/products/export/csv', [ProductController::class, 'exportCsv']);
Route::get('/products/{product}/labels', [ProductController::class, 'labels']);
Route::post('/products', [ProductController::class, 'store'])->middleware('permission:products.create');
Route::put('/products/{product}', [ProductController::class, 'update'])->middleware('permission:products.update');
Route::delete('/products/{product}', [ProductController::class, 'destroy'])->middleware('permission:products.delete');
Route::post('/products/import/csv', [ProductController::class, 'importCsv'])->middleware('permission:products.import');
Route::post('/products/{product}/image', [ProductController::class, 'uploadImage']);

Route::get('/variants/by-sku/{sku}', [ProductVariantController::class, 'bySku']);
Route::patch('/variants/{variant}/stock', [ProductVariantController::class, 'updateStock'])->middleware('permission:variants.update-stock');
Route::post('/variants/{variant}/image', [ProductVariantController::class, 'uploadImage']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);
Route::post('/categories', [CategoryController::class, 'store'])->middleware('permission:categories.create');
Route::put('/categories/{category}', [CategoryController::class, 'update'])->middleware('permission:categories.update');
Route::post('/categories/{category}/image', [CategoryController::class, 'uploadImage'])->middleware('permission:categories.update');
Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->middleware('permission:categories.delete');

Route::get('/brands', [BrandController::class, 'index']);
Route::get('/brands/{brand}', [BrandController::class, 'show']);
Route::post('/brands', [BrandController::class, 'store'])->middleware('permission:brands.create');
Route::put('/brands/{brand}', [BrandController::class, 'update'])->middleware('permission:brands.update');
Route::post('/brands/{brand}/logo', [BrandController::class, 'uploadLogo'])->middleware('permission:brands.update');
Route::delete('/brands/{brand}', [BrandController::class, 'destroy'])->middleware('permission:brands.delete');
