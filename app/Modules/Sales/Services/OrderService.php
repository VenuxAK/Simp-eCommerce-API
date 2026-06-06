<?php

namespace App\Modules\Sales\Services;

use App\Modules\Catalog\Repositories\ProductVariantRepository;
use App\Modules\Core\Enums\OrderStatus;
use App\Modules\Core\Enums\StockMovementReason;
use App\Modules\Inventory\Repositories\StockMovementRepository;
use App\Modules\Inventory\Services\StockService;
use App\Modules\Sales\Models\Order;
use App\Modules\Sales\Models\OrderItem;
use App\Modules\Sales\Repositories\InvoiceRepository;
use App\Modules\Sales\Repositories\OrderItemRepository;
use App\Modules\Sales\Repositories\OrderRepository;
use App\Modules\Sales\Repositories\PaymentRepository;
use Illuminate\Support\Facades\DB;

/**
 * Business logic for Order operations.
 */
class OrderService
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly InvoiceNumberGenerator $numberGenerator,
        private readonly OrderRepository $orderRepository,
        private readonly OrderItemRepository $orderItemRepository,
        private readonly ProductVariantRepository $variantRepository,
        private readonly StockMovementRepository $stockMovementRepository,
        private readonly InvoiceRepository $invoiceRepository,
        private readonly PaymentRepository $paymentRepository,
    ) {}

    /**
     * Create an order within a database transaction.
     *
     * Validates stock levels, creates order items, deducts inventory,
     * records payment and invoice, and awards loyalty points.
     *
     * @param  array  $orderItems  Items with variant_id, quantity, unit_price, subtotal
     * @param  array  $data  Raw request data (customer_id, payment, notes, etc.)
     * @param  float  $finalAmount  Total after discount
     * @param  float  $paidAmount  Amount tendered by customer
     * @param  float  $discountAmount  Discount applied
     * @param  string  $discountLabel  Human-readable discount description
     */
    public function createOrder(
        array $orderItems,
        array $data,
        float $finalAmount,
        float $paidAmount,
        float $discountAmount,
        string $discountLabel,
    ): Order {

        return DB::transaction(function () use ($orderItems, $data, $finalAmount, $paidAmount, $discountAmount, $discountLabel) {
            $notes = $data['notes'] ?? null;
            if ($discountAmount > 0) {
                $notes = ($notes ? $notes."\n" : '')."Discount: {$discountLabel}";
            }

            $order = Order::create([
                'user_id' => request()->user()->id,
                'customer_id' => $data['customer_id'] ?? null,
                'store_id' => $data['store_id'],
                'order_number' => $this->numberGenerator->generateOrderNumber(),
                'total_amount' => $finalAmount,
                'status' => 'completed',
                'notes' => $notes,
            ]);

            // Lock each variant row and decrement stock atomically.
            foreach ($orderItems as $item) {
                $variant = $this->variantRepository->lockForUpdate($item['variant_id']);

                if (! $variant || $variant->stock_quantity < $item['quantity']) {
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

                $this->stockService->recordMovement(
                    $variant, -$item['quantity'], 'sale', 'order', $order->id,
                );
            }

            $order->payment()->create([
                'method' => $data['payment']['method'],
                'amount' => $paidAmount,
                'paid_at' => now(),
            ]);

            $order->invoice()->create([
                'invoice_number' => $this->numberGenerator->generate(),
                'issued_date' => now(),
                'due_date' => now()->addDays(30),
                'status' => 'issued',
            ]);

            // Award 1 loyalty point per 10 currency units spent.
            if ($data['customer_id'] ?? null) {
                $order->customer->increment('loyalty_points', (int) ($finalAmount / 10));
            }

            return $order;
        });
    }

    public function returnItems(Order $order, array $returnData): Order
    {
        return DB::transaction(function () use ($order, $returnData) {
            $totalReturn = 0;

            foreach ($returnData['items'] as $returnItem) {
                $orderItem = $order->items()->findOrFail($returnItem['order_item_id']);

                $alreadyReturned = (int) $this->stockMovementRepository->query()
                    ->where('product_variant_id', $orderItem->product_variant_id)
                    ->where('reference_type', 'order')
                    ->where('reference_id', $order->id)
                    ->where('reason', StockMovementReason::Refunded->value)
                    ->sum('quantity_change');

                $returnableQty = $orderItem->quantity - $alreadyReturned;

                if ($returnItem['quantity'] > $returnableQty) {
                    throw new \RuntimeException("Return quantity exceeds remaining returnable quantity for item #{$orderItem->id} (max: {$returnableQty}).");
                }

                $unitPrice = $orderItem->unit_price;
                $subtotal = $unitPrice * $returnItem['quantity'];
                $totalReturn += $subtotal;

                $variant = $this->variantRepository->lockForUpdate($orderItem->product_variant_id);
                $variant->increment('stock_quantity', $returnItem['quantity']);

                $this->stockService->recordMovement(
                    $variant, $returnItem['quantity'], StockMovementReason::Refunded->value, 'order', $order->id,
                );
            }

            $order->update(['status' => OrderStatus::Refunded]);

            if ($order->invoice) {
                $order->invoice->update(['status' => 'refunded']);
            }

            $notes = $order->notes ? $order->notes."\n" : '';
            $notes .= 'Return: '.now()->toDateString().' - '.$totalReturn.' Ks returned';
            $order->update(['notes' => $notes]);

            return $order;
        });
    }

    public function transitionStatus(Order $order, string $newStatus, string $currentStatus, ?string $trackingNumber = null): Order
    {
        return DB::transaction(function () use ($order, $newStatus, $currentStatus, $trackingNumber) {
            $order->update(['status' => $newStatus]);

            $validInvoiceStatuses = ['issued', 'paid', 'cancelled', 'refunded'];
            if ($order->invoice && in_array($newStatus, $validInvoiceStatuses)) {
                $order->invoice->update(['status' => $newStatus]);
            }

            if ($newStatus === 'shipped' && $order->shipment) {
                $data = ['shipped_at' => now()];
                if ($trackingNumber) {
                    $data['tracking_number'] = $trackingNumber;
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
                        $item->variant, $item->quantity, StockMovementReason::Return->value, 'order', $order->id,
                    );
                }
            }

            if ($newStatus === 'completed' && $currentStatus === 'pending') {
                foreach ($order->items as $item) {
                    $item->variant->decrement('stock_quantity', $item->quantity);
                    $this->stockService->recordMovement(
                        $item->variant, -$item->quantity, StockMovementReason::Sale->value, 'order', $order->id,
                    );
                }
            }

            return $order;
        });
    }
}
