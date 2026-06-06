<?php

namespace App\Modules\Audit\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Audit\Http\Resources\AuditLogResource;
use App\Modules\Audit\Repositories\AuditLogRepository;
use App\Modules\Core\Traits\QueryFilter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Handles AuditLog-related API requests.
 */
class AuditLogController extends Controller
{
    use QueryFilter;

    public function __construct(
        private readonly AuditLogRepository $auditLogRepository,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $this->auditLogRepository->query()->with('user');

        $logs = $this->applyFilters(
            $query,
            ['action' => 'action', 'model' => 'model_type'],
        );
        $logs = $this->latestPaginated($logs, 50);

        return AuditLogResource::collection($logs);
    }
}
