<?php

namespace App\Modules\Catalog\Repositories;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Core\Repositories\Repository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Centralized data access for product variants.
 *
 * Encapsulates SKU lookups, stock-summary queries with computed
 * status flags, and pessimistic locking for order-placement flows.
 */
class ProductVariantRepository extends Repository
{
    protected function model(): string
    {
        return ProductVariant::class;
    }

    /**
     * Load a variant with its parent product.
     *
     * Used when displaying variant detail alongside product context
     * (e.g. product name, category) without separate queries.
     */
    public function findWithProduct(int $id): ?ProductVariant
    {
        return ProductVariant::with('product')->find($id);
    }

    /**
     * Find a variant by its unique SKU string.
     *
     * Used by barcode scanners and POS integrations where only
     * the SKU is available at scan time. Eager-loads the product
     * and its category for immediate display.
     */
    public function findBySku(string $sku, array $with = ['product.category']): ?ProductVariant
    {
        return ProductVariant::with($with)
            ->where('sku', $sku)
            ->first();
    }

    /**
     * Retrieve all variants belonging to a set of product IDs.
     *
     * Used by the dashboard and inventory views to pull variant
     * data without loading the parent product rows.
     */
    public function findByProductIds(array $productIds): Collection
    {
        return ProductVariant::whereIn('product_id', $productIds)->get();
    }

    /**
     * Stock-status summary for a set of product IDs.
     *
     * Returns variant rows with a computed stock_status column:
     * 'out' when quantity is zero, 'low' when at or below 5,
     * and 'ok' otherwise. Used by the dashboard to populate
     * low-stock and out-of-stock alerts.
     */
    public function findStockSummaryByProductIds(array $productIds): Collection
    {
        return ProductVariant::with('product')
            ->whereIn('product_id', $productIds)
            ->select('id', 'product_id', 'sku', 'size', 'color', 'stock_quantity',
                DB::raw("CASE WHEN stock_quantity = 0 THEN 'out' WHEN stock_quantity <= 5 THEN 'low' ELSE 'ok' END as stock_status"))
            ->get();
    }

    /**
     * Retrieve a variant with a pessimistic row lock.
     *
     * Used within order-placement transactions to prevent
     * race conditions on stock decrement. The lock is held
     * until the enclosing transaction commits.
     */
    public function lockForUpdate(int $id): ?ProductVariant
    {
        return ProductVariant::lockForUpdate()->find($id);
    }
}
