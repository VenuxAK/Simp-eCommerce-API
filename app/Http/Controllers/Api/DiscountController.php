<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Http\Requests\Api\StoreDiscountRequest;
use App\Http\Requests\Api\UpdateDiscountRequest;
use App\Http\Resources\DiscountResource;
use App\Models\Discount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DiscountController extends Controller
{
    use ApiResponse;
    public function index(): AnonymousResourceCollection
    {
        $discounts = Discount::orderBy('name')->paginate(20);
        return DiscountResource::collection($discounts);
    }

    public function active(): AnonymousResourceCollection
    {
        $now = now()->toDateString();
        $discounts = Discount::where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->orderBy('name')
            ->get();
        return DiscountResource::collection($discounts);
    }

    public function store(StoreDiscountRequest $request): JsonResponse
    {
        $discount = Discount::create($request->validated());
        return new DiscountResource($discount)->response()->setStatusCode(201);
    }

    public function show(Discount $discount): DiscountResource
    {
        return new DiscountResource($discount);
    }

    public function update(UpdateDiscountRequest $request, Discount $discount): DiscountResource
    {
        $discount->update($request->validated());
        return new DiscountResource($discount);
    }

    public function destroy(Discount $discount): JsonResponse
    {
        $discount->delete();
        return $this->respondMessage('Discount deleted.');
    }
}
