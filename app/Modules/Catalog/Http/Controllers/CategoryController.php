<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Http\Requests\StoreCategoryRequest;
use App\Modules\Catalog\Http\Requests\UpdateCategoryRequest;
use App\Modules\Catalog\Http\Resources\CategoryResource;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Repositories\CategoryRepository;
use App\Modules\Catalog\Services\StorefrontCacheService;
use App\Modules\Catalog\Services\MediaService;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Core\Traits\StoreScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

/**
 * RESTful API controller for category CRUD.
 *
 * Categories are store-scoped and include a computed product count
 * to support navigation UI without an extra query on the client.
 */
class CategoryController extends Controller
{
    use ApiResponse, StoreScope;

    public function __construct(
        private readonly CategoryRepository $categoryRepo,
        private readonly StorefrontCacheService $storefrontCache,
        private readonly MediaService $mediaService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $categories = $this->categoryRepo->paginateByStore(
            storeId: $this->resolveStoreId(),
        );

        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryRepo->create($this->mergeStoreId([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'parent_id' => $request->parent_id,
        ]));

        $this->storefrontCache->invalidateStore($this->resolveStoreId());

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    public function show(Category $category): CategoryResource
    {
        return new CategoryResource(
            $this->categoryRepo->findWithProductCount($category->id),
        );
    }

    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        // Regenerate the slug whenever the name changes to keep them in sync.
        $this->categoryRepo->update($category, [
            'name' => $request->name ?? $category->name,
            'slug' => $request->name ? Str::slug($request->name) : $category->slug,
            'description' => $request->description ?? $category->description,
            'parent_id' => $request->has('parent_id') ? $request->parent_id : $category->parent_id,
        ]);

        $this->storefrontCache->invalidateStore($this->resolveStoreId());

        return new CategoryResource($category);
    }

    public function uploadImage(Request $request, Category $category): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $this->mediaService->uploadImage($category, $request->file('image'), 'image');

        return $this->respond(new CategoryResource($category));
    }

    /**
     * Only allow deletion if no products reference this category.
     */
    public function destroy(Category $category): JsonResponse
    {
        $productCount = $this->categoryRepo->getProductCount($category->id);

        if ($productCount > 0) {
            return $this->respondError(__('messages.catalog.category_delete_blocked', ['count' => $productCount]));
        }

        $childCount = Category::where('parent_id', $category->id)->count();
        if ($childCount > 0) {
            return $this->respondError(__('messages.catalog.category_has_children', ['count' => $childCount]));
        }

        $this->categoryRepo->delete($category);

        $this->storefrontCache->invalidateStore($this->resolveStoreId());

        return $this->respondMessage('Category deleted.');
    }
}
