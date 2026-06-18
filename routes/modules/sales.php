<?php

use App\Modules\Sales\Http\Controllers\InvoiceController;
use App\Modules\Sales\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/orders', [OrderController::class, 'index']);
Route::get('/orders/{order}', [OrderController::class, 'show']);
Route::post('/orders', [OrderController::class, 'store'])
    ->middleware(['throttle:checkout', 'idempotent']);
Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->middleware('permission:orders.update-status');
Route::post('/orders/{order}/return', [OrderController::class, 'returnItems'])->middleware('permission:orders.return');

Route::get('/invoices', [InvoiceController::class, 'index']);
Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print']);
Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf']);
Route::get('/invoices/{invoice}/receipt', [InvoiceController::class, 'receipt']);
