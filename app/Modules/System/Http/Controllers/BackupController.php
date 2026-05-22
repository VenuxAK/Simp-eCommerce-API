<?php

namespace App\Modules\System\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Handles Backup-related API requests.
 */
class BackupController extends Controller
{
    use ApiResponse;

    public function create(): JsonResponse
    {
        $dbPath = database_path('database.sqlite');
        if (!File::exists($dbPath)) {
            return $this->respondError('Database file not found.', 404);
        }

        $filename = 'backup-' . now()->format('Y-m-d-His') . '.sqlite';
        Storage::disk('local')->makeDirectory('backups');
        File::copy($dbPath, Storage::disk('local')->path("backups/{$filename}"));

        return $this->respond(['message' => 'Backup created.', 'filename' => $filename]);
    }

    public function list(): JsonResponse
    {
        Storage::disk('local')->makeDirectory('backups');
        $files = collect(Storage::disk('local')->files('backups'))
            ->filter(fn($f) => str_ends_with($f, '.sqlite'))
            ->map(fn($f) => [
                'filename' => basename($f),
                'size' => Storage::disk('local')->size($f),
                'created_at' => date('Y-m-d H:i:s', Storage::disk('local')->lastModified($f)),
            ])
            ->sortByDesc('created_at')
            ->values();

        return $this->respond(['data' => $files]);
    }

    /**
     * Download a backup file.
     *
     * Uses basename() to strip directory traversal characters,
     * preventing unauthorized access outside the backups directory.
     */
    public function download(string $filename): BinaryFileResponse|JsonResponse
    {
        $filename = basename($filename);

        $path = "backups/{$filename}";
        if (!Storage::disk('local')->exists($path)) {
            return $this->respondError('Backup not found.', 404);
        }

        return response()->download(Storage::disk('local')->path($path));
    }
}
