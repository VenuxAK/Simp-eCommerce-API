<?php

use App\Modules\Payment\Http\Controllers\PaymentTransactionController;
use App\Modules\Payment\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/payment-transactions', [PaymentTransactionController::class, 'index']);

// ─── Payment Gateway Webhooks ────────────────────────────────
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])->withoutMiddleware(['store', 'cached.auth']);
