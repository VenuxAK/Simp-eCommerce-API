<?php

namespace App\Modules\Customer\Repositories;

use App\Modules\Core\Repositories\Repository;
use App\Modules\Customer\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Encapsulates Customer data access patterns used across services
 * and controllers. Supplements the base CRUD with queries for
 * store-scoped listing, lookup by email, and order-count hydration.
 */
class CustomerRepository extends Repository
{
    protected function model(): string
    {
        return Customer::class;
    }

    /**
     * Retrieve a customer by their email address.
     */
    public function findByEmail(string $email, ?int $storeId = null): ?Customer
    {
        if (! $storeId && app()->bound('current_store')) {
            $storeId = app('current_store')->id;
        }

        return Customer::where('email', $email)
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->first();
    }

    /**
     * Paginate customers filtered by store and an optional search term.
     *
     * When a storeId is provided the query is scoped to that store.
     * The search term matches against name, email, and phone columns.
     */
    public function paginateFiltered(?int $storeId, ?string $search, int $perPage = 20): LengthAwarePaginator
    {
        return Customer::withCount('orders')
            ->when($storeId, fn ($q, $id) => $q->where('store_id', $id))
            ->when($search, fn ($q, $term) => $q->where(function ($sub) use ($term) {
                $sub->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            }))
            ->paginate($perPage);
    }

    /**
     * Find a customer by ID with an orders count loaded.
     */
    public function findWithOrderCount(int $id): ?Customer
    {
        return Customer::withCount('orders')->find($id);
    }
}
