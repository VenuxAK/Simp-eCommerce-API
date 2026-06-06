<?php

namespace App\Modules\ECommerce\Services;

use App\Modules\Catalog\Repositories\ProductVariantRepository;
use App\Modules\Customer\Models\Address;
use App\Modules\Customer\Models\Customer;
use App\Modules\ECommerce\Repositories\CartItemRepository;
use App\Modules\ECommerce\Repositories\ShipmentRepository;
use App\Modules\Inventory\Services\StockService;
use App\Modules\Sales\Models\Order;
use App\Modules\Sales\Models\OrderItem;
use App\Modules\Sales\Repositories\OrderRepository;
use App\Modules\Sales\Services\InvoiceNumberGenerator;
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
        private readonly CartItemRepository $cartItemRepository,
        private readonly ProductVariantRepository $productVariantRepository,
        private readonly OrderRepository $orderRepository,
        private readonly ShipmentRepository $shipmentRepository,
        private readonly StockService $stockService,
        private readonly InvoiceNumberGenerator $numberGenerator,
    ) {}

    public function placeOrder(
        Customer $customer,
        Collection $cartItems,
        Address $address,
        ?string $notes,
        ?int $storeId = null,
    ): Order {
        return DB::transaction(function () use ($customer, $cartItems, $address, $notes, $storeId) {
            $totalAmount = 0;

            // Lock variant rows to prevent race conditions during checkout.
            foreach ($cartItems as $item) {
                $variant = $this->productVariantRepository->lockForUpdate($item->product_variant_id);

                if (! $variant || $variant->stock_quantity < $item->quantity) {
                    throw new \RuntimeException("Insufficient stock for '{$variant?->sku}'.");
                }

                $totalAmount += ($variant->product->base_price + $variant->price_adjustment) * $item->quantity;
            }

            $order = $this->orderRepository->create([
                'user_id' => null,
                'customer_id' => $customer->id,
                'store_id' => $storeId,
                'order_number' => $this->numberGenerator->generateOrderNumber(),
                'total_amount' => $totalAmount,
                'status' => 'processing',
                'source' => 'online',
                'notes' => $notes,
            ]);

            foreach ($cartItems as $item) {
                $variant = $this->productVariantRepository->find($item->product_variant_id);
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
                'invoice_number' => $this->numberGenerator->generate(),
                'issued_date' => now(),
                'due_date' => now()->addDays(30),
                'status' => 'issued',
            ]);

            // Create shipment linked to the chosen address.
            $this->shipmentRepository->create([
                'order_id' => $order->id,
                'address_id' => $address->id,
                'method' => 'standard',
            ]);

            // Clear cart after successful placement.
            $this->cartItemRepository->deleteByCustomer($customer->id);

            return $order;
        });
    }
}
