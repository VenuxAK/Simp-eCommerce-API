<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Repositories\CategoryRepository;
use App\Modules\Catalog\Repositories\ProductRepository;
use App\Modules\Store\Models\Store;

/**
 * Storefront-facing read queries for the public catalog.
 *
 * Filters out products where every variant is out of stock,
 * while still showing products that have no variants at all
 * (e.g. simple products tracked only at the product level).
 */
class StorefrontService
{
    public function __construct(
        private readonly ProductRepository $productRepo,
        private readonly CategoryRepository $categoryRepo,
    ) {}

    /**
     * Paginated product listing filtered by availability, category, and search.
     *
     * A product is considered "available" if it has no variants (simple product)
     * or at least one variant with positive stock. Only in-stock variants are
     * eager-loaded to keep the response lean.
     */
    public function products(Store $store, ?string $categorySlug, ?string $search, mixed $brandIds = null, int $perPage = 20)
    {
        return $this->productRepo->findAvailableByStore(
            $store->id, $categorySlug, $search, $brandIds, $perPage,
        );
    }

    /**
     * Single product detail for the storefront, scoped to the given store.
     */
    public function product(Store $store, string $slug)
    {
        return $this->productRepo->findBySlug($store->id, $slug);
    }

    /**
     * All categories for the store, with product counts for navigation display.
     */
    public function categories(Store $store)
    {
        return $this->categoryRepo->findByStore($store->id);
    }

    /**
     * All brands for the store.
     */
    public function brands(Store $store)
    {
        return Brand::where('store_id', $store->id)->orderBy('name')->get();
    }

    /**
     * Aggregate store metadata and product count for the storefront header/SEO.
     */
    public function settings(Store $store): array
    {
        return [
            'id' => $store->id,
            'name' => $store->name,
            'slug' => $store->slug,
            'description' => $store->description,
            'logo' => $store->logo ? asset('storage/'.$store->logo) : null,
            'phone' => $store->phone,
            'email' => $store->email,
            'is_active' => $store->is_active,
            'settings' => $store->settings,
            'products_count' => $this->productRepo->getIdsByStore($store->id)->count(),
        ];
    }
}
