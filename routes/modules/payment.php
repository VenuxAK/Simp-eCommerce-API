<?php

use App\Modules\Payment\Http\Controllers\PaymentTransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/payment-transactions', [PaymentTransactionController::class, 'index']);
