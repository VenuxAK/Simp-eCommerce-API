<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Http\Resources\ProductResource;
use App\Modules\Catalog\Services\StorefrontService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Public (unauthenticated) API endpoints for the storefront.
 *
 * Every request resolves the current store from the request context
 * (subdomain, header, or path prefix) via the current_store macro
 * registered in the service container, so no endpoint needs to
 * accept an explicit store identifier from the client.
 */
class StorefrontController extends Controller
{
    public function __construct(
        private readonly StorefrontService $storefrontService,
    ) {}

    /**
     * Paginated product listing filtered by availability and search.
     */
    public function products(Request $request): AnonymousResourceCollection
    {
        $store = app('current_store');

        return ProductResource::collection(
            $this->storefrontService->products(
                $store, $request->input('category_id'), $request->input('search'),
            ),
        );
    }

    /**
     * Single product detail page by slug.
     */
    public function product(string $slug): ProductResource
    {
        return new ProductResource(
            $this->storefrontService->product(app('current_store'), $slug),
        );
    }

    /**
     * All categories for the current store with product counts.
     */
    public function categories(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->storefrontService->categories(app('current_store')),
        ]);
    }

    /**
     * Store metadata and configuration for the frontend.
     */
    public function settings(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->storefrontService->settings(app('current_store')),
        ]);
    }
}
