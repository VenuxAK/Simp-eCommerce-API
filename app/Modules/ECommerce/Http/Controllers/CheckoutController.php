<?php

namespace App\Modules\ECommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Customer\Models\Address;
use App\Modules\ECommerce\Http\Requests\PlaceOrderRequest;
use App\Modules\ECommerce\Models\CartItem;
use App\Modules\ECommerce\Services\OnlineOrderService;
use App\Modules\Sales\Http\Resources\OrderResource;
use App\Modules\Store\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Converts a shopping cart into an order.
 *
 * COD only in this phase; payment gateways are deferred.
 * Stock is deducted immediately at checkout.
 */
class CheckoutController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly OnlineOrderService $orderService,
    ) {}

    public function placeOrder(PlaceOrderRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $customer = $request->user();

            $cartItems = CartItem::where('customer_id', $customer->id)
                ->with('variant.product')
                ->get();

            if ($cartItems->isEmpty()) {
                return $this->respondError('Cart is empty.', 422);
            }

            $address = Address::where('id', $request->address_id)
                ->where('customer_id', $customer->id)
                ->first();

            if (! $address) {
                return $this->respondError('Invalid shipping address.', 422);
            }

            // Double-check stock for every cart item before proceeding.
            foreach ($cartItems as $item) {
                if ($item->variant->stock_quantity < $item->quantity) {
                    return $this->respondError(
                        "Insufficient stock for '{$item->variant->sku}'. Available: {$item->variant->stock_quantity}.", 422);
                }
            }

            // Resolve store from X-Store header or fall back to the middleware-resolved store.
            $storeId = null;
            $storeSlug = $request->header('X-Store');
            if ($storeSlug) {
                $store = Store::where('slug', $storeSlug)->first();
                if ($store) {
                    $storeId = $store->id;
                }
            }
            if (! $storeId && app()->bound('current_store')) {
                $storeId = app('current_store')->id;
            }

            $order = $this->orderService->placeOrder($customer, $cartItems, $address, $request->notes, $storeId);

            return $this->respond([
                'message' => 'Order placed successfully.',
                'order' => new OrderResource($order->load([
                    'items.variant.product', 'shipment.address', 'invoice',
                ])),
            ])->setStatusCode(201);
        });
    }

    public function validateStock(Request $request): JsonResponse
    {
        $items = CartItem::where('customer_id', $request->user()->id)
            ->with('variant')
            ->get()
            ->map(fn ($item) => [
                'cart_item_id' => $item->id,
                'sku' => $item->variant->sku,
                'requested' => $item->quantity,
                'available' => $item->variant->stock_quantity,
                'insufficient' => $item->variant->stock_quantity < $item->quantity,
            ]);

        return $this->respond(['items' => $items]);
    }
}
