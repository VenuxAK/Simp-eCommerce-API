<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function create(): JsonResponse
    {
        $dbPath = database_path('database.sqlite');
        if (!File::exists($dbPath)) {
            return response()->json(['message' => 'Database file not found.'], 404);
        }

        $filename = 'backup-' . now()->format('Y-m-d-His') . '.sqlite';
        Storage::disk('local')->makeDirectory('backups');
        File::copy($dbPath, Storage::disk('local')->path("backups/{$filename}"));

        return response()->json(['message' => 'Backup created.', 'filename' => $filename]);
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

        return response()->json(['data' => $files]);
    }

    public function download(string $filename): BinaryFileResponse|JsonResponse
    {
        $filename = basename($filename);

        $path = "backups/{$filename}";
        if (!Storage::disk('local')->exists($path)) {
            return response()->json(['message' => 'Backup not found.'], 404);
        }

        return response()->download(Storage::disk('local')->path($path));
    }
}
