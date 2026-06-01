<?php

use Illuminate\Support\Facades\Route;

/*
 * Database backup management — admin only.
 * The download endpoint strips path traversal characters via basename().
 */
Route::post('/backups', [App\Modules\System\Http\Controllers\BackupController::class, 'create'])->middleware('role:root');
Route::get('/backups', [App\Modules\System\Http\Controllers\BackupController::class, 'list'])->middleware('role:root');
Route::get('/backups/{filename}/download', [App\Modules\System\Http\Controllers\BackupController::class, 'download'])
    ->middleware('role:root')
    ->where('filename', '[A-Za-z0-9._-]+');
