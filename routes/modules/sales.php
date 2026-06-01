<?php

use Illuminate\Support\Facades\Route;

/*
 * Orders and invoices — staff authenticated.
 * Status transitions and returns are admin-only.
 */

Route::get('/orders', [App\Modules\Sales\Http\Controllers\OrderController::class, 'index']);
Route::get('/orders/{order}', [App\Modules\Sales\Http\Controllers\OrderController::class, 'show']);
Route::post('/orders', [App\Modules\Sales\Http\Controllers\OrderController::class, 'store']);
Route::patch('/orders/{order}/status', [App\Modules\Sales\Http\Controllers\OrderController::class, 'updateStatus'])->middleware('role:root,store_admin');
Route::post('/orders/{order}/return', [App\Modules\Sales\Http\Controllers\OrderController::class, 'returnItems'])->middleware('role:root,store_admin');

Route::get('/invoices', [App\Modules\Sales\Http\Controllers\InvoiceController::class, 'index']);
Route::get('/invoices/{invoice}', [App\Modules\Sales\Http\Controllers\InvoiceController::class, 'show']);
Route::get('/invoices/{invoice}/print', [App\Modules\Sales\Http\Controllers\InvoiceController::class, 'print']);
Route::get('/invoices/{invoice}/pdf', [App\Modules\Sales\Http\Controllers\InvoiceController::class, 'pdf']);
Route::get('/invoices/{invoice}/receipt', [App\Modules\Sales\Http\Controllers\InvoiceController::class, 'receipt']);
