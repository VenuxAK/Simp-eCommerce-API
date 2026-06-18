<?php

namespace Tests\Feature\Api;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Core\Enums\StockMovementReason;
use App\Modules\Customer\Models\Customer;
use App\Modules\Identity\Models\User;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Sales\Models\Order;
use Illuminate\Support\Facades\Notification;
use Tests\ApiTestCase;

class OrderTest extends ApiTestCase
{
    private array $validPayload;

    protected function setUp(): void
    {
        parent::setUp();
        $variant = $this->createVariant(10, 50);

        $this->validPayload = [
            'items' => [['product_variant_id' => $variant->id, 'quantity' => 2]],
            'payment' => ['method' => 'cash', 'amount' => 100],
        ];
    }

    public function test_can_create_order(): void
    {
        $response = $this->postJson('/api/orders', $this->validPayload, $this->adminHeaders);
        $response->assertCreated()->assertJsonStructure([
            'data' => ['id', 'order_number', 'total_amount', 'status', 'items', 'payment', 'invoice'],
        ]);
    }

    public function test_order_deducts_stock(): void
    {
        $variant = ProductVariant::find($this->validPayload['items'][0]['product_variant_id']);
        $initialStock = $variant->stock_quantity;

        $response = $this->postJson('/api/orders', $this->validPayload, $this->adminHeaders);

        $response->assertCreated();
        $this->assertEquals($initialStock - 2, $variant->fresh()->stock_quantity);
    }

    public function test_cannot_order_with_insufficient_stock(): void
    {
        $response = $this->postJson('/api/orders', [
            'items' => [['product_variant_id' => $this->validPayload['items'][0]['product_variant_id'], 'quantity' => 999]],
            'payment' => ['method' => 'cash', 'amount' => 99999],
        ], $this->adminHeaders);

        $response->assertUnprocessable();
    }

    public function test_cannot_order_with_underpayment(): void
    {
        $response = $this->postJson('/api/orders', [
            'items' => $this->validPayload['items'],
            'payment' => ['method' => 'cash', 'amount' => 1],
        ], $this->adminHeaders);

        $response->assertUnprocessable();
    }

    public function test_cannot_order_with_duplicate_variants(): void
    {
        $variantId = $this->validPayload['items'][0]['product_variant_id'];

        $response = $this->postJson('/api/orders', [
            'items' => [
                ['product_variant_id' => $variantId, 'quantity' => 1],
                ['product_variant_id' => $variantId, 'quantity' => 1],
            ],
            'payment' => ['method' => 'cash', 'amount' => 200],
        ], $this->adminHeaders);

        $response->assertUnprocessable();
    }

    public function test_order_with_customer_awards_loyalty_points(): void
    {
        $customer = Customer::factory()->create(['loyalty_points' => 0]);
        $this->validPayload['customer_id'] = $customer->id;

        $this->postJson('/api/orders', $this->validPayload, $this->adminHeaders);

        $this->assertGreaterThan(0, $customer->fresh()->loyalty_points);
    }

    public function test_can_list_orders(): void
    {
        $this->postJson('/api/orders', $this->validPayload, $this->adminHeaders);

        $response = $this->getJson('/api/orders', $this->adminHeaders);
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_show_order(): void
    {
        $create = $this->postJson('/api/orders', $this->validPayload, $this->adminHeaders);
        $orderId = $create->json('data.id');

        $response = $this->getJson("/api/orders/{$orderId}", $this->adminHeaders);
        $response->assertOk()->assertJsonPath('data.id', $orderId);
    }

    public function test_can_cancel_order_and_restore_stock(): void
    {
        $variant = ProductVariant::find($this->validPayload['items'][0]['product_variant_id']);
        $initialStock = $variant->stock_quantity;

        $create = $this->postJson('/api/orders', $this->validPayload, $this->adminHeaders);
        $orderId = $create->json('data.id');
        $this->assertEquals($initialStock - 2, $variant->fresh()->stock_quantity);

        $this->patchJson("/api/orders/{$orderId}/status", ['status' => 'cancelled'], $this->adminHeaders);

        $this->assertEquals($initialStock, $variant->fresh()->stock_quantity);
    }

    public function test_double_cancel_is_idempotent(): void
    {
        $variant = ProductVariant::find($this->validPayload['items'][0]['product_variant_id']);
        $create = $this->postJson('/api/orders', $this->validPayload, $this->adminHeaders);
        $orderId = $create->json('data.id');

        $this->patchJson("/api/orders/{$orderId}/status", ['status' => 'cancelled'], $this->adminHeaders);
        $afterFirst = $variant->fresh()->stock_quantity;

        $response = $this->patchJson("/api/orders/{$orderId}/status", ['status' => 'cancelled'], $this->adminHeaders);
        $response->assertUnprocessable();
        $this->assertEquals($afterFirst, $variant->fresh()->stock_quantity);
    }

    public function test_order_creates_invoice_automatically(): void
    {
        $this->postJson('/api/orders', $this->validPayload, $this->adminHeaders);

        $this->assertDatabaseCount('invoices', 1);
    }

    public function test_staff_cannot_update_order_status(): void
    {
        $create = $this->postJson('/api/orders', $this->validPayload, $this->adminHeaders);
        $create->assertCreated();
        $orderId = $create->json('data.id');

        $staffUser = User::factory()->salesStaff()->create();
        $this->actingAs($staffUser, 'sanctum');

        $response = $this->patchJson("/api/orders/{$orderId}/status", ['status' => 'cancelled']);
        $response->assertStatus(403);
    }

    public function test_staff_can_create_order(): void
    {
        $response = $this->postJson('/api/orders', $this->validPayload, $this->staffHeaders);
        $response->assertCreated();
    }

    public function test_stock_deducted_on_pending_to_completed_transition(): void
    {
        $variant = ProductVariant::find($this->validPayload['items'][0]['product_variant_id']);
        $initialStock = $variant->stock_quantity;

        $order = Order::create([
            'user_id' => User::role('root')->first()->id,
            'store_id' => 1,
            'order_number' => 'ORD-PEND-001',
            'total_amount' => 100,
            'status' => 'pending',
            'notes' => null,
        ]);
        $order->items()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'unit_price' => 50,
            'subtotal' => 100,
        ]);

        $this->patchJson("/api/orders/{$order->id}/status", ['status' => 'completed'], $this->adminHeaders);

        $this->assertEquals($initialStock - 2, $variant->fresh()->stock_quantity);

        $movement = StockMovement::where('reference_id', $order->id)
            ->where('reason', StockMovementReason::Sale->value)
            ->first();
        $this->assertNotNull($movement);
        $this->assertEquals(-2, $movement->quantity_change);
    }

    // ─── Status Update Notifications ───────────────────────────

    public function test_online_order_shipped_dispatches_notification(): void
    {
        Notification::fake();

        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'source' => 'online',
            'status' => 'processing',
        ]);

        $this->patchJson("/api/orders/{$order->id}/status", [
            'status' => 'shipped',
        ], $this->adminHeaders);

        Notification::assertSentTo(
            $customer,
            \App\Modules\Sales\Notifications\OrderStatusUpdatedNotification::class,
            fn ($notification) => $notification->newStatus === 'shipped',
        );
    }

    public function test_online_order_delivered_dispatches_notification(): void
    {
        Notification::fake();

        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'source' => 'online',
            'status' => 'shipped',
        ]);

        $this->patchJson("/api/orders/{$order->id}/status", [
            'status' => 'delivered',
        ], $this->adminHeaders);

        Notification::assertSentTo(
            $customer,
            \App\Modules\Sales\Notifications\OrderStatusUpdatedNotification::class,
            fn ($notification) => $notification->newStatus === 'delivered',
        );
    }

    public function test_pos_order_does_not_dispatch_notification(): void
    {
        Notification::fake();

        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'source' => 'pos',
            'status' => 'pending',
        ]);

        $this->patchJson("/api/orders/{$order->id}/status", [
            'status' => 'completed',
        ], $this->adminHeaders);

        Notification::assertNothingSent();
    }

    public function test_online_order_without_customer_does_not_notify(): void
    {
        Notification::fake();

        $order = Order::factory()->create([
            'customer_id' => null,
            'source' => 'online',
            'status' => 'processing',
        ]);

        $this->patchJson("/api/orders/{$order->id}/status", [
            'status' => 'shipped',
        ], $this->adminHeaders);

        Notification::assertNothingSent();
    }
}
