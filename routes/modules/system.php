<?php

use Illuminate\Support\Facades\Route;

/*
 * Database backup management — admin only.
 * The download endpoint strips path traversal characters via basename().
 */
Route::post('/backups', [App\Modules\System\Http\Controllers\BackupController::class, 'create'])->middleware('admin');
Route::get('/backups', [App\Modules\System\Http\Controllers\BackupController::class, 'list'])->middleware('admin');
Route::get('/backups/{filename}/download', [App\Modules\System\Http\Controllers\BackupController::class, 'download'])
    ->middleware('admin')
    ->where('filename', '[A-Za-z0-9._-]+');
