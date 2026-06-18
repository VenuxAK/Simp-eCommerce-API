<?php

use App\Modules\Identity\Http\Controllers\AuthController;
use App\Modules\Identity\Http\Controllers\ProfileController;
use App\Modules\Identity\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/logout', [AuthController::class, 'logout']);
Route::get('/auth/me', [AuthController::class, 'me']);

Route::get('/profile', [ProfileController::class, 'show']);
Route::put('/profile', [ProfileController::class, 'update']);

Route::middleware('permission:users.manage-store')->group(function () {
    Route::apiResource('users', UserController::class);
});
