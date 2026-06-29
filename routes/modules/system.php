<?php

use App\Modules\System\Http\Controllers\BackupController;
use App\Modules\System\Http\Controllers\SystemDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:backups.create|backups.download')->group(function () {
    Route::post('/backups', [BackupController::class, 'create']);
    Route::get('/backups', [BackupController::class, 'list']);
    Route::get('/backups/{filename}/download', [BackupController::class, 'download']);
    Route::get('/system/dashboard/summary', [SystemDashboardController::class, 'summary']);
});
