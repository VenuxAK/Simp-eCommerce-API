<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DiscountResource;
use App\Models\Discount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DiscountController extends Controller
{
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

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'numeric', 'min:0'],
            'applies_to' => ['required', 'in:all,category,product'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['boolean'],
        ]);

        $discount = Discount::create($data);
        return new DiscountResource($discount)->response()->setStatusCode(201);
    }

    public function show(Discount $discount): DiscountResource
    {
        return new DiscountResource($discount);
    }

    public function update(Request $request, Discount $discount): DiscountResource
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'in:percentage,fixed'],
            'value' => ['sometimes', 'numeric', 'min:0'],
            'applies_to' => ['sometimes', 'in:all,category,product'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['boolean'],
        ]);

        $discount->update($data);
        return new DiscountResource($discount);
    }

    public function destroy(Discount $discount): JsonResponse
    {
        $discount->delete();
        return response()->json(['message' => 'Discount deleted.']);
    }
}
