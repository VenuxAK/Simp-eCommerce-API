<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'sku' => $this->sku,
            'size' => $this->size,
            'color' => $this->color,
            'image' => $this->image,
            'image_url' => $this->image ? Storage::url($this->image) : null,
            'price_adjustment' => (float) $this->price_adjustment,
            'purchase_price' => $this->purchase_price ? (float) $this->purchase_price : null,
            'stock_quantity' => $this->stock_quantity,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
