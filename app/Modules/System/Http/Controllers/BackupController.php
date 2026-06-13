<?php

namespace App\Modules\System\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\System\Services\BackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly BackupService $backupService,
    ) {}

    public function create(): JsonResponse
    {
        \App\Modules\System\Jobs\CreateBackupJob::dispatch();

        return $this->respondMessage('Backup queued for processing.');
    }

    public function list(): JsonResponse
    {
        return $this->respond(['data' => $this->backupService->list()]);
    }

    public function download(string $filename): BinaryFileResponse|JsonResponse
    {
        $filename = basename($filename);

        $path = "backups/{$filename}";
        if (! Storage::disk('local')->exists($path)) {
            return $this->respondError(__('messages.backup.not_found'), 404);
        }

        return response()->download(Storage::disk('local')->path($path));
    }
}
