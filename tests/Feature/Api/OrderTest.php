<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private array $headers;
    private array $validPayload;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->headers = ['Authorization' => "Bearer {$user->createToken('test')->plainTextToken}"];

        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'base_price' => 50]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 10,
            'price_adjustment' => 0,
        ]);

        $this->validPayload = [
            'items' => [['product_variant_id' => $variant->id, 'quantity' => 2]],
            'payment' => ['method' => 'cash', 'amount' => 100],
        ];
    }

    public function test_can_create_order(): void
    {
        $response = $this->postJson('/api/orders', $this->validPayload, $this->headers);
        $response->assertCreated()->assertJsonStructure([
            'data' => ['id', 'order_number', 'total_amount', 'status', 'items', 'payment', 'invoice'],
        ]);
    }

    public function test_order_deducts_stock(): void
    {
        $variant = ProductVariant::find($this->validPayload['items'][0]['product_variant_id']);
        $initialStock = $variant->stock_quantity;

        $response = $this->postJson('/api/orders', $this->validPayload, $this->headers);

        $response->assertCreated();
        $this->assertEquals($initialStock - 2, $variant->fresh()->stock_quantity);
    }

    public function test_cannot_order_with_insufficient_stock(): void
    {
        $response = $this->postJson('/api/orders', [
            'items' => [['product_variant_id' => $this->validPayload['items'][0]['product_variant_id'], 'quantity' => 999]],
            'payment' => ['method' => 'cash', 'amount' => 99999],
        ], $this->headers);

        $response->assertUnprocessable();
    }

    public function test_cannot_order_with_underpayment(): void
    {
        $response = $this->postJson('/api/orders', [
            'items' => $this->validPayload['items'],
            'payment' => ['method' => 'cash', 'amount' => 1],
        ], $this->headers);

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
        ], $this->headers);

        $response->assertUnprocessable();
    }

    public function test_order_with_customer_awards_loyalty_points(): void
    {
        $customer = Customer::factory()->create(['loyalty_points' => 0]);
        $this->validPayload['customer_id'] = $customer->id;

        $this->postJson('/api/orders', $this->validPayload, $this->headers);

        $this->assertGreaterThan(0, $customer->fresh()->loyalty_points);
    }

    public function test_can_list_orders(): void
    {
        $this->postJson('/api/orders', $this->validPayload, $this->headers);

        $response = $this->getJson('/api/orders', $this->headers);
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_show_order(): void
    {
        $create = $this->postJson('/api/orders', $this->validPayload, $this->headers);
        $orderId = $create->json('data.id');

        $response = $this->getJson("/api/orders/{$orderId}", $this->headers);
        $response->assertOk()->assertJsonPath('data.id', $orderId);
    }

    public function test_can_cancel_order_and_restore_stock(): void
    {
        $variant = ProductVariant::find($this->validPayload['items'][0]['product_variant_id']);
        $initialStock = $variant->stock_quantity;

        $create = $this->postJson('/api/orders', $this->validPayload, $this->headers);
        $orderId = $create->json('data.id');
        $this->assertEquals($initialStock - 2, $variant->fresh()->stock_quantity);

        $this->patchJson("/api/orders/{$orderId}/status", ['status' => 'cancelled'], $this->headers);

        $this->assertEquals($initialStock, $variant->fresh()->stock_quantity);
    }

    public function test_double_cancel_is_idempotent(): void
    {
        $variant = ProductVariant::find($this->validPayload['items'][0]['product_variant_id']);
        $create = $this->postJson('/api/orders', $this->validPayload, $this->headers);
        $orderId = $create->json('data.id');

        $this->patchJson("/api/orders/{$orderId}/status", ['status' => 'cancelled'], $this->headers);
        $afterFirst = $variant->fresh()->stock_quantity;

        $response = $this->patchJson("/api/orders/{$orderId}/status", ['status' => 'cancelled'], $this->headers);
        $response->assertUnprocessable();
        $this->assertEquals($afterFirst, $variant->fresh()->stock_quantity);
    }

    public function test_order_creates_invoice_automatically(): void
    {
        $this->postJson('/api/orders', $this->validPayload, $this->headers);

        $this->assertDatabaseCount('invoices', 1);
    }
}
