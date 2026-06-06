<?php

namespace App\Modules\Core\Traits;

use App\Modules\Store\Models\Store;
use Illuminate\Database\Eloquent\Builder;

/**
 * Scope Eloquent queries by the current store and auto-assign store_id on create.
 *
 * Resolution order:
 * 1. Staff/store_admin users → their assigned store_id
 * 2. Root users → X-Store header (set via store selector)
 * 3. Fallback → the store resolved by ResolveStore middleware (default 'main')
 */
trait StoreScope
{
    public function resolveStoreId(): ?int
    {
        $user = request()->user();

        // Staff/store_admin assigned to a specific store.
        if ($user && ($user->isStaff() || $user->isStoreAdmin()) && $user->store_id) {
            return $user->store_id;
        }

        // Root user with an active store selector.
        if ($user && $user->isRoot() && request()->header('X-Store')) {
            $store = Store::where('slug', request()->header('X-Store'))->first();
            if ($store) {
                return $store->id;
            }
        }

        // Fallback: the store resolved by ResolveStore middleware (defaults to 'main').
        if (app()->bound('current_store') && ($store = app('current_store'))) {
            return $store->id;
        }

        // Last resort: the 'main' store.
        $store = Store::where('slug', 'main')->first();

        return $store?->id;
    }

    public function scopeByStore(Builder $query): Builder
    {
        $storeId = $this->resolveStoreId();

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        return $query;
    }

    public function mergeStoreId(array $data): array
    {
        if (empty($data['store_id'])) {
            $storeId = $this->resolveStoreId();
            if ($storeId) {
                $data['store_id'] = $storeId;
            }
        }

        return $data;
    }
}
