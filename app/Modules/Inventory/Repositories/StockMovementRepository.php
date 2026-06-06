<?php

namespace App\Modules\Inventory\Repositories;

use App\Modules\Core\Repositories\Repository;
use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Stock-movement data-access layer.
 *
 * Supports filtered pagination used by the controller and
 * aggregate queries used by order-fulfilment services.
 */
class StockMovementRepository extends Repository
{
    protected function model(): string
    {
        return StockMovement::class;
    }

    /**
     * Paginate stock movements with optional filters.
     *
     * Supported filter keys: product_variant_id, reason, reference_type, reference_id.
     */
    public function paginateFiltered(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return StockMovement::when(
            $filters['product_variant_id'] ?? null,
            fn ($q, $id) => $q->where('product_variant_id', $id)
        )
            ->when(
                $filters['reason'] ?? null,
                fn ($q, $reason) => $q->where('reason', $reason)
            )
            ->when(
                $filters['reference_type'] ?? null,
                fn ($q, $type) => $q->where('reference_type', $type)
            )
            ->when(
                $filters['reference_id'] ?? null,
                fn ($q, $id) => $q->where('reference_id', $id)
            )
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Sum the quantity that has already been returned for a given reference.
     *
     * Returns a positive integer representing the total units returned.
     */
    public function getReturnedSum(int $variantId, string $referenceType, int $referenceId): int
    {
        return StockMovement::where('product_variant_id', $variantId)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('quantity_change', '<', 0)
            ->sum(DB::raw('ABS(quantity_change)'));
    }
}
