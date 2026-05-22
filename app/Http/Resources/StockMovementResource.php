<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'variant' => new ProductVariantResource($this->whenLoaded('variant')),
            'quantity_change' => $this->quantity_change,
            'reason' => $this->reason,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
