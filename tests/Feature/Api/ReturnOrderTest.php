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

    private array $headers;
    private int $orderId;
    private int $variantId;
    private int $orderItemId;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->headers = ['Authorization' => "Bearer {$user->createToken('test')->plainTextToken}"];

        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'base_price' => 50]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'stock_quantity' => 10, 'price_adjustment' => 0]);
        $this->variantId = $variant->id;

        $orderRes = $this->postJson('/api/orders', [
            'items' => [['product_variant_id' => $variant->id, 'quantity' => 2]],
            'payment' => ['method' => 'cash', 'amount' => 100],
        ], $this->headers);

        $this->orderId = $orderRes->json('data.id');
        $this->orderItemId = $orderRes->json('data.items.0.id');
    }

    public function test_can_return_items(): void
    {
        $stockBefore = ProductVariant::find($this->variantId)->stock_quantity;

        $response = $this->postJson("/api/orders/{$this->orderId}/return", [
            'items' => [['order_item_id' => $this->orderItemId, 'quantity' => 1, 'reason' => 'Wrong size']],
        ], $this->headers);

        $response->assertOk();
        $this->assertEquals($stockBefore + 1, ProductVariant::find($this->variantId)->stock_quantity);
    }

    public function test_cannot_return_more_than_ordered(): void
    {
        $response = $this->postJson("/api/orders/{$this->orderId}/return", [
            'items' => [['order_item_id' => $this->orderItemId, 'quantity' => 99]],
        ], $this->headers);

        $response->assertUnprocessable();
    }
}
