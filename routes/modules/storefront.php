<?php

use App\Modules\Catalog\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

/*
 * Public storefront endpoints — scoped by X-Store header via ResolveStore middleware.
 * No authentication required. Used by Nuxt SSR storefronts.
 */

Route::get('/products', [StorefrontController::class, 'products']);
Route::get('/products/{slug}', [StorefrontController::class, 'product']);
Route::get('/categories', [StorefrontController::class, 'categories']);
Route::get('/settings', [StorefrontController::class, 'settings']);
