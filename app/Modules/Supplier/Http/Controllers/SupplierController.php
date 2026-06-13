<?php

namespace App\Modules\Supplier\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Core\Traits\StoreScope;
use App\Modules\Supplier\Http\Requests\StoreSupplierRequest;
use App\Modules\Supplier\Http\Requests\UpdateSupplierRequest;
use App\Modules\Supplier\Http\Resources\SupplierResource;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\Supplier\Repositories\SupplierRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Handles Supplier-related API requests.
 */
class SupplierController extends Controller
{
    use ApiResponse, StoreScope;

    public function __construct(
        private readonly SupplierRepository $supplierRepo,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $suppliers = $this->supplierRepo->paginateByStore(
            storeId: $this->resolveStoreId(),
        );

        return SupplierResource::collection($suppliers);
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $supplier = $this->supplierRepo->create($this->mergeStoreId($request->validated()));

        return (new SupplierResource($supplier))->response()->setStatusCode(201);
    }

    public function show(Supplier $supplier): SupplierResource
    {
        return new SupplierResource(
            $this->supplierRepo->findWithProductCount($supplier->id),
        );
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): SupplierResource
    {
        $this->supplierRepo->update($supplier, $request->validated());

        return new SupplierResource($supplier);
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $productCount = $this->supplierRepo->getProductCount($supplier->id);
        if ($productCount > 0) {
            return $this->respondError(__('messages.supplier.delete_blocked', ['count' => $productCount]));
        }
        $this->supplierRepo->delete($supplier);

        return $this->respondMessage('Supplier deleted.');
    }
}
