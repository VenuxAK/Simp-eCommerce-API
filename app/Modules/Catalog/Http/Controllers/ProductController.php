<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sales\Models\OrderItem;
use App\Modules\Catalog\Http\Requests\StoreProductRequest;
use App\Modules\Catalog\Http\Requests\UpdateProductRequest;
use App\Modules\Catalog\Http\Resources\ProductResource;
use App\Modules\Catalog\Models\Product;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Catalog\Services\MediaService;
use App\Modules\Catalog\Services\ProductExportService;
use App\Modules\Catalog\Services\ProductImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

/**
 * Handles Product-related API requests.
 */
class ProductController extends Controller
{
    use ApiResponse, \App\Modules\Core\Traits\StoreScope;

    public function __construct(
        private readonly ProductImportService $importService,
        private readonly ProductExportService $exportService,
        private readonly MediaService $mediaService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $products = Product::with(['category', 'supplier', 'variants'])
            ->when(true, fn($q) => $this->scopeByStore($q))
            ->when(request('category_id'), fn($q) => $q->where('category_id', request('category_id')))
            ->when(request('search'), fn($q) => $q->whereRaw('LOWER(name) LIKE LOWER(?)', ['%' . request('search') . '%']))
            ->orderBy('name')
            ->paginate(20);

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($this->mergeStoreId([
            'category_id' => $request->category_id,
            'supplier_id' => $request->supplier_id ?? null,
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . Str::random(8),
            'description' => $request->description,
            'base_price' => $request->base_price,
            'image' => $request->image,
        ]));

        foreach ($request->variants as $variantData) {
            $product->variants()->create([
                'sku' => $variantData['sku'],
                'size' => $variantData['size'] ?? null,
                'color' => $variantData['color'] ?? null,
                'price_adjustment' => $variantData['price_adjustment'] ?? 0,
                'stock_quantity' => $variantData['stock_quantity'] ?? 0,
            ]);
        }

        return new ProductResource($product->load(['category', 'variants']))->response()->setStatusCode(201);
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load(['category', 'variants']));
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource|JsonResponse
    {
        $product->update([
            'category_id' => $request->category_id ?? $product->category_id,
            'supplier_id' => $request->supplier_id ?? $product->supplier_id,
            'name' => $request->name ?? $product->name,
            'slug' => $request->name ? Str::slug($request->name) . '-' . Str::random(8) : $product->slug,
            'description' => $request->description ?? $product->description,
            'base_price' => $request->base_price ?? $product->base_price,
            'image' => $request->image ?? $product->image,
        ]);

        if ($request->has('variants')) {
            $existingIds = $product->variants()->pluck('id')->toArray();
            $updatedIds = [];

            foreach ($request->variants as $variantData) {
                if (isset($variantData['id']) && in_array($variantData['id'], $existingIds)) {
                    $product->variants()->where('id', $variantData['id'])->update([
                        'sku' => $variantData['sku'],
                        'size' => $variantData['size'] ?? null,
                        'color' => $variantData['color'] ?? null,
                        'price_adjustment' => $variantData['price_adjustment'] ?? 0,
                        'stock_quantity' => $variantData['stock_quantity'] ?? 0,
                    ]);
                    $updatedIds[] = $variantData['id'];
                } else {
                    $new = $product->variants()->create([
                        'sku' => $variantData['sku'],
                        'size' => $variantData['size'] ?? null,
                        'color' => $variantData['color'] ?? null,
                        'price_adjustment' => $variantData['price_adjustment'] ?? 0,
                        'stock_quantity' => $variantData['stock_quantity'] ?? 0,
                    ]);
                    $updatedIds[] = $new->id;
                }
            }

            $toDelete = array_diff($existingIds, $updatedIds);
            if (!empty($toDelete)) {
                $variantOrderCount = OrderItem::whereIn('product_variant_id', $toDelete)->count();
                if ($variantOrderCount > 0) {
                    return $this->respondError("Cannot delete {$variantOrderCount} variant(s) with existing order history. Remove them from the payload to keep them.");
                }
                $product->variants()->whereIn('id', $toDelete)->delete();
            }
        }

        return new ProductResource($product->load(['category', 'variants']));
    }

    public function exportCsv(): \Illuminate\Http\Response
    {
        $csv = $this->exportService->exportToCsv();

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="products.csv"',
        ]);
    }

    public function importCsv(Request $request): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $result = $this->importService->importFromFile($request->file('file'));

        return $this->respond($result);
    }

    public function labels(Product $product): \Illuminate\View\View
    {
        $product->load('variants');
        return view('pdf.label', ['variants' => $product->variants]);
    }

    public function uploadImage(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $this->mediaService->uploadImage($product, $request->file('image'));

        return $this->respond(new ProductResource($product->load(['category', 'variants'])));
    }

    public function destroy(Product $product): JsonResponse
    {
        $orderCount = OrderItem::whereIn('product_variant_id', $product->variants()->pluck('id'))->count();

        if ($orderCount > 0) {
            return $this->respondError("Cannot delete product: {$orderCount} order item(s) reference it.");
        }

        $product->delete();

        return $this->respondMessage('Product deleted.');
    }
}
