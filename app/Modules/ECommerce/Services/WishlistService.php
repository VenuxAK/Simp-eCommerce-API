<?php

namespace App\Modules\ECommerce\Services;

use App\Modules\Customer\Models\Customer;
use App\Modules\ECommerce\Repositories\WishlistItemRepository;

class WishlistService
{
    public function __construct(
        private readonly WishlistItemRepository $wishlistItemRepository,
    ) {}

    public function getItems(Customer $customer)
    {
        return $this->wishlistItemRepository->findByCustomer($customer->id);
    }

    public function toggle(Customer $customer, int $productId): array
    {
        $existing = $this->wishlistItemRepository->findExisting($customer->id, $productId);

        if ($existing) {
            $this->wishlistItemRepository->delete($existing);

            return ['wishlisted' => false];
        }

        $item = $this->wishlistItemRepository->create([
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
        $item = $this->wishlistItemRepository->findOwnedByCustomer($id, $customer->id);

        if (! $item) {
            abort(404);
        }

        $this->wishlistItemRepository->delete($item);
    }

    public function clear(Customer $customer): void
    {
        $this->wishlistItemRepository->deleteByCustomer($customer->id);
    }
}
