<?php

use Illuminate\Support\Facades\Route;

// ─── Staff login ───────────────────────────────────────────────
// Rate-limited to 10 attempts per minute per IP.
Route::post('/auth/login', [App\Modules\Identity\Http\Controllers\AuthController::class, 'login'])
    ->middleware(['stateful', 'throttle:10,1']);

// ─── Customer registration & login ────────────────────────────
// Register is unauthenticated; login is rate-limited.
Route::post('/customer/register', [App\Modules\Customer\Http\Controllers\CustomerAuthController::class, 'register'])
    ->middleware('stateful');

Route::post('/customer/login', [App\Modules\Customer\Http\Controllers\CustomerAuthController::class, 'login'])
    ->middleware(['stateful', 'throttle:10,1']);
