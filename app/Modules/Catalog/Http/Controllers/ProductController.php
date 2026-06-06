<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Http\Requests\StoreProductRequest;
use App\Modules\Catalog\Http\Requests\UpdateProductRequest;
use App\Modules\Catalog\Http\Resources\ProductResource;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Services\MediaService;
use App\Modules\Catalog\Services\ProductExportService;
use App\Modules\Catalog\Services\ProductImportService;
use App\Modules\Catalog\Services\ProductService;
use App\Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * RESTful API controller for product management plus import/export.
 *
 * Most business logic is delegated to ProductService, keeping this
 * controller focused on HTTP concerns: validation, response formatting,
 * and store-scoping via the StoreScope trait.
 */
class ProductController extends Controller
{
    use ApiResponse, \App\Modules\Core\Traits\StoreScope;

    public function __construct(
        private readonly ProductService $productService,
        private readonly ProductImportService $importService,
        private readonly ProductExportService $exportService,
        private readonly MediaService $mediaService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $products = Product::with(['category', 'supplier', 'variants'])
            ->when(true, fn ($q) => $this->scopeByStore($q))
            ->when(request('category_id'), fn ($q) => $q->where('category_id', request('category_id')))
            ->when(request('search'), fn ($q) => $q->whereRaw('LOWER(name) LIKE LOWER(?)', ['%'.request('search').'%']))
            ->orderBy('name')
            ->paginate(20);

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->createProduct(
            $this->mergeStoreId($request->validated()),
        );

        return (new ProductResource($product->load(['category', 'variants'])))->response()->setStatusCode(201);
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load(['category', 'variants']));
    }

    /**
     * Update the product and optionally sync its variants.
     *
     * Variant sync can return an error if any variant slated for deletion
     * still has order history — in that case we still apply the product-level
     * changes but reject the variant deletions with a descriptive message.
     */
    public function update(UpdateProductRequest $request, Product $product): ProductResource|JsonResponse
    {
        $this->productService->updateProduct($product, $request->validated());

        if ($request->has('variants')) {
            $error = $this->productService->syncVariants($product, $request->variants);
            if ($error) {
                return $this->respondError($error);
            }
        }

        return new ProductResource($product->load(['category', 'variants']));
    }

    /**
     * Stream the full product catalog as a CSV download.
     */
    public function exportCsv(): Response
    {
        $csv = $this->exportService->exportToCsv();

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="products.csv"',
        ]);
    }

    /**
     * Import products from an uploaded CSV file.
     *
     * Accepts .csv and .txt extensions (Excel often saves CSVs as .txt).
     * The import service handles validation and reports per-row errors.
     */
    public function importCsv(Request $request): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $result = $this->importService->importFromFile($request->file('file'));

        return $this->respond($result);
    }

    /**
     * Render a PDF label sheet for all variants of a product.
     */
    public function labels(Product $product): View
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

    /**
     * Delete a product only if it has no order history.
     */
    public function destroy(Product $product): JsonResponse
    {
        $error = $this->productService->canDelete($product);

        if ($error) {
            return $this->respondError($error);
        }

        $product->delete();

        return $this->respondMessage('Product deleted.');
    }
}
