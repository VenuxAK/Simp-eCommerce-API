<?php

use App\Modules\Audit\Http\Controllers\AuditLogController;
use Illuminate\Support\Facades\Route;

/*
 * Audit trail — admin only.
 */
Route::get('/audit-logs', [AuditLogController::class, 'index'])->middleware('role:root');
