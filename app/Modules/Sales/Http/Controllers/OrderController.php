<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Core\Traits\QueryFilter;
use App\Modules\Core\Traits\StoreScope;
use App\Modules\Sales\Http\Requests\ReturnOrderRequest;
use App\Modules\Sales\Http\Requests\StoreOrderRequest;
use App\Modules\Sales\Http\Requests\UpdateOrderStatusRequest;
use App\Modules\Sales\Http\Resources\OrderResource;
use App\Modules\Sales\Models\Order;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Promotion\Services\DiscountService;
use App\Modules\Sales\Services\OrderService;
use App\Modules\Inventory\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Handles Order-related API requests.
 */
class OrderController extends Controller
{
    use ApiResponse, QueryFilter, StoreScope;

    public function __construct(
        private readonly OrderService $orderService,
        private readonly DiscountService $discountService,
        private readonly StockService $stockService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $query = Order::with(['user', 'customer', 'items.variant', 'payment', 'invoice']);
        $this->scopeByStore($query);
        $orders = $this->applyFilters(
            $query,
            ['status' => 'status', 'customer_id' => 'customer_id'],
        );
        $orders = $this->applyDateRange($orders);
        $orders = $this->latestPaginated($orders);

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
                $orderItems, $this->mergeStoreId($data), $finalAmount, $paidAmount, $discountAmount, $discountLabel,
            );
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage());
        }

        return new OrderResource($order->load(['user', 'customer', 'items.variant.product', 'payment', 'invoice']))->response()->setStatusCode(201);
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
        if (!in_array($order->status, ['completed', 'refunded'])) {
            return $this->respondError('Order cannot be returned.');
        }

        $data = $request->validated();

        try {
            $order = DB::transaction(function () use ($order, $data) {
                $totalReturn = 0;

                foreach ($data['items'] as $returnItem) {
                    $orderItem = $order->items()->findOrFail($returnItem['order_item_id']);

                    // Calculate already returned quantity for this variant on this order.
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

    /**
     * Transition an order through its allowed statuses.
     *
     * Restores or deducts stock on cancel/refund/complete transitions.
     * Only valid transitions defined in the state machine are permitted.
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): OrderResource|JsonResponse
    {
        $newStatus = $request->status;
        $currentStatus = $order->status;

        // Allowed transitions: current => [next]. Online statuses extend the POS state machine.
        $allowedFrom = [
            'completed' => ['cancelled', 'refunded'],
            'pending' => ['completed', 'cancelled'],
            'cancelled' => [],
            'refunded' => [],
            'processing' => ['shipped', 'cancelled'],        // Online: pay → ship or cancel.
            'shipped' => ['delivered'],                       // Online: ship → deliver.
            'delivered' => [],                                // Terminal.
        ];

        if (!isset($allowedFrom[$currentStatus]) || !in_array($newStatus, $allowedFrom[$currentStatus])) {
            return $this->respondError("Cannot transition from '{$currentStatus}' to '{$newStatus}'.");
        }

        $order = DB::transaction(function () use ($order, $newStatus, $currentStatus, $request) {
            $order->update(['status' => $newStatus]);

            // Mirror order status on invoice only for valid invoice statuses.
            $validInvoiceStatuses = ['issued', 'paid', 'cancelled', 'refunded'];
            if ($order->invoice && in_array($newStatus, $validInvoiceStatuses)) {
                $order->invoice->update(['status' => $newStatus]);
            }

            // Update shipment tracking when order ships.
            if ($newStatus === 'shipped' && $order->shipment) {
                $data = ['shipped_at' => now()];
                if ($request->filled('tracking_number')) {
                    $data['tracking_number'] = $request->tracking_number;
                }
                $order->shipment->update($data);
            }
            if ($newStatus === 'delivered' && $order->shipment) {
                $order->shipment->update(['delivered_at' => now()]);
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
