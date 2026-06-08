<?php

use App\Modules\Customer\Http\Controllers\CustomerAuthController;
use App\Modules\Customer\Http\Controllers\OAuthController;
use App\Modules\Identity\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// ─── Staff login ───────────────────────────────────────────────
// Rate-limited using granular auth limiter (5 attempts per minute).
Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:auth');

// ─── Customer registration & login ────────────────────────────
// Stateful middleware enables CSRF-protected session cookie auth.
Route::post('/customer/register', [CustomerAuthController::class, 'register'])
    ->middleware(['store', 'stateful']);

Route::post('/customer/login', [CustomerAuthController::class, 'login'])
    ->middleware(['store', 'stateful', 'throttle:auth']);

// ─── OAuth (Google) ──────────────────────────────────────────
// Redirect to provider's consent screen.
Route::get('/auth/oauth/{provider}/redirect', [OAuthController::class, 'redirect']);
// Callback — provider redirects here with ?code= after consent.
Route::get('/auth/oauth/{provider}/callback', [OAuthController::class, 'callback'])
    ->middleware('stateful');
