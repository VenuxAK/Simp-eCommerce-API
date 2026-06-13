<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Http\Requests\UpdateStockRequest;
use App\Modules\Catalog\Http\Requests\UploadImageRequest;
use App\Modules\Catalog\Http\Resources\ProductResource;
use App\Modules\Catalog\Http\Resources\ProductVariantResource;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Repositories\ProductVariantRepository;
use App\Modules\Catalog\Services\MediaService;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Inventory\Services\StockService;
use Illuminate\Http\JsonResponse;

/**
 * API controller for variant-level operations (stock, images, SKU lookup).
 *
 * These endpoints operate directly on ProductVariant rather than going
 * through the parent Product, which is useful for POS/barcode workflows
 * where the user scans a SKU and expects immediate stock feedback.
 */
class ProductVariantController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly StockService $stockService,
        private readonly MediaService $mediaService,
        private readonly ProductVariantRepository $variantRepo,
    ) {}

    /**
     * Overwrite stock quantity and record the resulting movement.
     *
     * The diff is computed on the server to prevent the client from
     * lying about the direction or magnitude of the change.
     */
    public function updateStock(UpdateStockRequest $request, ProductVariant $variant): ProductVariantResource
    {
        $oldStock = $variant->stock_quantity;
        $data = ['stock_quantity' => $request->quantity];
        if ($request->has('low_stock_threshold')) {
            $data['low_stock_threshold'] = $request->low_stock_threshold;
        }
        $variant->update($data);
        $diff = $request->quantity - $oldStock;

        if ($diff !== 0) {
            $this->stockService->recordMovement($variant, $diff, 'adjustment');
        }

        return new ProductVariantResource($variant);
    }

    public function uploadImage(UploadImageRequest $request, ProductVariant $variant): JsonResponse
    {

        $this->mediaService->uploadImage($variant, $request->file('image'));

        return $this->respond(new ProductVariantResource($variant));
    }

    /**
     * Look up a variant by SKU and return it together with its parent product.
     *
     * This endpoint is used by barcode scanners and POS integrations
     * where only the SKU string is available at scan time.
     */
    public function bySku(string $sku): JsonResponse
    {
        $variant = $this->variantRepo->findBySku($sku);

        if (! $variant) {
            return $this->respondError('Variant not found for the given SKU.', 404);
        }

        return $this->respond([
            'variant' => new ProductVariantResource($variant),
            'product' => new ProductResource($variant->product),
        ]);
    }
}
