<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Core\Enums\OrderStatus;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Core\Traits\QueryFilter;
use App\Modules\Core\Traits\StoreScope;
use App\Modules\Promotion\Services\DiscountService;
use App\Modules\Sales\Http\Requests\ReturnOrderRequest;
use App\Modules\Sales\Http\Requests\StoreOrderRequest;
use App\Modules\Sales\Http\Requests\UpdateOrderStatusRequest;
use App\Modules\Sales\Http\Resources\OrderResource;
use App\Modules\Sales\Models\Order;
use App\Modules\Sales\Repositories\OrderRepository;
use App\Modules\Sales\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Handles Order-related API requests.
 */
class OrderController extends Controller
{
    use ApiResponse, QueryFilter, StoreScope;

    public function __construct(
        private readonly OrderService $orderService,
        private readonly DiscountService $discountService,
        private readonly OrderRepository $orderRepository,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $filters = array_merge(
            request()->only(['status', 'customer_id', 'date_from', 'date_to']),
            ['store_id' => $this->resolveStoreId()],
        );

        $orders = $this->orderRepository->paginateFiltered(
            $filters,
            ['user', 'customer', 'items.variant', 'payment', 'invoice'],
        );

        return OrderResource::collection($orders);
    }

    /**
     * Create an order from POS checkout.
     *
     * Validates stock, calculates totals, applies discounts,
     * checks payment sufficiency, and delegates to OrderService.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $errors = [];

        $totalAmount = 0;
        $orderItems = [];
        $seenVariants = [];

        foreach ($data['items'] as $item) {
            $variantId = $item['product_variant_id'];

            if (in_array($variantId, $seenVariants)) {
                $errors[] = 'Duplicate variant in order items.';

                continue;
            }
            $seenVariants[] = $variantId;

            $variant = ProductVariant::find($variantId);

            if (! $variant) {
                $errors[] = "Variant #{$variantId} not found.";

                continue;
            }

            if ($variant->stock_quantity < $item['quantity']) {
                $errors[] = 'Insufficient stock for variant (available: '.$variant->stock_quantity.')';

                continue;
            }

            $unitPrice = $variant->product->base_price + $variant->price_adjustment;
            $subtotal = $unitPrice * $item['quantity'];
            $totalAmount += $subtotal;

            $orderItems[] = [
                'variant_id' => $variant->id,
                'variant' => $variant,
                'quantity' => $item['quantity'],
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
            ];
        }

        [$discountAmount, $discountLabel] = $this->discountService->apply(
            $data['discount_id'] ?? null, $orderItems, $totalAmount,
        );

        $finalAmount = $totalAmount - $discountAmount;

        $paidAmount = (float) $data['payment']['amount'];
        if ($paidAmount < $finalAmount) {
            $errors[] = "Payment amount ({$paidAmount}) is less than total ({$finalAmount}).";
        }

        if (! empty($errors)) {
            return $this->respondError(implode(' ', $errors));
        }

        try {
            $order = $this->orderService->createOrder(
                $orderItems, $this->mergeStoreId($data), $finalAmount, $paidAmount, $discountAmount, $discountLabel,
            );
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage());
        }

        return (new OrderResource($order->load(['user', 'customer', 'items.variant.product', 'payment', 'invoice'])))->response()->setStatusCode(201);
    }

    public function show(Order $order): OrderResource
    {
        return new OrderResource($order->load(['user', 'customer', 'items.variant.product', 'payment', 'invoice', 'shipment.address']));
    }

    /**
     * Process item-level returns with stock restoration.
     *
     * Validates returnable quantities by checking prior returns,
     * restocks inventory, and marks the order as refunded.
     */
    public function returnItems(ReturnOrderRequest $request, Order $order): JsonResponse
    {
        if (! in_array($order->status, [OrderStatus::Completed, OrderStatus::Refunded])) {
            return $this->respondError(__('messages.orders.cannot_return'));
        }

        try {
            $order = $this->orderService->returnItems($order, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage());
        }

        return $this->respond([
            'message' => 'Items returned successfully.',
            'order' => new OrderResource($order->load(['items.variant', 'invoice'])),
        ]);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): OrderResource|JsonResponse
    {
        $newStatus = $request->status;
        $currentStatus = $order->status->value;

        $allowedFrom = [
            'completed' => ['cancelled', 'refunded'],
            'pending' => ['completed', 'cancelled'],
            'cancelled' => [],
            'refunded' => [],
            'processing' => ['shipped', 'cancelled'],
            'shipped' => ['delivered'],
            'delivered' => [],
        ];

        if (! isset($allowedFrom[$currentStatus]) || ! in_array($newStatus, $allowedFrom[$currentStatus])) {
            return $this->respondError(__('messages.order_status.invalid_transition', ['current' => $currentStatus, 'new' => $newStatus]));
        }

        try {
            $order = $this->orderService->transitionStatus(
                $order, $newStatus, $currentStatus, $request->tracking_number,
            );
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage());
        }

        return new OrderResource($order->load(['items.variant', 'invoice']));
    }
}
