<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Services\StorefrontCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public (unauthenticated) API endpoints for the storefront.
 *
 * Every request resolves the current store from the request context
 * (subdomain, header, or path prefix) via the current_store macro
 * registered in the service container, so no endpoint needs to
 * accept an explicit store identifier from the client.
 *
 * All read queries are served through StorefrontCacheService which
 * adds a 5-minute cache layer with version-based invalidation.
 * Responses include ETag and Cache-Control headers for HTTP-level caching.
 */
class StorefrontController extends Controller
{
    public function __construct(
        private readonly StorefrontCacheService $storefrontCache,
    ) {}

    /**
     * Paginated product listing filtered by availability, category,
     * brand, price range, search text, and configurable sorting.
     */
    public function products(Request $request): JsonResponse
    {
        $store = app('current_store');

        $result = $this->storefrontCache->products(
            $store,
            $request->input('category_slug'),
            $request->input('search'),
            $request->input('brand_id'), // Can be array or comma-separated
            $request->input('sort_by', 'name'),   // name, price, newest
            $request->input('sort_dir', 'asc'),   // asc, desc
            $request->input('min_price'),          // nullable float
            $request->input('max_price'),          // nullable float
            (int) $request->input('per_page', 20),
            (int) $request->input('page', 1),
        );

        return response()->json($result);
    }

    /**
     * Single product detail page by slug.
     */
    public function product(Request $request, string $slug): JsonResponse
    {
        $productArray = $this->storefrontCache->product(app('current_store'), $slug);

        // We use the ID for the ETag since we no longer have the updated_at timestamp directly on the array, 
        // or we can use md5 of the entire array which is safer.
        $etag = '"' . md5(serialize($productArray)) . '"';

        // Return 304 Not Modified if the client already has this version.
        if ($request->header('If-None-Match') === $etag) {
            return response()->json(null, 304)
                ->header('ETag', $etag);
        }

        return response()->json(['data' => $productArray])
            ->header('ETag', $etag)
            ->header('Cache-Control', 'public, max-age=60, stale-while-revalidate=300');
    }

    /**
     * All categories for the current store with product counts.
     */
    public function categories(Request $request): JsonResponse
    {
        $data = $this->storefrontCache->categories(app('current_store'));

        return $this->withETag(
            response()->json(['data' => $data]),
            $data,
        );
    }

    /**
     * All brands for the current store.
     */
    public function brands(Request $request): JsonResponse
    {
        $data = $this->storefrontCache->brands(app('current_store'));

        return $this->withETag(
            response()->json(['data' => $data]),
            $data,
        );
    }

    /**
     * Store metadata and configuration for the frontend.
     */
    public function settings(Request $request): JsonResponse
    {
        $data = $this->storefrontCache->settings(app('current_store'));

        return $this->withETag(
            response()->json(['data' => $data]),
            $data,
        );
    }

    /**
     * Add ETag and Cache-Control headers to a JSON response.
     *
     * Clients can send If-None-Match on subsequent requests; middleware
     * or reverse proxies (Nginx, Cloudflare) can return 304 Not Modified.
     */
    private function withETag(JsonResponse $response, mixed $data): JsonResponse
    {
        $etag = '"' . md5(serialize($data)) . '"';

        return $response
            ->header('ETag', $etag)
            ->header('Cache-Control', 'public, max-age=60, stale-while-revalidate=300');
    }
}
