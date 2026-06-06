<?php

namespace App\Modules\ECommerce\Repositories;

use App\Modules\Core\Repositories\Repository;
use App\Modules\ECommerce\Models\CartItem;
use Illuminate\Support\Collection;

/**
 * Encapsulates Eloquent data access for CartItem records.
 *
 * Provides customer-scoped lookups used throughout the
 * checkout and cart-management flows.
 *
 * @extends Repository<CartItem>
 */
class CartItemRepository extends Repository
{
    protected function model(): string
    {
        return CartItem::class;
    }

    /**
     * Return all cart items for a given customer, with the specified relations.
     */
    public function findByCustomer(int $customerId, array $with = ['variant.product']): Collection
    {
        return CartItem::with($with)
            ->where('customer_id', $customerId)
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Find a cart item matching a customer and product variant.
     */
    public function findExisting(int $customerId, int $variantId): ?CartItem
    {
        return CartItem::where('customer_id', $customerId)
            ->where('product_variant_id', $variantId)
            ->first();
    }

    /**
     * Remove all cart items for a given customer.
     */
    public function deleteByCustomer(int $customerId): void
    {
        CartItem::where('customer_id', $customerId)->delete();
    }
}
