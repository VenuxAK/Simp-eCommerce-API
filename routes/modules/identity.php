<?php

use App\Modules\Identity\Http\Controllers\AuthController;
use App\Modules\Identity\Http\Controllers\ProfileController;
use App\Modules\Identity\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ─── Staff session management ──────────────────────────────────
Route::post('/auth/logout', [AuthController::class, 'logout']);
Route::get('/auth/me', [AuthController::class, 'me']);

// ─── Staff profile ─────────────────────────────────────────────
Route::get('/profile', [ProfileController::class, 'show']);
Route::put('/profile', [ProfileController::class, 'update']);

// ─── User management (admin only) ──────────────────────────────
Route::middleware('role:root')->group(function () {
    Route::apiResource('users', UserController::class);
});
