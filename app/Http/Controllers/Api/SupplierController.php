<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\ApiResponse;
use App\Http\Requests\Api\StoreSupplierRequest;
use App\Http\Requests\Api\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupplierController extends Controller
{
    use ApiResponse;
    public function index(): AnonymousResourceCollection
    {
        $suppliers = Supplier::withCount('products')->orderBy('name')->paginate(20);
        return SupplierResource::collection($suppliers);
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $supplier = Supplier::create($request->validated());
        return new SupplierResource($supplier)->response()->setStatusCode(201);
    }

    public function show(Supplier $supplier): SupplierResource
    {
        return new SupplierResource($supplier->loadCount('products'));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): SupplierResource
    {
        $supplier->update($request->validated());
        return new SupplierResource($supplier);
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $productCount = $supplier->products()->count();
        if ($productCount > 0) {
            return $this->respondError("Cannot delete supplier with {$productCount} product(s).");
        }
        $supplier->delete();
        return $this->respondMessage('Supplier deleted.');
    }
}
