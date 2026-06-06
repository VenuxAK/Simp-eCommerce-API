<?php

namespace App\Modules\Supplier\Repositories;

use App\Modules\Core\Repositories\Repository;
use App\Modules\Supplier\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Supplier data-access layer.
 *
 * Adds store-scoped pagination and aggregate queries
 * used by the SupplierController and related services.
 */
class SupplierRepository extends Repository
{
    protected function model(): string
    {
        return Supplier::class;
    }

    /**
     * Paginate suppliers scoped to a store, with product counts.
     */
    public function paginateByStore(?int $storeId, int $perPage = 20): LengthAwarePaginator
    {
        return Supplier::withCount('products')
            ->when($storeId, fn ($q, $id) => $q->where('store_id', $id))
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Find a supplier by ID with the product count loaded.
     */
    public function findWithProductCount(int $id): ?Supplier
    {
        return Supplier::withCount('products')->find($id);
    }

    /**
     * Return the total number of products for a given supplier.
     */
    public function getProductCount(int $id): int
    {
        return Supplier::find($id)?->products()->count() ?? 0;
    }
}
