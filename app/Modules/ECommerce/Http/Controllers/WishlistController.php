<?php

namespace App\Modules\ECommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\ECommerce\Http\Resources\WishlistItemResource;
use App\Modules\ECommerce\Models\WishlistItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WishlistController extends Controller
{
    use ApiResponse;

    public function index(Request $request): AnonymousResourceCollection
    {
        $items = WishlistItem::where('customer_id', $request->user()->id)
            ->with('product.category', 'product.variants')
            ->latest()
            ->get();

        return WishlistItemResource::collection($items);
    }

    public function toggle(Request $request): JsonResponse
    {
        $data = $request->validate(['product_id' => 'required|exists:products,id']);
        $customerId = $request->user()->id;

        $existing = WishlistItem::where('customer_id', $customerId)
            ->where('product_id', $data['product_id'])
            ->first();

        if ($existing) {
            $existing->delete();
            return $this->respond(['wishlisted' => false]);
        }

        $item = WishlistItem::create([
            'customer_id' => $customerId,
            'product_id' => $data['product_id'],
        ]);

        return $this->respond([
            'wishlisted' => true,
            'item' => new WishlistItemResource($item->load('product.category', 'product.variants')),
        ]);
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        $item = WishlistItem::where('id', $id)
            ->where('customer_id', $request->user()->id)
            ->firstOrFail();

        $item->delete();

        return $this->respondNoContent();
    }

    public function clear(Request $request): JsonResponse
    {
        WishlistItem::where('customer_id', $request->user()->id)->delete();

        return $this->respondNoContent();
    }
}
