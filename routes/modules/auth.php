<?php

use App\Modules\Customer\Http\Controllers\CustomerAuthController;
use App\Modules\Customer\Http\Controllers\OAuthController;
use App\Modules\Identity\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// ─── Staff login ───────────────────────────────────────────────
// Rate-limited to 10 attempts per minute per IP.
Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1');

// ─── Customer registration & login ────────────────────────────
// Stateful middleware enables CSRF-protected session cookie auth.
Route::post('/customer/register', [CustomerAuthController::class, 'register'])
    ->middleware(['store', 'stateful']);

Route::post('/customer/login', [CustomerAuthController::class, 'login'])
    ->middleware(['store', 'stateful', 'throttle:10,1']);

// ─── OAuth (Google) ──────────────────────────────────────────
// Redirect to provider's consent screen.
Route::get('/auth/oauth/{provider}/redirect', [OAuthController::class, 'redirect']);
// Callback — provider redirects here with ?code= after consent.
Route::get('/auth/oauth/{provider}/callback', [OAuthController::class, 'callback'])
    ->middleware('stateful');
