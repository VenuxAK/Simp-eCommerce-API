<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Http\Requests\StoreCategoryRequest;
use App\Modules\Catalog\Http\Requests\UpdateCategoryRequest;
use App\Modules\Catalog\Http\Resources\CategoryResource;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Repositories\CategoryRepository;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Core\Traits\StoreScope;
use Illuminate\Http\JsonResponse;
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
        ]));

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
        ]);

        return new CategoryResource($category);
    }

    /**
     * Only allow deletion if no products reference this category.
     */
    public function destroy(Category $category): JsonResponse
    {
        $productCount = $this->categoryRepo->getProductCount($category->id);

        if ($productCount > 0) {
            return $this->respondError("Cannot delete category: {$productCount} product(s) are linked to it.");
        }

        $this->categoryRepo->delete($category);

        return $this->respondMessage('Category deleted.');
    }
}
