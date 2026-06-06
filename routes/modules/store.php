<?php

use App\Modules\Store\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

/*
 * Store management — admin only.
 * The default store (slug: 'main') cannot be deleted.
 */
Route::apiResource('stores', StoreController::class)
    ->middleware('role:root');
