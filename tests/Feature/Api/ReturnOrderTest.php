<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnOrderTest extends TestCase
{
    use RefreshDatabase;

    private array $adminHeaders;
    private array $staffHeaders;
    private int $orderId;
    private int $variantId;
    private int $orderItemId;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);
        $this->adminHeaders = ['Authorization' => "Bearer {$admin->createToken('test')->plainTextToken}"];
        $this->staffHeaders = ['Authorization' => "Bearer {$staff->createToken('test')->plainTextToken}"];

        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'base_price' => 50]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'stock_quantity' => 10, 'price_adjustment' => 0]);
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
        $staffUser = User::factory()->create(['role' => 'staff']);
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
        $order = \App\Models\Order::create([
            'user_id' => User::where('role', 'admin')->first()->id,
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
