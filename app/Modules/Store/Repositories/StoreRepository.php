<?php

namespace App\Modules\Store\Repositories;

use App\Modules\Core\Repositories\Repository;
use App\Modules\Store\Models\Store;

/**
 * Centralized data access for stores.
 *
 * Encapsulates slug-based resolution (used by middleware,
 * OAuth, checkout, and the store selector) and provides a
 * shorthand for the currently resolved store instance.
 */
class StoreRepository extends Repository
{
    protected function model(): string
    {
        return Store::class;
    }

    /**
     * Resolve a store by its URL-friendly slug.
     *
     * Used by middleware to bind the current store from the
     * X-Store header, as well as by public endpoints that
     * identify stores via slug.
     */
    public function findBySlug(string $slug): ?Store
    {
        return Store::where('slug', $slug)->first();
    }

    /**
     * Return the store that was resolved for the current request.
     *
     * Relies on the store instance that middleware bound into
     * the container. Returns null outside of request context
     * (e.g. queued jobs, CLI commands).
     */
    public function getCurrentStore(): ?Store
    {
        return app('current_store');
    }

    /**
     * Resolve a store by slug or throw if not found.
     *
     * Used in contexts where the store is required to exist
     * (e.g. checkout, storefront) and a 404 is appropriate
     * when it does not.
     */
    public function findBySlugOrFail(string $slug): Store
    {
        return Store::where('slug', $slug)->firstOrFail();
    }
}
