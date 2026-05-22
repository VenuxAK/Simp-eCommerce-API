<?php

namespace App\Modules\Sales\Services;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Sales\Models\Order;
use App\Modules\Sales\Models\OrderItem;
use App\Modules\Inventory\Services\StockService;
use Illuminate\Support\Facades\DB;

/**
 * Business logic for Order operations.
 */
class OrderService
{
    public function __construct(
        private readonly StockService $stockService,
    ) {}

    /**
     * Create an order within a database transaction.
     *
     * Validates stock levels, creates order items, deducts inventory,
     * records payment and invoice, and awards loyalty points.
     *
     * @param  array  $orderItems  Items with variant_id, quantity, unit_price, subtotal
     * @param  array  $data        Raw request data (customer_id, payment, notes, etc.)
     * @param  float  $finalAmount Total after discount
     * @param  float  $paidAmount  Amount tendered by customer
     * @param  float  $discountAmount  Discount applied
     * @param  string $discountLabel   Human-readable discount description
     */
    public function createOrder(
        array $orderItems,
        array $data,
        float $finalAmount,
        float $paidAmount,
        float $discountAmount,
        string $discountLabel,
    ): Order {
        $generator = app(InvoiceNumberGenerator::class);

        return DB::transaction(function () use ($orderItems, $data, $finalAmount, $paidAmount, $discountAmount, $discountLabel, $generator) {
            $notes = $data['notes'] ?? null;
            if ($discountAmount > 0) {
                $notes = ($notes ? $notes . "\n" : '') . "Discount: {$discountLabel}";
            }

            $order = Order::create([
                'user_id' => request()->user()->id,
                'customer_id' => $data['customer_id'] ?? null,
                'order_number' => $generator->generateOrderNumber(),
                'total_amount' => $finalAmount,
                'status' => 'completed',
                'notes' => $notes,
            ]);

            // Lock each variant row and decrement stock atomically.
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
                'invoice_number' => $generator->generate(),
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
}
