<?php

use App\Modules\Cash\Http\Controllers\CashSessionController;
use Illuminate\Support\Facades\Route;

Route::get('/cash-sessions', [CashSessionController::class, 'index'])->middleware('permission:cash-sessions.view');
Route::get('/cash-sessions/active', [CashSessionController::class, 'active'])->middleware('permission:cash-sessions.view');
Route::post('/cash-sessions/open', [CashSessionController::class, 'open'])->middleware('permission:cash-sessions.open');
Route::post('/cash-sessions/close', [CashSessionController::class, 'close'])->middleware('permission:cash-sessions.close');
