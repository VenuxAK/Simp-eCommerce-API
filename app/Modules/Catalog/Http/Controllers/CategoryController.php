<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Http\Requests\StoreCategoryRequest;
use App\Modules\Catalog\Http\Requests\UpdateCategoryRequest;
use App\Modules\Catalog\Http\Resources\CategoryResource;
use App\Modules\Catalog\Models\Category;
use App\Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Handles Category-related API requests.
 */
class CategoryController extends Controller
{
    use ApiResponse;

    public function index(): AnonymousResourceCollection
    {
        $categories = Category::withCount('products')->orderBy('name')->paginate(20);

        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create([
            'name' => $request->name,
            'slug' => \Illuminate\Support\Str::slug($request->name),
            'description' => $request->description,
        ]);

        return new CategoryResource($category)->response()->setStatusCode(201);
    }

    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category->loadCount('products'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $category->update([
            'name' => $request->name ?? $category->name,
            'slug' => $request->name ? \Illuminate\Support\Str::slug($request->name) : $category->slug,
            'description' => $request->description ?? $category->description,
        ]);

        return new CategoryResource($category);
    }

    public function destroy(Category $category): JsonResponse
    {
        $productCount = $category->products()->count();

        if ($productCount > 0) {
            return $this->respondError("Cannot delete category: {$productCount} product(s) are linked to it.");
        }

        $category->delete();

        return $this->respondMessage('Category deleted.');
    }
}
