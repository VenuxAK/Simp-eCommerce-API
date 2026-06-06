<?php

namespace App\Modules\Promotion\Services;

use App\Modules\Core\Enums\DiscountScope;
use App\Modules\Core\Enums\DiscountType;
use App\Modules\Promotion\Models\Discount;
use App\Modules\Promotion\Repositories\DiscountRepository;

/**
 * Business logic for Discount operations.
 */
class DiscountService
{
    public function __construct(
        private readonly DiscountRepository $discountRepository,
    ) {}

    /**
     * Calculate discount amount and return a human-readable label.
     *
     * Supports percentage and fixed discounts scoped to all items,
     * a specific category, or a specific product.
     *
     * @return array{0: float, 1: string} [discountAmount, discountLabel]
     */
    public function apply(?int $discountId, array $orderItems, float $totalAmount): array
    {
        $discountAmount = 0;
        $discountLabel = '';

        if (! $discountId) {
            return [$discountAmount, $discountLabel];
        }

        $discount = $this->discountRepository->find($discountId);
        if (! $discount || ! $discount->is_active) {
            return [$discountAmount, $discountLabel];
        }

        // Sum the subtotals of eligible items based on discount scope.
        $discountableTotal = match ($discount->applies_to) {
            DiscountScope::All => $totalAmount,
            DiscountScope::Category => collect($orderItems)
                ->filter(fn ($item) => $item['variant']->product->category_id === $discount->category_id)
                ->sum('subtotal'),
            DiscountScope::Product => collect($orderItems)
                ->filter(fn ($item) => $item['variant']->product_id === $discount->product_id)
                ->sum('subtotal'),
            default => 0,
        };

        if ($discount->type === DiscountType::Percentage) {
            $discountAmount = round($discountableTotal * $discount->value / 100, 2);
            $discountLabel = "{$discount->name} ({$discount->value}%)";
        } else {
            // Fixed discount capped at the eligible total.
            $discountAmount = min($discount->value, $discountableTotal);
            $discountLabel = "{$discount->name} (-{$discount->value} Ks)";
        }

        return [$discountAmount, $discountLabel];
    }
}
