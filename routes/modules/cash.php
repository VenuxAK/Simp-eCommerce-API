<?php

use Illuminate\Support\Facades\Route;

/*
 * Cash drawer sessions — staff authenticated.
 */
Route::get('/cash-sessions', [App\Modules\Cash\Http\Controllers\CashSessionController::class, 'index']);
Route::get('/cash-sessions/active', [App\Modules\Cash\Http\Controllers\CashSessionController::class, 'active']);
Route::post('/cash-sessions/open', [App\Modules\Cash\Http\Controllers\CashSessionController::class, 'open']);
Route::post('/cash-sessions/close', [App\Modules\Cash\Http\Controllers\CashSessionController::class, 'close']);
