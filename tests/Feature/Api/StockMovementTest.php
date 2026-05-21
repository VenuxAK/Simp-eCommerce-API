<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementTest extends TestCase
{
    use RefreshDatabase;

    private array $headers;
    private int $variantId;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->headers = ['Authorization' => "Bearer {$user->createToken('test')->plainTextToken}"];

        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'base_price' => 50]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'stock_quantity' => 10, 'price_adjustment' => 0]);
        $this->variantId = $variant->id;
    }

    public function test_stock_movement_created_on_order(): void
    {
        $this->postJson('/api/orders', [
            'items' => [['product_variant_id' => $this->variantId, 'quantity' => 2]],
            'payment' => ['method' => 'cash', 'amount' => 100],
        ], $this->headers)->assertCreated();

        $response = $this->getJson('/api/stock-movements', $this->headers);
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(-2, $response->json('data.0.quantity_change'));
        $this->assertEquals('sale', $response->json('data.0.reason'));
    }

    public function test_stock_movement_created_on_adjustment(): void
    {
        $this->patchJson("/api/variants/{$this->variantId}/stock", ['quantity' => 20], $this->headers);

        $response = $this->getJson('/api/stock-movements', $this->headers);
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(10, $response->json('data.0.quantity_change'));
        $this->assertEquals('adjustment', $response->json('data.0.reason'));
    }

    public function test_stock_movement_created_on_cancel(): void
    {
        $createRes = $this->postJson('/api/orders', [
            'items' => [['product_variant_id' => $this->variantId, 'quantity' => 2]],
            'payment' => ['method' => 'cash', 'amount' => 100],
        ], $this->headers);
        $orderId = $createRes->json('data.id');

        $this->patchJson("/api/orders/{$orderId}/status", ['status' => 'cancelled'], $this->headers);

        $response = $this->getJson('/api/stock-movements', $this->headers);
        $this->assertEquals(2, $response->json('data.1.quantity_change'));
        $this->assertEquals('cancelled', $response->json('data.1.reason'));
    }
}
