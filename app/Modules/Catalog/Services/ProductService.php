<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Repositories\ProductRepository;
use App\Modules\Catalog\Repositories\ProductVariantRepository;
use Illuminate\Support\Str;

/**
 * Orchestrates product CRUD and variant sync operations.
 *
 * Keep business rules (slug uniqueness, variant diffing, order-protected deletion)
 * in a dedicated service rather than the controller so they remain testable
 * independent of HTTP concerns.
 */
class ProductService
{
    public function __construct(
        private readonly ProductRepository $productRepo,
        private readonly ProductVariantRepository $variantRepo,
    ) {}

    /**
     * Create a product and its variants atomically.
     *
     * The slug appends random characters to avoid collisions
     * on identical names — uniqueness is not enforced at the
     * DB level for this field.
     */
    public function createProduct(array $data): Product
    {
        $product = $this->productRepo->create([
            'category_id' => $data['category_id'],
            'supplier_id' => $data['supplier_id'] ?? null,
            'store_id' => $data['store_id'] ?? null,
            'name' => $data['name'],
            'slug' => Str::slug($data['name']).'-'.Str::random(8),
            'description' => $data['description'] ?? null,
            'base_price' => $data['base_price'],
            'image' => $data['image'] ?? null,
        ]);

        foreach ($data['variants'] as $variantData) {
            $this->variantRepo->create([
                'product_id' => $product->id,
                'sku' => $variantData['sku'],
                'size' => $variantData['size'] ?? null,
                'color' => $variantData['color'] ?? null,
                'image' => $variantData['image'] ?? null,
                'price_adjustment' => $variantData['price_adjustment'] ?? 0,
                'purchase_price' => $variantData['purchase_price'] ?? null,
                'stock_quantity' => $variantData['stock_quantity'] ?? 0,
            ]);
        }

        return $product;
    }

    /**
     * Patch only the product-level fields that were actually sent.
     *
     * Regenerating the slug on every name change is intentional —
     * slugs are treated as opaque identifiers, not user-facing URLs.
     */
    public function updateProduct(Product $product, array $data): Product
    {
        $updateData = [];
        foreach (['category_id', 'supplier_id', 'name', 'description', 'base_price', 'image'] as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }
        if (isset($data['name'])) {
            $updateData['slug'] = Str::slug($data['name']).'-'.Str::random(8);
        }
        $product->update($updateData);

        return $product;
    }

    /**
     * Reconcile the incoming variant list with what is currently persisted.
     *
     * Existing variants are matched by ID; unknown IDs create new records.
     * Variants omitted from the payload are candidates for deletion, but
     * only if they have no order history — we refuse to orphan order items.
     * Returns an error string if deletion was blocked, or null on success.
     */
    public function syncVariants(Product $product, array $variants): ?string
    {
        $existingIds = $product->variants()->pluck('id')->toArray();
        $updatedIds = [];

        foreach ($variants as $variantData) {
            if (isset($variantData['id']) && in_array($variantData['id'], $existingIds)) {
                $product->variants()->where('id', $variantData['id'])->update([
                    'sku' => $variantData['sku'],
                    'size' => $variantData['size'] ?? null,
                    'color' => $variantData['color'] ?? null,
                    'image' => $variantData['image'] ?? null,
                    'price_adjustment' => $variantData['price_adjustment'] ?? 0,
                    'purchase_price' => $variantData['purchase_price'] ?? null,
                    'stock_quantity' => $variantData['stock_quantity'] ?? 0,
                ]);
                $updatedIds[] = $variantData['id'];
            } else {
                $new = $this->variantRepo->create([
                    'product_id' => $product->id,
                    'sku' => $variantData['sku'],
                    'size' => $variantData['size'] ?? null,
                    'color' => $variantData['color'] ?? null,
                    'image' => $variantData['image'] ?? null,
                    'price_adjustment' => $variantData['price_adjustment'] ?? 0,
                    'purchase_price' => $variantData['purchase_price'] ?? null,
                    'stock_quantity' => $variantData['stock_quantity'] ?? 0,
                ]);
                $updatedIds[] = $new->id;
            }
        }

        $toDelete = array_diff($existingIds, $updatedIds);
        if (! empty($toDelete)) {
            $variantOrderCount = $this->productRepo->findWithOrderHistory($toDelete);
            if ($variantOrderCount > 0) {
                return "Cannot delete {$variantOrderCount} variant(s) with existing order history. Remove them from the payload to keep them.";
            }
            $product->variants()->whereIn('id', $toDelete)->delete();
        }

        return null;
    }

    /**
     * Determine whether a product can be safely deleted.
     *
     * Returns an error message if any order item references
     * one of its variants, or null if it is safe to proceed.
     */
    public function canDelete(Product $product): ?string
    {
        $orderCount = $this->productRepo->findWithOrderHistory(
            $product->variants()->pluck('id')->toArray(),
        );

        if ($orderCount > 0) {
            return "Cannot delete product: {$orderCount} order item(s) reference it.";
        }

        return null;
    }
}
