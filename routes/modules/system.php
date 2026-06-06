<?php

use App\Modules\System\Http\Controllers\BackupController;
use Illuminate\Support\Facades\Route;

/*
 * Database backup management — admin only.
 * The download endpoint strips path traversal characters via basename().
 */
Route::post('/backups', [BackupController::class, 'create'])->middleware('role:root');
Route::get('/backups', [BackupController::class, 'list'])->middleware('role:root');
Route::get('/backups/{filename}/download', [BackupController::class, 'download'])
    ->middleware('role:root')
    ->where('filename', '[A-Za-z0-9._-]+');
