<?php

use Illuminate\Support\Facades\Route;

// ─── Staff session management ──────────────────────────────────
Route::post('/auth/logout', [App\Modules\Identity\Http\Controllers\AuthController::class, 'logout']);
Route::get('/auth/me', [App\Modules\Identity\Http\Controllers\AuthController::class, 'me']);

// ─── Staff profile ─────────────────────────────────────────────
Route::get('/profile', [App\Modules\Identity\Http\Controllers\ProfileController::class, 'show']);
Route::put('/profile', [App\Modules\Identity\Http\Controllers\ProfileController::class, 'update']);

// ─── User management (admin only) ──────────────────────────────
Route::middleware('admin')->group(function () {
    Route::apiResource('users', App\Modules\Identity\Http\Controllers\UserController::class);
});
