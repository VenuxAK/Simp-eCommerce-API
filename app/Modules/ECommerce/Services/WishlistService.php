<?php

namespace App\Modules\ECommerce\Services;

use App\Modules\Customer\Models\Customer;
use App\Modules\ECommerce\Models\WishlistItem;

class WishlistService
{
    public function getItems(Customer $customer)
    {
        return WishlistItem::where('customer_id', $customer->id)
            ->with('product.category', 'product.variants')
            ->latest()
            ->get();
    }

    public function toggle(Customer $customer, int $productId): array
    {
        $existing = WishlistItem::where('customer_id', $customer->id)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            $existing->delete();

            return ['wishlisted' => false];
        }

        $item = WishlistItem::create([
            'customer_id' => $customer->id,
            'product_id' => $productId,
        ]);

        return [
            'wishlisted' => true,
            'item' => $item,
        ];
    }

    public function removeItem(Customer $customer, int $id): void
    {
        $item = WishlistItem::where('id', $id)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $item->delete();
    }

    public function clear(Customer $customer): void
    {
        WishlistItem::where('customer_id', $customer->id)->delete();
    }
}
