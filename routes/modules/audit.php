<?php

use Illuminate\Support\Facades\Route;

/*
 * Audit trail — admin only.
 */
Route::get('/audit-logs', [App\Modules\Audit\Http\Controllers\AuditLogController::class, 'index'])->middleware('admin');
