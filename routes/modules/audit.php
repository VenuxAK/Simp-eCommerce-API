<?php

use App\Modules\Audit\Http\Controllers\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::get('/audit-logs', [AuditLogController::class, 'index'])->middleware('permission:audit.view');
