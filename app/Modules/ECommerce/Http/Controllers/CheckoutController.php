<?php

namespace App\Modules\ECommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Core\Traits\StoreScope;
use App\Modules\Customer\Models\Address;
use App\Modules\ECommerce\Http\Requests\PlaceOrderRequest;
use App\Modules\ECommerce\Models\CartItem;
use App\Modules\ECommerce\Services\OnlineOrderService;
use App\Modules\Payment\Models\PaymentTransaction;
use App\Modules\Sales\Http\Resources\OrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    use ApiResponse, StoreScope;

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
                return $this->respondError(__('messages.checkout.cart_empty'), 422);
            }

            $address = Address::where('id', $request->address_id)
                ->where('customer_id', $customer->id)
                ->first();

            if (! $address) {
                return $this->respondError(__('messages.checkout.invalid_address'), 422);
            }

            foreach ($cartItems as $item) {
                if ($item->variant->stock_quantity < $item->quantity) {
                    return $this->respondError(
                        __('messages.checkout.insufficient_stock', ['sku' => $item->variant->sku, 'available' => $item->variant->stock_quantity]), 422);
                }
            }

            $order = $this->orderService->placeOrder(
                $customer, $cartItems, $address, $request->notes, $this->resolveStoreId(),
            );

            // Create payment record for gateway payments.
            if ($request->payment_method === 'mmpay') {
                $tx = PaymentTransaction::where('transaction_id', $request->payment_transaction_id)->first();

                $payment = $order->payment()->create([
                    'method' => 'mmpay',
                    'amount' => $order->total_amount,
                    'gateway' => 'mmpay',
                    'transaction_id' => $request->payment_transaction_id,
                    'gateway_status' => $tx?->gateway_status ?? 'pending',
                    'paid_at' => null,
                ]);

                if ($tx) {
                    $tx->update(['payment_id' => $payment->id, 'order_id' => $order->id]);
                }
            } elseif ($request->payment_method === 'stripe') {
                $order->payment()->create([
                    'method' => 'stripe',
                    'amount' => $order->total_amount,
                    'gateway' => 'stripe',
                    'transaction_id' => $request->payment_intent_id,
                    'gateway_status' => 'pending',
                    'paid_at' => null,
                ]);
            }

            return $this->respond([
                'message' => 'Order placed successfully.',
                'order' => new OrderResource($order->load([
                    'items.variant.product', 'shipment.address', 'invoice', 'payment',
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
