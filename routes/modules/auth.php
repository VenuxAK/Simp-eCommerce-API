<?php

use App\Modules\Customer\Http\Controllers\CustomerAuthController;
use App\Modules\Customer\Http\Controllers\CustomerForgotPasswordController;
use App\Modules\Customer\Http\Controllers\OAuthController;
use App\Modules\Identity\Http\Controllers\AuthController;
use App\Modules\Identity\Http\Controllers\ForgotPasswordController;
use App\Modules\Payment\Http\Controllers\MMPayWebhookController;
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

// ─── Staff password reset ─────────────────────────────────────
Route::post('/auth/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])
    ->middleware('throttle:auth');

Route::post('/auth/reset-password', [ForgotPasswordController::class, 'reset'])
    ->middleware('throttle:auth');

// ─── Customer password reset ──────────────────────────────────
Route::post('/customer/forgot-password', [CustomerForgotPasswordController::class, 'sendResetLink'])
    ->middleware(['store', 'stateful', 'throttle:auth']);

Route::post('/customer/reset-password', [CustomerForgotPasswordController::class, 'reset'])
    ->middleware(['store', 'stateful', 'throttle:auth']);

// ─── OAuth (Google) ──────────────────────────────────────────
// Redirect to provider's consent screen.
Route::get('/auth/oauth/{provider}/redirect', [OAuthController::class, 'redirect']);
// Callback — provider redirects here with ?code= after consent.
Route::get('/auth/oauth/{provider}/callback', [OAuthController::class, 'callback'])
    ->middleware('web');

// ─── Payment Gateway Webhooks ────────────────────────────────
Route::post('/mmpay/webhook', [MMPayWebhookController::class, 'handle']);
