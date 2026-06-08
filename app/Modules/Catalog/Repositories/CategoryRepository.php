<?php

namespace App\Modules\Catalog\Repositories;

use App\Modules\Catalog\Models\Category;
use App\Modules\Core\Repositories\Repository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Centralized data access for categories.
 *
 * Encapsulates store-scoped paginated listings, product-count
 * lookups, and the firstOrCreate pattern used by CSV imports.
 */
class CategoryRepository extends Repository
{
    protected function model(): string
    {
        return Category::class;
    }

    /**
     * Load a single category with its computed product count.
     *
     * Used by the category show endpoint to avoid an extra
     * client-side query for the navigation badge.
     */
    public function findWithProductCount(int $id): ?Category
    {
        return Category::withCount('products')->find($id);
    }

    /**
     * All categories for a store with product counts.
     *
     * Used by the storefront category navigation to display
     * product counts alongside each category name.
     */
    public function findByStore(int $storeId): Collection
    {
        return Category::where('store_id', $storeId)
            ->withCount('products')
            ->orderBy('name')
            ->get();
    }

    /**
     * Paginated admin listing scoped to a store.
     *
     * Includes product counts and is ordered alphabetically
     * for consistent admin UX.
     */
    public function paginateByStore(int $storeId, int $perPage = 20): LengthAwarePaginator
    {
        return Category::where('store_id', $storeId)
            ->withCount('products')
            ->orderBy('name')
            ->paginate($this->clampPerPage($perPage));
    }

    /**
     * Find a category by name or create it with defaults.
     *
     * Used by the CSV import service to create categories
     * on the fly from row data without pre-import setup.
     */
    public function firstOrCreateByName(string $name, array $extra = []): Category
    {
        return Category::firstOrCreate(
            ['name' => $name],
            $extra,
        );
    }

    /**
     * Count the number of products in a category.
     *
     * Used by the delete guard to refuse deletion when
     * products are still linked to the category.
     */
    public function getProductCount(int $id): int
    {
        return Category::withCount('products')->find($id)?->products_count ?? 0;
    }
}
