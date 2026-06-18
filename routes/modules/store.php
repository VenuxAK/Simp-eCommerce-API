<?php

use App\Modules\Store\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:stores.view')->group(function () {
    Route::get('/stores', [StoreController::class, 'index'])->name('stores.index');
    Route::post('/stores', [StoreController::class, 'store'])->name('stores.store');
    Route::get('/stores/{store}', [StoreController::class, 'show'])->name('stores.show');
    Route::put('/stores/{store}', [StoreController::class, 'update'])->name('stores.update');
    Route::delete('/stores/{store}', [StoreController::class, 'destroy'])->name('stores.destroy');
});
