<?php

namespace App\Modules\Promotion\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Core\Traits\StoreScope;
use App\Modules\Promotion\Http\Requests\StoreDiscountRequest;
use App\Modules\Promotion\Http\Requests\UpdateDiscountRequest;
use App\Modules\Promotion\Http\Resources\DiscountResource;
use App\Modules\Promotion\Models\Discount;
use App\Modules\Promotion\Repositories\DiscountRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Handles Discount-related API requests.
 */
class DiscountController extends Controller
{
    use ApiResponse, StoreScope;

    public function __construct(
        private readonly DiscountRepository $discountRepository,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $discounts = $this->discountRepository->paginateByStore($this->resolveStoreId());

        return DiscountResource::collection($discounts);
    }

    public function active(): AnonymousResourceCollection
    {
        return DiscountResource::collection($this->discountRepository->findActive());
    }

    public function store(StoreDiscountRequest $request): JsonResponse
    {
        $discount = $this->discountRepository->create($this->mergeStoreId($request->validated()));

        return (new DiscountResource($discount))->response()->setStatusCode(201);
    }

    public function show(Discount $discount): DiscountResource
    {
        return new DiscountResource($discount);
    }

    public function update(UpdateDiscountRequest $request, Discount $discount): DiscountResource
    {
        $this->discountRepository->update($discount, $request->validated());

        return new DiscountResource($discount);
    }

    public function destroy(Discount $discount): JsonResponse
    {
        $this->discountRepository->delete($discount);

        return $this->respondMessage('Discount deleted.');
    }
}
