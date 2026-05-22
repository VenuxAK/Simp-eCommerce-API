<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\ApiResponse;
use App\Http\Requests\Api\ReturnOrderRequest;
use App\Http\Requests\Api\StoreOrderRequest;
use App\Http\Requests\Api\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Services\InvoiceNumberGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    use ApiResponse;
    public function index(): AnonymousResourceCollection
    {
        $orders = Order::with(['user', 'customer', 'items.variant', 'payment', 'invoice'])
            ->when(request('status'), fn($q) => $q->where('status', request('status')))
            ->when(request('customer_id'), fn($q) => $q->where('customer_id', request('customer_id')))
            ->when(request('date_from'), fn($q) => $q->whereDate('created_at', '>=', request('date_from')))
            ->when(request('date_to'), fn($q) => $q->whereDate('created_at', '<=', request('date_to')))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

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

        $discountAmount = 0;
        $discountLabel = '';
        $discountableTotal = 0;

        if ($data['discount_id'] ?? null) {
            $discount = Discount::find($data['discount_id']);
            if ($discount && $discount->is_active) {
                if ($discount->applies_to === 'all') {
                    $discountableTotal = $totalAmount;
                } elseif ($discount->applies_to === 'category' && $discount->category_id) {
                    foreach ($orderItems as $item) {
                        if ($item['variant']->product->category_id === $discount->category_id) {
                            $discountableTotal += $item['subtotal'];
                        }
                    }
                } elseif ($discount->applies_to === 'product' && $discount->product_id) {
                    foreach ($orderItems as $item) {
                        if ($item['variant']->product_id === $discount->product_id) {
                            $discountableTotal += $item['subtotal'];
                        }
                    }
                }

                if ($discount->type === 'percentage') {
                    $discountAmount = round($discountableTotal * $discount->value / 100, 2);
                    $discountLabel = "{$discount->name} ({$discount->value}%)";
                } else {
                    $discountAmount = min($discount->value, $discountableTotal);
                    $discountLabel = "{$discount->name} (-{$discount->value} Ks)";
                }
            }
        }

        $finalAmount = $totalAmount - $discountAmount;

        $paidAmount = (float) $data['payment']['amount'];
        if ($paidAmount < $finalAmount) {
            $errors[] = "Payment amount ({$paidAmount}) is less than total ({$finalAmount}).";
        }

        if (!empty($errors)) {
            return $this->respondError(implode(' ', $errors));
        }

        try {
            $order = DB::transaction(function () use ($orderItems, $data, $finalAmount, $paidAmount, $discountAmount, $discountLabel) {
                $notes = $data['notes'] ?? null;
                if ($discountAmount > 0) {
                    $notes = ($notes ? $notes . "\n" : '') . "Discount: {$discountLabel}";
                }

                $order = Order::create([
                    'user_id' => request()->user()->id,
                    'customer_id' => $data['customer_id'] ?? null,
                    'order_number' => InvoiceNumberGenerator::generateOrderNumber(),
                    'total_amount' => $finalAmount,
                    'status' => 'completed',
                    'notes' => $notes,
                ]);

                foreach ($orderItems as $item) {
                    $variant = ProductVariant::lockForUpdate()->find($item['variant_id']);

                    if (!$variant || $variant->stock_quantity < $item['quantity']) {
                        throw new \RuntimeException("Insufficient stock for variant SKU: {$item['variant']['sku']}");
                    }

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_variant_id' => $variant->id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'subtotal' => $item['subtotal'],
                    ]);

                    $variant->decrement('stock_quantity', $item['quantity']);

                    StockMovement::create([
                        'product_variant_id' => $variant->id,
                        'quantity_change' => -$item['quantity'],
                        'reason' => 'sale',
                        'reference_type' => 'order',
                        'reference_id' => $order->id,
                        'user_id' => request()->user()->id,
                    ]);
                }

                $order->payment()->create([
                    'method' => $data['payment']['method'],
                    'amount' => $paidAmount,
                    'paid_at' => now(),
                ]);

                $generator = app(InvoiceNumberGenerator::class);
                $order->invoice()->create([
                    'invoice_number' => $generator->generate(),
                    'issued_date' => now(),
                    'due_date' => now()->addDays(30),
                    'status' => 'issued',
                ]);

                if ($data['customer_id'] ?? null) {
                    $order->customer->increment('loyalty_points', (int) ($finalAmount / 10));
                }

                return $order;
            });
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

                    StockMovement::create([
                        'product_variant_id' => $orderItem->product_variant_id,
                        'quantity_change' => $returnItem['quantity'],
                        'reason' => 'refunded',
                        'reference_type' => 'order',
                        'reference_id' => $order->id,
                        'user_id' => request()->user()->id,
                    ]);
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

                    StockMovement::create([
                        'product_variant_id' => $item->product_variant_id,
                        'quantity_change' => $item->quantity,
                        'reason' => $newStatus,
                        'reference_type' => 'order',
                        'reference_id' => $order->id,
                        'user_id' => request()->user()->id,
                    ]);
                }
            }

            if ($newStatus === 'completed' && $currentStatus === 'pending') {
                foreach ($order->items as $item) {
                    $item->variant->decrement('stock_quantity', $item->quantity);

                    StockMovement::create([
                        'product_variant_id' => $item->product_variant_id,
                        'quantity_change' => -$item->quantity,
                        'reason' => $newStatus,
                        'reference_type' => 'order',
                        'reference_id' => $order->id,
                        'user_id' => request()->user()->id,
                    ]);
                }
            }

            return $order;
        });

        return new OrderResource($order->load(['items.variant', 'invoice']));
    }
}
