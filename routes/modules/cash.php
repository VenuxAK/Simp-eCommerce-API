<?php

use App\Modules\Cash\Http\Controllers\CashSessionController;
use Illuminate\Support\Facades\Route;

/*
 * Cash drawer sessions — staff authenticated.
 * All staff with sales access can manage their sessions.
 */
Route::middleware('role:root,store_owner,store_manager,sales_staff')->group(function () {
    Route::get('/cash-sessions', [CashSessionController::class, 'index']);
    Route::get('/cash-sessions/active', [CashSessionController::class, 'active']);
    Route::post('/cash-sessions/open', [CashSessionController::class, 'open']);
    Route::post('/cash-sessions/close', [CashSessionController::class, 'close']);
});
