<?php

namespace App\Modules\ECommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\ECommerce\Http\Resources\CartItemResource;
use App\Modules\ECommerce\Models\CartItem;
use App\Modules\ECommerce\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Shopping cart persisted on the server.
 *
 * Stock validation runs on add/update so the customer knows
 * immediately if a variant is out of stock.
 */
class CartController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CartService $cartService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return CartItemResource::collection(
            $this->cartService->getItems($request->user()),
        );
    }

    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $item = $this->cartService->addItem(
                $request->user(), $request->product_variant_id, $request->quantity,
            );
        } catch (HttpException $e) {
            return $this->respondError($e->getMessage(), $e->getStatusCode());
        }

        return $this->respond(new CartItemResource($item->load('variant.product')))->setStatusCode(201);
    }

    public function update(Request $request, CartItem $cartItem): JsonResponse
    {
        if ($cartItem->customer_id !== $request->user()->id) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $this->cartService->updateItem($cartItem, $request->quantity);
        } catch (HttpException $e) {
            return $this->respondError($e->getMessage(), $e->getStatusCode());
        }

        return $this->respond(new CartItemResource($cartItem->load('variant.product')));
    }

    public function remove(Request $request, CartItem $cartItem): JsonResponse
    {
        if ($cartItem->customer_id !== $request->user()->id) {
            abort(403, 'Unauthorized.');
        }

        $this->cartService->removeItem($cartItem);

        return $this->respondMessage('Item removed from cart.');
    }

    public function clear(Request $request): JsonResponse
    {
        $this->cartService->clearCart($request->user());

        return $this->respondMessage('Cart cleared.');
    }
}
