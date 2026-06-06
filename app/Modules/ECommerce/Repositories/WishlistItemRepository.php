<?php

namespace App\Modules\ECommerce\Repositories;

use App\Modules\Core\Repositories\Repository;
use App\Modules\ECommerce\Models\WishlistItem;
use Illuminate\Support\Collection;

/**
 * Encapsulates Eloquent data access for WishlistItem records.
 *
 * Provides customer-scoped queries used by the wishlist
 * toggle, list, and clear flows.
 *
 * @extends Repository<WishlistItem>
 */
class WishlistItemRepository extends Repository
{
    protected function model(): string
    {
        return WishlistItem::class;
    }

    /**
     * Return all wishlist items for a given customer, with the specified relations.
     */
    public function findByCustomer(int $customerId, array $with = ['product.category', 'product.variants']): Collection
    {
        return WishlistItem::with($with)
            ->where('customer_id', $customerId)
            ->latest()
            ->get();
    }

    /**
     * Find a wishlist item matching a customer and product.
     */
    public function findExisting(int $customerId, int $productId): ?WishlistItem
    {
        return WishlistItem::where('customer_id', $customerId)
            ->where('product_id', $productId)
            ->first();
    }

    /**
     * Find a wishlist item by ID only if it belongs to the specified customer.
     */
    public function findOwnedByCustomer(int $id, int $customerId): ?WishlistItem
    {
        return WishlistItem::where('id', $id)
            ->where('customer_id', $customerId)
            ->first();
    }

    /**
     * Remove all wishlist items for a given customer.
     */
    public function deleteByCustomer(int $customerId): void
    {
        WishlistItem::where('customer_id', $customerId)->delete();
    }
}
