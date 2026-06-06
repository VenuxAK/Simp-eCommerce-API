<?php

namespace App\Modules\Audit\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Audit\Http\Resources\AuditLogResource;
use App\Modules\Audit\Models\AuditLog;
use App\Modules\Core\Traits\QueryFilter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Handles AuditLog-related API requests.
 */
class AuditLogController extends Controller
{
    use QueryFilter;

    public function index(Request $request): AnonymousResourceCollection
    {
        $logs = $this->applyFilters(
            AuditLog::with('user'),
            ['action' => 'action', 'model' => 'model_type'],
        );
        $logs = $this->latestPaginated($logs, 50);

        return AuditLogResource::collection($logs);
    }
}
