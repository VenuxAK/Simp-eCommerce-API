<?php

namespace App\Modules\Customer\Repositories;

use App\Modules\Core\Repositories\Repository;
use App\Modules\Customer\Models\Address;
use Illuminate\Support\Collection;

/**
 * Manages address records belonging to customers.
 *
 * Provides ownership-scoped lookups so addresses can only be
 * retrieved when they belong to the correct customer, and a
 * convenience method to bulk-reset default flags.
 */
class AddressRepository extends Repository
{
    protected function model(): string
    {
        return Address::class;
    }

    /**
     * Retrieve all addresses for the given customer.
     *
     * @return Collection<int, Address>
     */
    public function findByCustomer(int $customerId): Collection
    {
        return Address::where('customer_id', $customerId)->get();
    }

    /**
     * Find an address by ID only if it belongs to the specified customer.
     */
    public function findOwnedByCustomer(int $id, int $customerId): ?Address
    {
        return Address::where('id', $id)
            ->where('customer_id', $customerId)
            ->first();
    }

    /**
     * Unmark all default addresses for a customer.
     *
     * Optionally excludes a specific address ID so that address
     * can be set as the new default without a redundant update.
     */
    public function clearDefaults(int $customerId, ?int $exceptId = null): void
    {
        Address::where('customer_id', $customerId)
            ->where('is_default', true)
            ->when($exceptId, fn ($q, $id) => $q->where('id', '!=', $id))
            ->update(['is_default' => false]);
    }
}
