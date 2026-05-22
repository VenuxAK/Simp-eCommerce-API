<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private readonly StockService $stockService,
    ) {}

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

            if ($data['customer_id'] ?? null) {
                $order->customer->increment('loyalty_points', (int) ($finalAmount / 10));
            }

            return $order;
        });
    }
}
