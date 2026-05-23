<?php

use Illuminate\Support\Facades\Route;

/*
 * Store management — admin only.
 * The default store (slug: 'main') cannot be deleted.
 */
Route::apiResource('stores', App\Modules\Store\Http\Controllers\StoreController::class);
