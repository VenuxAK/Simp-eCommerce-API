<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Http\Resources\ProductResource;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Store\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StorefrontController extends Controller
{
    public function products(): AnonymousResourceCollection
    {
        $store = app('current_store');

        $products = Product::where('store_id', $store->id)
            ->with(['category', 'variants'])
            ->when(request('category_id'), fn($q) => $q->where('category_id', request('category_id')))
            ->when(request('search'), fn($q) => $q->whereRaw('LOWER(name) LIKE LOWER(?)', ['%' . request('search') . '%']))
            ->orderBy('name')
            ->paginate(20);

        return ProductResource::collection($products);
    }

    public function product(string $slug): ProductResource
    {
        $store = app('current_store');

        $product = Product::where('store_id', $store->id)
            ->where('slug', $slug)
            ->with(['category', 'variants'])
            ->firstOrFail();

        return new ProductResource($product);
    }

    public function categories(): JsonResponse
    {
        $store = app('current_store');

        $categories = Category::where('store_id', $store->id)
            ->withCount('products')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $categories]);
    }

    public function settings(): JsonResponse
    {
        $store = app('current_store');

        return response()->json([
            'data' => [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'description' => $store->description,
                'logo' => $store->logo ? asset('storage/' . $store->logo) : null,
                'phone' => $store->phone,
                'email' => $store->email,
                'is_active' => $store->is_active,
                'settings' => $store->settings,
                'products_count' => Product::where('store_id', $store->id)->count(),
            ],
        ]);
    }
}
