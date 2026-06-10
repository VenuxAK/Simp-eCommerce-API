<?php

namespace App\Modules\Catalog\Repositories;

use App\Modules\Catalog\Models\Product;
use App\Modules\Core\Repositories\Repository;
use App\Modules\Sales\Models\OrderItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Centralized data access for products.
 *
 * Encapsulates the paginated listing, store-scoped filtering,
 * availability-aware queries, and order-history checks that
 * were previously repeated across controllers and services.
 */
class ProductRepository extends Repository
{
    protected function model(): string
    {
        return Product::class;
    }

    /**
     * Load a product with the relations needed for detail views.
     *
     * Used by store/show/update flows where the response always
     * includes the parent category and variant children.
     */
    public function findWithRelations(int $id, array $relations = ['category', 'brand', 'variants']): ?Product
    {
        return Product::with($relations)->find($id);
    }

    /**
     * Paginate products scoped to a store, with optional category
     * and search-text filters. Used by the admin product listing.
     */
    public function findByStore(int $storeId, ?int $categoryId, ?string $search, int $perPage = 20): LengthAwarePaginator
    {
        return Product::with(['category', 'brand', 'supplier', 'variants'])
            ->where('store_id', $storeId)
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($search, fn ($q) => $q->whereRaw('LOWER(name) LIKE LOWER(?)', ['%'.$search.'%']))
            ->orderBy('name')
            ->paginate($this->clampPerPage($perPage));
    }

    /**
     * Paginate products that are currently available for purchase.
     *
     * A product is available if it has no variants (simple product)
     * or at least one variant with positive stock. Only in-stock
     * variants are eager-loaded to keep the response lean.
     *
     * Uses simplePaginate() instead of paginate() to avoid the
     * expensive COUNT(*) query on high-volume storefront listings.
     */
    public function findAvailableByStore(int $storeId, ?string $categorySlug, ?string $search, mixed $brandIds = null, int $perPage = 20): \Illuminate\Contracts\Pagination\Paginator
    {
        return Product::where('store_id', $storeId)
            ->where(function ($q) {
                $q->whereDoesntHave('variants')
                    ->orWhereHas('variants', fn ($q) => $q->where('stock_quantity', '>', 0));
            })
            ->with([
                'category',
                'brand',
                'variants' => fn ($q) => $q->where('stock_quantity', '>', 0),
            ])
            ->select('id', 'category_id', 'brand_id', 'supplier_id', 'store_id', 'name', 'slug', 'description', 'base_price', 'image', 'created_at', 'updated_at')
            ->when($categorySlug, function ($q) use ($categorySlug) {
                $q->whereHas('category', function ($q2) use ($categorySlug) {
                    $q2->where('slug', $categorySlug)
                       ->orWhereHas('parent', fn ($q3) => $q3->where('slug', $categorySlug));
                });
            })
            ->when($brandIds, function ($q) use ($brandIds) {
                $ids = is_array($brandIds) ? $brandIds : explode(',', $brandIds);
                $q->whereIn('brand_id', $ids);
            })
            ->when($search, fn ($q) => $q->whereRaw('LOWER(name) LIKE LOWER(?)', ['%'.$search.'%']))
            ->orderBy('name')
            ->simplePaginate($this->clampPerPage($perPage));
    }

    /**
     * Look up a single product by slug scoped to a store.
     *
     * Used by the storefront product detail page where the URL
     * contains the slug rather than the numeric ID.
     */
    public function findBySlug(int $storeId, string $slug, array $relations = ['category', 'brand', 'variants']): Product
    {
        return Product::where('store_id', $storeId)
            ->where('slug', $slug)
            ->with($relations)
            ->firstOrFail();
    }

    /**
     * Pluck all product IDs belonging to a given store.
     *
     * Used by the dashboard to feed ID lists into aggregated
     * variant and stock queries without loading full models.
     */
    public function getIdsByStore(int $storeId): Collection
    {
        return Product::where('store_id', $storeId)
            ->pluck('id');
    }

    /**
     * Count how many order items reference any of the given variant IDs.
     *
     * Used by variant-sync and product-deletion guards to determine
     * whether a variant or product has sales history that prevents
     * destructive operations.
     */
    public function findWithOrderHistory(array $variantIds): int
    {
        return OrderItem::whereIn('product_variant_id', $variantIds)->count();
    }
}
