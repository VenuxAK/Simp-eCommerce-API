<?php

namespace App\Modules\Promotion\Services;

use App\Modules\Promotion\Models\Discount;

class DiscountService
{
    public function apply(?int $discountId, array $orderItems, float $totalAmount): array
    {
        $discountAmount = 0;
        $discountLabel = '';

        if (!$discountId) {
            return [$discountAmount, $discountLabel];
        }

        $discount = Discount::find($discountId);
        if (!$discount || !$discount->is_active) {
            return [$discountAmount, $discountLabel];
        }

        $discountableTotal = match ($discount->applies_to) {
            'all' => $totalAmount,
            'category' => collect($orderItems)
                ->filter(fn($item) => $item['variant']->product->category_id === $discount->category_id)
                ->sum('subtotal'),
            'product' => collect($orderItems)
                ->filter(fn($item) => $item['variant']->product_id === $discount->product_id)
                ->sum('subtotal'),
            default => 0,
        };

        if ($discount->type === 'percentage') {
            $discountAmount = round($discountableTotal * $discount->value / 100, 2);
            $discountLabel = "{$discount->name} ({$discount->value}%)";
        } else {
            $discountAmount = min($discount->value, $discountableTotal);
            $discountLabel = "{$discount->name} (-{$discount->value} Ks)";
        }

        return [$discountAmount, $discountLabel];
    }
}
