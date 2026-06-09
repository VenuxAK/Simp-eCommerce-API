<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Store\Models\Store;
use Illuminate\Support\Facades\Cache;

/**
 * Caching decorator for storefront read queries.
 *
 * Wraps StorefrontService methods with cache-aside logic using
 * driver-agnostic key prefixes. Each store gets its own key namespace
 * so invalidation is scoped per store.
 *
 * Cache keys follow the pattern: storefront:{storeId}:v{version}:{resource}:{params}
 *
 * Uses a versioning strategy for invalidation: when a store's catalog changes,
 * the version counter is bumped so all existing keys become stale without
 * needing to enumerate or delete them individually. Old entries expire naturally.
 *
 * This works with any Laravel cache driver (database, file, redis).
 * When Redis is available (Phase 2), consider upgrading to cache tags
 * for more granular group invalidation.
 */
class StorefrontCacheService
{
    /** Default TTL in seconds (5 minutes). */
    private const TTL = 300;

    /** Prefix for all storefront cache keys. */
    private const PREFIX = 'storefront';

    public function __construct(
        private readonly StorefrontService $storefrontService,
    ) {}

    /**
     * Cached paginated product listing.
     *
     * Search queries bypass the cache entirely — too many permutations
     * for meaningful hit rates, and search latency tolerance is higher.
     */
    public function products(
        Store $store,
        ?int $categoryId,
        ?string $search,
        int $perPage = 20,
        int $page = 1,
    ) {
        if ($search) {
            $paginator = $this->storefrontService->products($store, $categoryId, $search, $perPage);
            return \App\Modules\Catalog\Http\Resources\ProductResource::collection($paginator)->response()->getData(true);
        }

        $key = $this->key($store->id, 'products', [
            'cat' => $categoryId,
            'pp' => $perPage,
            'p' => $page,
        ]);

        return Cache::remember($key, self::TTL, function () use ($store, $categoryId, $perPage) {
            $paginator = $this->storefrontService->products($store, $categoryId, null, $perPage);
            return \App\Modules\Catalog\Http\Resources\ProductResource::collection($paginator)->response()->getData(true);
        });
    }

    /**
     * Cached single product detail by slug.
     */
    public function product(Store $store, string $slug)
    {
        $key = $this->key($store->id, 'product', ['slug' => $slug]);

        return Cache::remember($key, self::TTL, function () use ($store, $slug) {
            $product = $this->storefrontService->product($store, $slug);
            return (new \App\Modules\Catalog\Http\Resources\ProductResource($product))->resolve();
        });
    }

    /**
     * Cached category listing for a store.
     */
    public function categories(Store $store)
    {
        $key = $this->key($store->id, 'categories');

        return Cache::remember($key, self::TTL, function () use ($store) {
            return $this->storefrontService->categories($store)->toArray();
        });
    }

    /**
     * Cached store settings/metadata.
     */
    public function settings(Store $store): array
    {
        $key = $this->key($store->id, 'settings');

        return Cache::remember($key, self::TTL, function () use ($store) {
            return $this->storefrontService->settings($store);
        });
    }

    /**
     * Invalidate all storefront caches for a given store.
     *
     * Called when products, categories, or store settings are modified.
     * Bumps a store-specific version counter so all existing cache keys
     * become stale without needing to enumerate them.
     */
    public function invalidateStore(int $storeId): void
    {
        $versionKey = $this->versionKey($storeId);
        $current = (int) Cache::get($versionKey, 0);
        Cache::put($versionKey, $current + 1, self::TTL * 10);
    }

    /**
     * Build a cache key incorporating the store version for automatic expiry.
     *
     * The version is embedded in the key itself, so when the version is bumped,
     * new requests generate new keys and old cached entries expire naturally
     * via TTL without explicit deletion.
     */
    private function key(int $storeId, string $resource, array $params = []): string
    {
        $version = (int) Cache::get($this->versionKey($storeId), 0);

        $paramString = $params ? ':' . http_build_query($params) : '';

        return self::PREFIX . ":{$storeId}:v{$version}:{$resource}{$paramString}";
    }

    /**
     * The key that holds the current version counter for a store's cache.
     */
    private function versionKey(int $storeId): string
    {
        return self::PREFIX . ":{$storeId}:version";
    }
}
