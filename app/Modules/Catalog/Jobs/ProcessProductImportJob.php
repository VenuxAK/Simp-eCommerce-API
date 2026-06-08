<?php

namespace App\Modules\Catalog\Jobs;

use App\Modules\Catalog\Services\ProductImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessProductImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Delete the job if its models no longer exist.
     */
    public $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly string $filePath,
        public readonly int $storeId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ProductImportService $importService): void
    {
        $absolutePath = Storage::disk('local')->path($this->filePath);

        if (!file_exists($absolutePath)) {
            Log::error("Product import failed: File not found at {$absolutePath}");
            return;
        }

        try {
            $result = $importService->importFromPath($absolutePath, $this->storeId);
            
            Log::info('Product import completed successfully', [
                'store_id' => $this->storeId,
                'created' => $result['created'],
                'errors' => $result['errors']
            ]);
        } finally {
            // Always clean up the temporary file
            Storage::disk('local')->delete($this->filePath);
        }
    }
}
