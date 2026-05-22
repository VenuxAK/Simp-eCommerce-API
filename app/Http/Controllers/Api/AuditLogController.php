<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\QueryFilter;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuditLogController extends Controller
{
    use QueryFilter;

    public function index(): AnonymousResourceCollection
    {
        $logs = $this->applyFilters(
            AuditLog::with('user'),
            ['action' => 'action', 'model' => 'model_type'],
        );
        $logs = $this->latestPaginated($logs, 50);

        return AuditLogResource::collection($logs);
    }
}
