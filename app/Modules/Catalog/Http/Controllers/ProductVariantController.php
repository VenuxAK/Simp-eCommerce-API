<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Http\Requests\UpdateStockRequest;
use App\Modules\Catalog\Http\Resources\ProductResource;
use App\Modules\Catalog\Http\Resources\ProductVariantResource;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Catalog\Services\MediaService;
use App\Modules\Inventory\Services\StockService;
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
            'product' => new ProductResource($variant->product),
        ]);
    }
}
