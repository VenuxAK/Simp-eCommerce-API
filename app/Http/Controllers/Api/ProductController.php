<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreProductRequest;
use App\Http\Requests\Api\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $products = Product::with(['category', 'supplier', 'variants'])
            ->when(request('category_id'), fn($q) => $q->where('category_id', request('category_id')))
            ->when(request('search'), fn($q) => $q->where('name', 'like', '%' . request('search') . '%'))
            ->orderBy('name')
            ->paginate(20);

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create([
            'category_id' => $request->category_id,
            'supplier_id' => $request->supplier_id ?? null,
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . Str::random(8),
            'description' => $request->description,
            'base_price' => $request->base_price,
            'image' => $request->image,
        ]);

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

    public function update(UpdateProductRequest $request, Product $product): ProductResource
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
                    return response()->json([
                        'message' => "Cannot delete {$variantOrderCount} variant(s) with existing order history. Remove them from the payload to keep them.",
                    ], 422);
                }
                $product->variants()->whereIn('id', $toDelete)->delete();
            }
        }

        return new ProductResource($product->load(['category', 'variants']));
    }

    public function exportCsv(): \Illuminate\Http\Response
    {
        $products = Product::with(['category', 'variants', 'supplier'])->orderBy('name')->get();
        $csv = "category,name,sku,size,color,base_price,price_adjustment,purchase_price,stock,supplier\n";

        foreach ($products as $product) {
            foreach ($product->variants as $variant) {
                $csv .= implode(',', [
                    '"' . ($product->category?->name ?? '') . '"',
                    '"' . $product->name . '"',
                    '"' . $variant->sku . '"',
                    '"' . ($variant->size ?? '') . '"',
                    '"' . ($variant->color ?? '') . '"',
                    $product->base_price,
                    $variant->price_adjustment,
                    $variant->purchase_price ?? '',
                    $variant->stock_quantity,
                    '"' . ($product->supplier?->name ?? '') . '"',
                ]) . "\n";
            }
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="products.csv"',
        ]);
    }

    public function importCsv(Request $request): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $handle = fopen($request->file('file')->getPathname(), 'r');
        $header = fgetcsv($handle);
        $created = 0;
        $errors = [];

        DB::transaction(function () use ($handle, $header, &$created, &$errors) {
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($header, $row);

                $rowValidator = \Illuminate\Support\Facades\Validator::make($data, [
                    'name' => ['required', 'string', 'max:255'],
                    'sku' => ['required', 'string', 'max:100'],
                    'base_price' => ['nullable', 'numeric', 'min:0'],
                    'price_adjustment' => ['nullable', 'numeric', 'min:0'],
                    'stock' => ['nullable', 'integer', 'min:0'],
                    'purchase_price' => ['nullable', 'numeric', 'min:0'],
                ]);

                if ($rowValidator->fails()) {
                    $errors[] = "Row " . ($created + 1) . ": " . implode('; ', $rowValidator->errors()->all());
                    continue;
                }

                $category = \App\Models\Category::firstOrCreate(['name' => $data['category'] ?? 'Uncategorized'], ['slug' => Str::slug($data['category'] ?? 'Uncategorized')]);
                $supplier = null;
                if (!empty($data['supplier'])) {
                    $supplier = \App\Models\Supplier::firstOrCreate(['name' => $data['supplier']]);
                }

                try {
                    $product = Product::firstOrCreate(
                        ['name' => $data['name']],
                        [
                            'category_id' => $category->id,
                            'supplier_id' => $supplier?->id,
                            'slug' => Str::slug($data['name']) . '-' . Str::random(8),
                            'base_price' => $data['base_price'] ?? 0,
                        ]
                    );

                    $product->variants()->updateOrCreate(
                        ['sku' => $data['sku']],
                        [
                            'size' => $data['size'] ?? null,
                            'color' => $data['color'] ?? null,
                            'price_adjustment' => $data['price_adjustment'] ?? 0,
                            'purchase_price' => $data['purchase_price'] ?? null,
                            'stock_quantity' => $data['stock'] ?? 0,
                        ]
                    );
                    $created++;
                } catch (\Exception $e) {
                    $errors[] = "Row {$created}: {$e->getMessage()}";
                }
            }
        });

        fclose($handle);
        return response()->json(['created' => $created, 'errors' => $errors]);
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

        if ($product->image) {
            Storage::delete($product->image);
        }

        $path = $request->file('image')->store('products', 'public');
        $product->update(['image' => $path]);

        return response()->json(new ProductResource($product->load(['category', 'variants'])));
    }

    public function destroy(Product $product): JsonResponse
    {
        $orderCount = OrderItem::whereIn('product_variant_id', $product->variants()->pluck('id'))->count();

        if ($orderCount > 0) {
            return response()->json([
                'message' => "Cannot delete product: {$orderCount} order item(s) reference it.",
            ], 422);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully.']);
    }
}
