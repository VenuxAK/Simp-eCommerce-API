<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
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
    /**
     * Paginated product listing filtered by availability, category, and search.
     *
     * A product is considered "available" if it has no variants (simple product)
     * or at least one variant with positive stock. Only in-stock variants are
     * eager-loaded to keep the response lean.
     */
    public function products(Store $store, ?int $categoryId, ?string $search, int $perPage = 20)
    {
        return Product::where('store_id', $store->id)
            ->where(function ($q) {
                $q->whereDoesntHave('variants')
                    ->orWhereHas('variants', fn ($q) => $q->where('stock_quantity', '>', 0));
            })
            ->with(['category', 'variants' => fn ($q) => $q->where('stock_quantity', '>', 0)])
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($search, fn ($q) => $q->whereRaw('LOWER(name) LIKE LOWER(?)', ['%'.$search.'%']))
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Single product detail for the storefront, scoped to the given store.
     */
    public function product(Store $store, string $slug): Product
    {
        return Product::where('store_id', $store->id)
            ->where('slug', $slug)
            ->with(['category', 'variants'])
            ->firstOrFail();
    }

    /**
     * All categories for the store, with product counts for navigation display.
     */
    public function categories(Store $store)
    {
        return Category::where('store_id', $store->id)
            ->withCount('products')
            ->orderBy('name')
            ->get();
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
            'products_count' => Product::where('store_id', $store->id)->count(),
        ];
    }
}
