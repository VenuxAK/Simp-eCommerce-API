<?php

use Illuminate\Support\Facades\Route;

// ─── Staff login ───────────────────────────────────────────────
// Rate-limited to 10 attempts per minute per IP.
Route::post('/auth/login', [App\Modules\Identity\Http\Controllers\AuthController::class, 'login'])
    ->middleware('throttle:10,1');

// ─── Customer registration & login ────────────────────────────
// Stateful middleware enables CSRF-protected session cookie auth.
Route::post('/customer/register', [App\Modules\Customer\Http\Controllers\CustomerAuthController::class, 'register'])
    ->middleware('stateful');

Route::post('/customer/login', [App\Modules\Customer\Http\Controllers\CustomerAuthController::class, 'login'])
    ->middleware(['stateful', 'throttle:10,1']);

// ─── OAuth (Google) ──────────────────────────────────────────
// Redirect to provider's consent screen.
Route::get('/auth/oauth/{provider}/redirect', [App\Modules\Customer\Http\Controllers\OAuthController::class, 'redirect']);
// Callback — provider redirects here with ?code= after consent.
Route::get('/auth/oauth/{provider}/callback', [App\Modules\Customer\Http\Controllers\OAuthController::class, 'callback'])
    ->middleware('stateful');
