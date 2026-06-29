<?php

namespace App\Modules\ECommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\ECommerce\Http\Requests\AddCartItemRequest;
use App\Modules\ECommerce\Http\Requests\UpdateCartItemRequest;
use App\Modules\ECommerce\Http\Requests\SyncCartRequest;
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

    public function add(AddCartItemRequest $request): JsonResponse
    {
        try {
            $item = $this->cartService->addItem(
                $request->user(), $request->product_variant_id, $request->quantity,
            );
        } catch (HttpException $e) {
            return $this->respondError($e->getMessage(), $e->getStatusCode());
        }

        return $this->respond(new CartItemResource($item->load('variant.product')))->setStatusCode(201);
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem): JsonResponse
    {
        if ($cartItem->customer_id !== $request->user()->id) {
            abort(403, 'Unauthorized.');
        }

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

    public function sync(SyncCartRequest $request): JsonResponse
    {
        $items = $this->cartService->syncCart($request->user(), $request->validated()['items']);

        return $this->respond(CartItemResource::collection($items));
    }
}
