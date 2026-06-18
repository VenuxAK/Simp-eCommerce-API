<?php

namespace Tests\Feature\Api;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Identity\Models\User;
use App\Modules\Sales\Models\Order;
use Tests\ApiTestCase;

class ReturnOrderTest extends ApiTestCase
{
    private int $orderId;

    private int $variantId;

    private int $orderItemId;

    protected function setUp(): void
    {
        parent::setUp();
        $variant = $this->createVariant(10, 50);
        $this->variantId = $variant->id;

        $orderRes = $this->postJson('/api/orders', [
            'items' => [['product_variant_id' => $variant->id, 'quantity' => 2]],
            'payment' => ['method' => 'cash', 'amount' => 100],
        ], $this->adminHeaders);

        $this->orderId = $orderRes->json('data.id');
        $this->orderItemId = $orderRes->json('data.items.0.id');
    }

    public function test_can_return_items(): void
    {
        $stockBefore = ProductVariant::find($this->variantId)->stock_quantity;

        $response = $this->postJson("/api/orders/{$this->orderId}/return", [
            'items' => [['order_item_id' => $this->orderItemId, 'quantity' => 1, 'reason' => 'Wrong size']],
        ], $this->adminHeaders);

        $response->assertOk();
        $this->assertEquals($stockBefore + 1, ProductVariant::find($this->variantId)->stock_quantity);
    }

    public function test_cannot_return_more_than_ordered(): void
    {
        $response = $this->postJson("/api/orders/{$this->orderId}/return", [
            'items' => [['order_item_id' => $this->orderItemId, 'quantity' => 99]],
        ], $this->adminHeaders);

        $response->assertUnprocessable();
    }

    public function test_staff_cannot_return_order(): void
    {
        $staffUser = User::factory()->salesStaff()->create();
        $this->actingAs($staffUser, 'sanctum');

        $response = $this->postJson("/api/orders/{$this->orderId}/return", [
            'items' => [['order_item_id' => $this->orderItemId, 'quantity' => 1]],
        ]);
        $response->assertForbidden();
    }

    public function test_cannot_return_partial_then_exceed_remaining(): void
    {
        $response = $this->postJson("/api/orders/{$this->orderId}/return", [
            'items' => [['order_item_id' => $this->orderItemId, 'quantity' => 1]],
        ], $this->adminHeaders);
        $response->assertOk();

        $response = $this->postJson("/api/orders/{$this->orderId}/return", [
            'items' => [['order_item_id' => $this->orderItemId, 'quantity' => 2]],
        ], $this->adminHeaders);
        $response->assertUnprocessable();
    }

    public function test_can_return_partial_then_remaining(): void
    {
        $stockBefore = ProductVariant::find($this->variantId)->stock_quantity;

        $this->postJson("/api/orders/{$this->orderId}/return", [
            'items' => [['order_item_id' => $this->orderItemId, 'quantity' => 1]],
        ], $this->adminHeaders);

        $stockAfterFirst = ProductVariant::find($this->variantId)->stock_quantity;
        $this->assertEquals($stockBefore + 1, $stockAfterFirst);

        $this->postJson("/api/orders/{$this->orderId}/return", [
            'items' => [['order_item_id' => $this->orderItemId, 'quantity' => 1]],
        ], $this->adminHeaders);

        $stockAfterSecond = ProductVariant::find($this->variantId)->stock_quantity;
        $this->assertEquals($stockBefore + 2, $stockAfterSecond);
    }

    public function test_cannot_return_pending_order(): void
    {
        $order = Order::create([
            'user_id' => User::role('root')->first()->id,
            'store_id' => 1,
            'order_number' => 'ORD-PEND-RET',
            'total_amount' => 50,
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/orders/{$order->id}/return", [
            'items' => [['order_item_id' => 999, 'quantity' => 1]],
        ], $this->adminHeaders);

        $response->assertUnprocessable();
    }
}
