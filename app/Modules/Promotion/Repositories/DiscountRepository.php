<?php

namespace App\Modules\Promotion\Repositories;

use App\Modules\Core\Repositories\Repository;
use App\Modules\Promotion\Models\Discount;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Handles discount query logic.
 *
 * Supplements the base repository with queries to retrieve
 * currently active discounts and store-scoped paginated lists.
 */
class DiscountRepository extends Repository
{
    protected function model(): string
    {
        return Discount::class;
    }

    /**
     * Return all discounts that are currently active and within their
     * configured date range (starts_at ≤ now ≤ ends_at).
     *
     * Nullable date boundaries are treated as unbounded.
     *
     * @return Collection<int, Discount>
     */
    public function findActive(): Collection
    {
        return Discount::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderBy('name')
            ->get();
    }

    /**
     * Paginate discounts scoped to the given store, ordered by name.
     */
    public function paginateByStore(?int $storeId, int $perPage = 20): LengthAwarePaginator
    {
        return Discount::when($storeId, fn ($q, $id) => $q->where('store_id', $id))
            ->orderBy('name')
            ->paginate($perPage);
    }
}
