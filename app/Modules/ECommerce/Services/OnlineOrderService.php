<?php

namespace App\Modules\ECommerce\Services;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Customer\Models\Address;
use App\Modules\Customer\Models\Customer;
use App\Modules\ECommerce\Models\CartItem;
use App\Modules\Sales\Models\Order;
use App\Modules\Sales\Models\OrderItem;
use App\Modules\Sales\Services\InvoiceNumberGenerator;
use App\Modules\Inventory\Services\StockService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates online order placement.
 *
 * Runs within a database transaction: validates stock (with row lock),
 * creates order/items/invoice/shipment, deducts inventory, and clears the cart.
 */
class OnlineOrderService
{
    public function __construct(
        private readonly StockService $stockService,
    ) {}

    public function placeOrder(
        Customer $customer,
        Collection $cartItems,
        Address $address,
        ?string $notes,
    ): Order {
        $generator = app(InvoiceNumberGenerator::class);

        return DB::transaction(function () use ($customer, $cartItems, $address, $notes, $generator) {
            $totalAmount = 0;

            // Lock variant rows to prevent race conditions during checkout.
            foreach ($cartItems as $item) {
                $variant = ProductVariant::lockForUpdate()->find($item->product_variant_id);

                if (!$variant || $variant->stock_quantity < $item->quantity) {
                    throw new \RuntimeException("Insufficient stock for '{$variant?->sku}'.");
                }

                $totalAmount += ($variant->product->base_price + $variant->price_adjustment) * $item->quantity;
            }

            $order = Order::create([
                'user_id' => null,                                // No staff user for online orders.
                'customer_id' => $customer->id,
                'order_number' => $generator->generateOrderNumber(),
                'total_amount' => $totalAmount,
                'status' => 'processing',
                'source' => 'online',
                'notes' => $notes,
            ]);

            foreach ($cartItems as $item) {
                $variant = ProductVariant::find($item->product_variant_id);
                $unitPrice = $variant->product->base_price + $variant->price_adjustment;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $item->quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $unitPrice * $item->quantity,
                ]);

                $variant->decrement('stock_quantity', $item->quantity);

                $this->stockService->recordMovement(
                    $variant, -$item->quantity, 'sale', 'order', $order->id,
                );
            }

            // Auto-generate invoice for online orders.
            $order->invoice()->create([
                'invoice_number' => $generator->generate(),
                'issued_date' => now(),
                'due_date' => now()->addDays(30),
                'status' => 'issued',
            ]);

            // Create shipment linked to the chosen address.
            $order->shipment()->create([
                'address_id' => $address->id,
                'method' => 'standard',
            ]);

            // Clear cart after successful placement.
            CartItem::where('customer_id', $customer->id)->delete();

            return $order;
        });
    }
}
