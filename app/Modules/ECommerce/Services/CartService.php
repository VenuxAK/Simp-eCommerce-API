<?php

namespace App\Modules\ECommerce\Services;

use App\Modules\Catalog\Repositories\ProductVariantRepository;
use App\Modules\Customer\Models\Customer;
use App\Modules\ECommerce\Models\CartItem;
use App\Modules\ECommerce\Repositories\CartItemRepository;
use Illuminate\Support\Collection;

class CartService
{
    public function __construct(
        private readonly CartItemRepository $cartItemRepository,
        private readonly ProductVariantRepository $productVariantRepository,
    ) {}

    public function getItems(Customer $customer): Collection
    {
        return $this->cartItemRepository->findByCustomer($customer->id);
    }

    public function addItem(Customer $customer, int $variantId, int $quantity): CartItem
    {
        $this->validateStock($variantId, $quantity);

        $existing = $this->cartItemRepository->findExisting($customer->id, $variantId);

        if ($existing) {
            $newQty = $existing->quantity + $quantity;
            $this->validateStock($variantId, $newQty);
            $this->cartItemRepository->update($existing, ['quantity' => $newQty]);

            return $existing;
        }

        return $this->cartItemRepository->create([
            'customer_id' => $customer->id,
            'product_variant_id' => $variantId,
            'quantity' => $quantity,
        ]);
    }

    public function updateItem(CartItem $cartItem, int $quantity): void
    {
        $this->validateStock($cartItem->product_variant_id, $quantity);
        $this->cartItemRepository->update($cartItem, ['quantity' => $quantity]);
    }

    public function removeItem(CartItem $cartItem): void
    {
        $this->cartItemRepository->delete($cartItem);
    }

    public function clearCart(Customer $customer): void
    {
        $this->cartItemRepository->deleteByCustomer($customer->id);
    }

    public function syncCart(Customer $customer, array $items): Collection
    {
        foreach ($items as $item) {
            $variantId = $item['product_variant_id'];
            $quantity = $item['quantity'];

            $existing = $this->cartItemRepository->findExisting($customer->id, $variantId);
            if ($existing) {
                $newQty = $existing->quantity + $quantity;
                // Cap at available stock
                $variant = $this->productVariantRepository->find($variantId);
                if ($variant) {
                    $newQty = min($newQty, $variant->stock_quantity);
                    $this->cartItemRepository->update($existing, ['quantity' => $newQty]);
                }
            } else {
                $variant = $this->productVariantRepository->find($variantId);
                if ($variant) {
                    $quantity = min($quantity, $variant->stock_quantity);
                    if ($quantity > 0) {
                        $this->cartItemRepository->create([
                            'customer_id' => $customer->id,
                            'product_variant_id' => $variantId,
                            'quantity' => $quantity,
                        ]);
                    }
                }
            }
        }

        return $this->getItems($customer);
    }

    private function validateStock(int $variantId, int $quantity): void
    {
        $variant = $this->productVariantRepository->findOrFail($variantId);

        if ($variant->stock_quantity < $quantity) {
            abort(422, "Insufficient stock for '{$variant->sku}'. Available: {$variant->stock_quantity}.");
        }
    }
}
