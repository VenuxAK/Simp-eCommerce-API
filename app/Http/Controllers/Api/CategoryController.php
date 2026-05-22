<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Http\Requests\Api\StoreCategoryRequest;
use App\Http\Requests\Api\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

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
            'slug' => Str::slug($request->name),
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
            'slug' => $request->name ? Str::slug($request->name) : $category->slug,
            'description' => $request->description,
        ]);

        return new CategoryResource($category);
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return $this->respondMessage('Category deleted.');
    }
}
