<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Core\Traits\QueryFilter;
use App\Modules\Sales\Http\Requests\ReturnOrderRequest;
use App\Modules\Sales\Http\Requests\StoreOrderRequest;
use App\Modules\Sales\Http\Requests\UpdateOrderStatusRequest;
use App\Modules\Sales\Http\Resources\OrderResource;
use App\Modules\Sales\Models\Order;
use App\Models\StockMovement;
use App\Services\DiscountService;
use App\Modules\Sales\Services\OrderService;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    use ApiResponse, QueryFilter;

    public function __construct(
        private readonly OrderService $orderService,
        private readonly DiscountService $discountService,
        private readonly StockService $stockService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $orders = $this->applyFilters(
            Order::with(['user', 'customer', 'items.variant', 'payment', 'invoice']),
            ['status' => 'status', 'customer_id' => 'customer_id'],
        );
        $orders = $this->applyDateRange($orders);
        $orders = $this->latestPaginated($orders);

        return OrderResource::collection($orders);
    }

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

            if (!$variant) {
                $errors[] = "Variant #{$variantId} not found.";
                continue;
            }

            if ($variant->stock_quantity < $item['quantity']) {
                $errors[] = 'Insufficient stock for variant (available: ' . $variant->stock_quantity . ')';
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

        if (!empty($errors)) {
            return $this->respondError(implode(' ', $errors));
        }

        try {
            $order = $this->orderService->createOrder(
                $orderItems, $data, $finalAmount, $paidAmount, $discountAmount, $discountLabel,
            );
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage());
        }

        return new OrderResource($order->load(['user', 'customer', 'items.variant.product', 'payment', 'invoice']))->response()->setStatusCode(201);
    }

    public function show(Order $order): OrderResource
    {
        return new OrderResource($order->load(['user', 'customer', 'items.variant.product', 'payment', 'invoice']));
    }

    public function returnItems(ReturnOrderRequest $request, Order $order): JsonResponse
    {
        if (!in_array($order->status, ['completed', 'refunded'])) {
            return $this->respondError('Order cannot be returned.');
        }

        $data = $request->validated();

        try {
            $order = DB::transaction(function () use ($order, $data) {
                $totalReturn = 0;

                foreach ($data['items'] as $returnItem) {
                    $orderItem = $order->items()->findOrFail($returnItem['order_item_id']);

                    $alreadyReturned = StockMovement::where('product_variant_id', $orderItem->product_variant_id)
                        ->where('reference_type', 'order')
                        ->where('reference_id', $order->id)
                        ->where('reason', 'refunded')
                        ->sum('quantity_change');

                    $returnableQty = $orderItem->quantity - $alreadyReturned;

                    if ($returnItem['quantity'] > $returnableQty) {
                        throw new \RuntimeException("Return quantity exceeds remaining returnable quantity for item #{$orderItem->id} (max: {$returnableQty}).");
                    }

                    $unitPrice = $orderItem->unit_price;
                    $subtotal = $unitPrice * $returnItem['quantity'];
                    $totalReturn += $subtotal;

                    $variant = ProductVariant::lockForUpdate()->find($orderItem->product_variant_id);
                    $variant->increment('stock_quantity', $returnItem['quantity']);

                    $this->stockService->recordMovement(
                        $variant, $returnItem['quantity'], 'refunded', 'order', $order->id,
                    );
                }

                $order->update(['status' => 'refunded']);

                if ($order->invoice) {
                    $order->invoice->update(['status' => 'refunded']);
                }

                $notes = $order->notes ? $order->notes . "\n" : '';
                $notes .= 'Return: ' . now()->toDateString() . ' - ' . $totalReturn . ' Ks returned';
                $order->update(['notes' => $notes]);

                return $order;
            });
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
        $currentStatus = $order->status;

        $allowedFrom = [
            'completed' => ['cancelled', 'refunded'],
            'pending' => ['completed', 'cancelled'],
            'cancelled' => [],
            'refunded' => [],
        ];

        if (!isset($allowedFrom[$currentStatus]) || !in_array($newStatus, $allowedFrom[$currentStatus])) {
            return $this->respondError("Cannot transition from '{$currentStatus}' to '{$newStatus}'.");
        }

        $order = DB::transaction(function () use ($order, $newStatus, $currentStatus) {
            $order->update(['status' => $newStatus]);

            if ($order->invoice) {
                $order->invoice->update(['status' => $newStatus]);
            }

            if (in_array($newStatus, ['cancelled', 'refunded'])) {
                foreach ($order->items as $item) {
                    $item->variant->increment('stock_quantity', $item->quantity);
                    $this->stockService->recordMovement(
                        $item->variant, $item->quantity, $newStatus, 'order', $order->id,
                    );
                }
            }

            if ($newStatus === 'completed' && $currentStatus === 'pending') {
                foreach ($order->items as $item) {
                    $item->variant->decrement('stock_quantity', $item->quantity);
                    $this->stockService->recordMovement(
                        $item->variant, -$item->quantity, $newStatus, 'order', $order->id,
                    );
                }
            }

            return $order;
        });

        return new OrderResource($order->load(['items.variant', 'invoice']));
    }
}
