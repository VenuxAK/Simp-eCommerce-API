<?php

namespace App\Modules\System\Jobs;

use App\Modules\System\Services\BackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(BackupService $backupService): void
    {
        try {
            $result = $backupService->create();
            Log::info("Backup completed successfully: {$result['filename']}");
        } catch (\Exception $e) {
            Log::error("Backup failed: {$e->getMessage()}");
            throw $e;
        }
    }
}
