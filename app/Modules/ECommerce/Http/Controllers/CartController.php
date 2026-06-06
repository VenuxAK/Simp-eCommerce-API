<?php

namespace App\Modules\ECommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\ECommerce\Http\Resources\CartItemResource;
use App\Modules\ECommerce\Models\CartItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Shopping cart persisted on the server.
 *
 * Stock validation runs on add/update so the customer knows
 * immediately if a variant is out of stock.
 */
class CartController extends Controller
{
    use ApiResponse;

    public function index(Request $request): AnonymousResourceCollection
    {
        $items = CartItem::where('customer_id', $request->user()->id)
            ->with('variant.product')
            ->orderBy('created_at')
            ->get();

        return CartItemResource::collection($items);
    }

    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $variantId = $request->product_variant_id;
        $quantity = $request->quantity;
        $customerId = $request->user()->id;

        // If the same variant is already in the cart, increment quantity.
        $existing = CartItem::where('customer_id', $customerId)
            ->where('product_variant_id', $variantId)
            ->first();

        if ($existing) {
            $newQty = $existing->quantity + $quantity;
            $this->validateStock($variantId, $newQty);
            $existing->update(['quantity' => $newQty]);
            $item = $existing;
        } else {
            $this->validateStock($variantId, $quantity);
            $item = CartItem::create([
                'customer_id' => $customerId,
                'product_variant_id' => $variantId,
                'quantity' => $quantity,
            ]);
        }

        return $this->respond(new CartItemResource($item->load('variant.product')))->setStatusCode(201);
    }

    public function update(Request $request, CartItem $cartItem): JsonResponse
    {
        $this->authorizeOwner($request, $cartItem);

        $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $this->validateStock($cartItem->product_variant_id, $request->quantity);

        $cartItem->update(['quantity' => $request->quantity]);

        return $this->respond(new CartItemResource($cartItem->load('variant.product')));
    }

    public function remove(Request $request, CartItem $cartItem): JsonResponse
    {
        $this->authorizeOwner($request, $cartItem);

        $cartItem->delete();

        return $this->respondMessage('Item removed from cart.');
    }

    public function clear(Request $request): JsonResponse
    {
        CartItem::where('customer_id', $request->user()->id)->delete();

        return $this->respondMessage('Cart cleared.');
    }

    private function authorizeOwner(Request $request, CartItem $cartItem): void
    {
        if ($cartItem->customer_id !== $request->user()->id) {
            abort(403, 'Unauthorized.');
        }
    }

    private function validateStock(int $variantId, int $quantity): void
    {
        $variant = ProductVariant::findOrFail($variantId);

        if ($variant->stock_quantity < $quantity) {
            abort(422, "Insufficient stock for '{$variant->sku}'. Available: {$variant->stock_quantity}.");
        }
    }
}
