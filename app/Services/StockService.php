<?php

namespace App\Services;

use App\Modules\Catalog\Models\ProductVariant;
use App\Models\StockMovement;

class StockService
{
    public function recordMovement(
        ProductVariant $variant,
        int $quantity,
        string $reason,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): StockMovement {
        return StockMovement::create([
            'product_variant_id' => $variant->id,
            'quantity_change' => $quantity,
            'reason' => $reason,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'user_id' => request()->user()->id,
        ]);
    }
}
