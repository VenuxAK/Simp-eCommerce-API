<?php

namespace App\Modules\ECommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product' => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'base_price' => $this->product->base_price,
                'image' => $this->product->image,
                'category' => $this->product->category?->name,
                'variants' => $this->product->variants->map(fn($v) => [
                    'id' => $v->id,
                    'sku' => $v->sku,
                    'size' => $v->size,
                    'color' => $v->color,
                    'price_adjustment' => $v->price_adjustment,
                    'stock_quantity' => $v->stock_quantity,
                ]),
            ],
            'created_at' => $this->created_at,
        ];
    }
}
