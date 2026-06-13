<?php

namespace App\Modules\Catalog\Repositories;

    use App\Modules\Catalog\Models\Product;
    use App\Modules\Core\Repositories\Repository;
    use App\Modules\Sales\Models\OrderItem;
    use Illuminate\Contracts\Pagination\LengthAwarePaginator;
    use Illuminate\Contracts\Pagination\Paginator;
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
     * Paginate products that are currently available for purchase,
     * with configurable sorting, price-range filtering, and extended
     * search (name + description).
     *
     * A product is available if it has no variants (simple product)
     * or at least one variant with positive stock. Only in-stock
     * variants are eager-loaded to keep the response lean.
     *
     * Uses simplePaginate() instead of paginate() to avoid the
     * expensive COUNT(*) query on high-volume storefront listings.
     *
     * @param  int         $storeId       Current store ID for scoping.
     * @param  string|null $categorySlug  Filter by category slug (supports parent).
     * @param  string|null $search        Search term matching name OR description.
     * @param  mixed|null  $brandIds      One or more brand IDs (array or comma-separated).
     * @param  string      $sortBy        Sort field: 'name' (default), 'price', or 'newest'.
     * @param  string      $sortDir       Sort direction: 'asc' (default) or 'desc'.
     * @param  float|null  $minPrice      Minimum base_price filter.
     * @param  float|null  $maxPrice      Maximum base_price filter.
     * @param  int         $perPage       Items per page (clamped via clampPerPage).
     */
    public function findAvailableByStore(
        int $storeId,
        ?string $categorySlug,
        ?string $search,
        mixed $brandIds = null,
        string $sortBy = 'name',
        string $sortDir = 'asc',
        ?float $minPrice = null,
        ?float $maxPrice = null,
        int $perPage = 20,
    ): Paginator {
        return Product::where('store_id', $storeId)
            // Show products with no variants OR at least one in-stock variant.
            ->where(function ($q) {
                $q->whereDoesntHave('variants')
                    ->orWhereHas('variants', fn ($q) => $q->where('stock_quantity', '>', 0));
            })
            ->with([
                'category',
                'brand',
                // Only eager-load variants that are actually purchasable.
                'variants' => fn ($q) => $q->where('stock_quantity', '>', 0),
            ])
            ->select('id', 'category_id', 'brand_id', 'supplier_id', 'store_id', 'name', 'slug', 'description', 'base_price', 'image', 'created_at', 'updated_at')
            // ── Category filter ─────────────────────────────────
            // Accepts a category slug; also matches children via parent FK.
            ->when($categorySlug, function ($q) use ($categorySlug) {
                $q->whereHas('category', function ($q2) use ($categorySlug) {
                    $q2->where('slug', $categorySlug)
                       ->orWhereHas('parent', fn ($q3) => $q3->where('slug', $categorySlug));
                });
            })
            // ── Brand filter ────────────────────────────────────
            // Accepts a single ID (string) or multiple (comma-separated, array).
            ->when($brandIds, function ($q) use ($brandIds) {
                $ids = is_array($brandIds) ? $brandIds : explode(',', $brandIds);
                $q->whereIn('brand_id', $ids);
            })
            // ── Price range filter ──────────────────────────────
            // Uses product.base_price (variant price_adjustment is excluded
            // from filtering; it only affects the per-variant unit price).
            // Both boundaries are nullable — omit to leave unbounded.
            ->when($minPrice !== null, fn ($q) => $q->where('base_price', '>=', $minPrice))
            ->when($maxPrice !== null, fn ($q) => $q->where('base_price', '<=', $maxPrice))
            // ── Extended search (name + description) ────────────
            // Previously only matched product.name; now also searches
            // product.description so customers can find products by
            // material, use-case, or other descriptive text.
            ->when($search, fn ($q) => $q->where(function ($sub) use ($search) {
                $sub->whereRaw('LOWER(name) LIKE LOWER(?)', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(description) LIKE LOWER(?)', ['%'.$search.'%']);
            }))
            // ── Dynamic sorting ─────────────────────────────────
            // Previously hardcoded to ->orderBy('name'). Now supports
            // three sort fields with configurable direction:
            //   - 'name'    → alphabetical (default, backward-compatible)
            //   - 'price'   → by base_price (asc/desc)
            //   - 'newest'  → by created_at (asc/desc)
            // Falls back to ->orderBy('name') for invalid sort_by values.
            ->when(in_array($sortBy, ['price', 'newest', 'name']), function ($q) use ($sortBy, $sortDir) {
                $dir = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';

                match ($sortBy) {
                    'price' => $q->orderBy('base_price', $dir),
                    'newest' => $q->orderBy('created_at', $dir),
                    default => $q->orderBy('name', $dir),
                };
            }, fn ($q) => $q->orderBy('name'))
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
