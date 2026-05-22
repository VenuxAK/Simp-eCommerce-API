<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Http\Requests\Api\UpdateStockRequest;
use App\Http\Resources\ProductVariantResource;
use App\Models\ProductVariant;
use App\Services\MediaService;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly StockService $stockService,
        private readonly MediaService $mediaService,
    ) {}

    public function updateStock(UpdateStockRequest $request, ProductVariant $variant): ProductVariantResource
    {
        $oldStock = $variant->stock_quantity;
        $variant->update(['stock_quantity' => $request->quantity]);
        $diff = $request->quantity - $oldStock;

        if ($diff !== 0) {
            $this->stockService->recordMovement($variant, $diff, 'adjustment');
        }

        return new ProductVariantResource($variant);
    }

    public function uploadImage(Request $request, ProductVariant $variant): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $this->mediaService->uploadImage($variant, $request->file('image'));

        return $this->respond(new ProductVariantResource($variant));
    }

    public function bySku(string $sku): JsonResponse
    {
        $variant = ProductVariant::with('product.category')
            ->where('sku', $sku)
            ->first();

        if (!$variant) {
            return $this->respondError('Variant not found for the given SKU.', 404);
        }

        return $this->respond([
            'variant' => new ProductVariantResource($variant),
            'product' => new \App\Http\Resources\ProductResource($variant->product),
        ]);
    }
}
