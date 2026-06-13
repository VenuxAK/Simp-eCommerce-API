<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Http\Requests\StoreBrandRequest;
use App\Modules\Catalog\Http\Requests\UpdateBrandRequest;
use App\Modules\Catalog\Http\Resources\BrandResource;
use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Repositories\BrandRepository;
use App\Modules\Catalog\Services\StorefrontCacheService;
use App\Modules\Catalog\Services\MediaService;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Core\Traits\StoreScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    use ApiResponse, StoreScope;

    public function __construct(
        private readonly BrandRepository $brandRepo,
        private readonly StorefrontCacheService $storefrontCache,
        private readonly MediaService $mediaService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $brands = $this->brandRepo->paginateByStore(
            storeId: $this->resolveStoreId(),
        );

        return BrandResource::collection($brands);
    }

    public function store(StoreBrandRequest $request): JsonResponse
    {
        $brand = $this->brandRepo->create($this->mergeStoreId([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'logo' => $request->logo,
        ]));

        $this->storefrontCache->invalidateStore($this->resolveStoreId());

        return (new BrandResource($brand))->response()->setStatusCode(201);
    }

    public function show(Brand $brand): BrandResource
    {
        return new BrandResource($brand);
    }

    public function update(UpdateBrandRequest $request, Brand $brand): BrandResource
    {
        $this->brandRepo->update($brand, [
            'name' => $request->name ?? $brand->name,
            'slug' => $request->name ? Str::slug($request->name) : $brand->slug,
            'logo' => $request->has('logo') ? $request->logo : $brand->logo,
        ]);

        $this->storefrontCache->invalidateStore($this->resolveStoreId());

        return new BrandResource($brand);
    }

    public function uploadLogo(Request $request, Brand $brand): JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $this->mediaService->uploadImage($brand, $request->file('logo'), 'logo');

        return $this->respond(new BrandResource($brand));
    }

    public function destroy(Brand $brand): JsonResponse
    {
        $productCount = $this->brandRepo->getProductCount($brand->id);

        if ($productCount > 0) {
            return $this->respondError(__('messages.catalog.brand_delete_blocked', ['count' => $productCount]));
        }

        $this->brandRepo->delete($brand);

        $this->storefrontCache->invalidateStore($this->resolveStoreId());

        return $this->respondMessage('Brand deleted.');
    }
}
