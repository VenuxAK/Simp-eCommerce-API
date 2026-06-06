<?php

namespace App\Modules\Cash\Repositories;

use App\Modules\Cash\Models\CashSession;
use App\Modules\Core\Repositories\Repository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Cash session data-access layer.
 *
 * Provides queries for locating currently open sessions and
 * retrieving store-scoped paginated session histories.
 */
class CashSessionRepository extends Repository
{
    protected function model(): string
    {
        return CashSession::class;
    }

    /**
     * Find the currently active (open) session for a store.
     */
    public function findActiveByStore(?int $storeId): ?CashSession
    {
        return CashSession::whereNull('closed_at')
            ->when($storeId, fn ($q, $id) => $q->where('store_id', $id))
            ->latest('opened_at')
            ->first();
    }

    /**
     * Paginate cash sessions scoped to a store, newest first.
     */
    public function paginateByStore(?int $storeId, int $perPage = 20): LengthAwarePaginator
    {
        return CashSession::when($storeId, fn ($q, $id) => $q->where('store_id', $id))
            ->latest('opened_at')
            ->paginate($perPage);
    }
}
