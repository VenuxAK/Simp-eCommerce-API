<?php

namespace App\Modules\Audit\Repositories;

use App\Modules\Audit\Models\AuditLog;
use App\Modules\Core\Repositories\Repository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Audit-log data-access layer.
 *
 * Provides a filtered paginated query used by the admin controller
 * to browse historical activity records.
 */
class AuditLogRepository extends Repository
{
    protected function model(): string
    {
        return AuditLog::class;
    }

    /**
     * Paginate audit logs with optional filters.
     *
     * Supported filter keys: action, model_type, model_id, user_id.
     */
    public function paginateFiltered(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        return AuditLog::with('user')
            ->when(
                $filters['action'] ?? null,
                fn ($q, $action) => $q->where('action', $action)
            )
            ->when(
                $filters['model_type'] ?? null,
                fn ($q, $type) => $q->where('model_type', $type)
            )
            ->when(
                $filters['model_id'] ?? null,
                fn ($q, $id) => $q->where('model_id', $id)
            )
            ->when(
                $filters['user_id'] ?? null,
                fn ($q, $id) => $q->where('user_id', $id)
            )
            ->latest()
            ->paginate($perPage);
    }
}
