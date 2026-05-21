<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateStockRequest;
use App\Http\Resources\ProductVariantResource;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductVariantController extends Controller
{
    public function updateStock(UpdateStockRequest $request, ProductVariant $variant): ProductVariantResource
    {
        $oldStock = $variant->stock_quantity;
        $variant->update(['stock_quantity' => $request->quantity]);
        $diff = $request->quantity - $oldStock;

        if ($diff !== 0) {
            StockMovement::create([
                'product_variant_id' => $variant->id,
                'quantity_change' => $diff,
                'reason' => 'adjustment',
                'user_id' => request()->user()->id,
            ]);
        }

        return new ProductVariantResource($variant);
    }

    public function uploadImage(Request $request, ProductVariant $variant): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($variant->image) {
            Storage::delete($variant->image);
        }

        $path = $request->file('image')->store('variants', 'public');
        $variant->update(['image' => $path]);

        return response()->json(new ProductVariantResource($variant));
    }

    public function bySku(string $sku): JsonResponse
    {
        $variant = ProductVariant::with('product.category')
            ->where('sku', $sku)
            ->first();

        if (!$variant) {
            return response()->json(['message' => 'Variant not found for SKU: ' . $sku], 404);
        }

        return response()->json([
            'variant' => new ProductVariantResource($variant),
            'product' => new \App\Http\Resources\ProductResource($variant->product),
        ]);
    }
}
