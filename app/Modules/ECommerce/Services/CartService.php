<?php

namespace App\Modules\ECommerce\Services;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Customer\Models\Customer;
use App\Modules\ECommerce\Models\CartItem;
use Illuminate\Database\Eloquent\Collection;

class CartService
{
    public function getItems(Customer $customer): Collection
    {
        return CartItem::where('customer_id', $customer->id)
            ->with('variant.product')
            ->orderBy('created_at')
            ->get();
    }

    public function addItem(Customer $customer, int $variantId, int $quantity): CartItem
    {
        $this->validateStock($variantId, $quantity);

        $existing = CartItem::where('customer_id', $customer->id)
            ->where('product_variant_id', $variantId)
            ->first();

        if ($existing) {
            $newQty = $existing->quantity + $quantity;
            $this->validateStock($variantId, $newQty);
            $existing->update(['quantity' => $newQty]);

            return $existing;
        }

        return CartItem::create([
            'customer_id' => $customer->id,
            'product_variant_id' => $variantId,
            'quantity' => $quantity,
        ]);
    }

    public function updateItem(CartItem $cartItem, int $quantity): void
    {
        $this->validateStock($cartItem->product_variant_id, $quantity);
        $cartItem->update(['quantity' => $quantity]);
    }

    public function removeItem(CartItem $cartItem): void
    {
        $cartItem->delete();
    }

    public function clearCart(Customer $customer): void
    {
        CartItem::where('customer_id', $customer->id)->delete();
    }

    private function validateStock(int $variantId, int $quantity): void
    {
        $variant = ProductVariant::findOrFail($variantId);

        if ($variant->stock_quantity < $quantity) {
            abort(422, "Insufficient stock for '{$variant->sku}'. Available: {$variant->stock_quantity}.");
        }
    }
}
