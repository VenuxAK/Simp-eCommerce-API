<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->headers = ['Authorization' => "Bearer {$user->createToken('test')->plainTextToken}"];
    }

    public function test_can_list_products(): void
    {
        $category = Category::factory()->create();
        Product::factory(3)->create(['category_id' => $category->id]);

        $response = $this->getJson('/api/products', $this->headers);

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_can_create_product_with_variants(): void
    {
        $category = Category::factory()->create();

        $response = $this->postJson('/api/products', [
            'category_id' => $category->id,
            'name' => 'Classic Tee',
            'base_price' => 29.99,
            'variants' => [
                ['sku' => 'TEE-BLK-M', 'size' => 'M', 'color' => 'Black', 'stock_quantity' => 10],
                ['sku' => 'TEE-WHT-L', 'size' => 'L', 'color' => 'White', 'stock_quantity' => 5],
            ],
        ], $this->headers);

        $response->assertCreated()->assertJsonPath('data.name', 'Classic Tee');
        $this->assertDatabaseHas('product_variants', ['sku' => 'TEE-BLK-M']);
    }

    public function test_product_requires_at_least_one_variant(): void
    {
        $category = Category::factory()->create();

        $response = $this->postJson('/api/products', [
            'category_id' => $category->id,
            'name' => 'No Variants',
            'base_price' => 10,
            'variants' => [],
        ], $this->headers);

        $response->assertUnprocessable();
    }

    public function test_variant_sku_must_be_unique(): void
    {
        $category = Category::factory()->create();
        ProductVariant::factory()->create(['sku' => 'DUP-SKU']);

        $response = $this->postJson('/api/products', [
            'category_id' => $category->id,
            'name' => 'Duplicate SKU',
            'base_price' => 10,
            'variants' => [['sku' => 'DUP-SKU', 'stock_quantity' => 1]],
        ], $this->headers);

        $response->assertUnprocessable();
    }

    public function test_can_show_product(): void
    {
        $product = Product::factory()->has(ProductVariant::factory(2), 'variants')->create();

        $response = $this->getJson("/api/products/{$product->id}", $this->headers);

        $response->assertOk()->assertJsonPath('data.id', $product->id);
    }

    public function test_can_update_product(): void
    {
        $product = Product::factory()->create(['name' => 'Old Name']);

        $response = $this->putJson("/api/products/{$product->id}", [
            'name' => 'New Name',
        ], $this->headers);

        $response->assertOk()->assertJsonPath('data.name', 'New Name');
    }

    public function test_can_update_product_variants_in_place(): void
    {
        $product = Product::factory()->has(ProductVariant::factory(), 'variants')->create();
        $variant = $product->variants()->first();

        $response = $this->putJson("/api/products/{$product->id}", [
            'variants' => [
                ['id' => $variant->id, 'sku' => 'UPDATED-SKU', 'stock_quantity' => 20],
            ],
        ], $this->headers);

        $response->assertOk();
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'sku' => 'UPDATED-SKU']);
    }

    public function test_cannot_delete_product_with_order_history(): void
    {
        $product = Product::factory()->has(ProductVariant::factory(), 'variants')->create();
        $variant = $product->variants()->first();
        $order = Order::factory()->create();
        OrderItem::factory()->create(['order_id' => $order->id, 'product_variant_id' => $variant->id]);

        $response = $this->deleteJson("/api/products/{$product->id}", [], $this->headers);

        $response->assertUnprocessable();
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_can_delete_product_without_orders(): void
    {
        $product = Product::factory()->has(ProductVariant::factory(), 'variants')->create();

        $response = $this->deleteJson("/api/products/{$product->id}", [], $this->headers);

        $response->assertOk();
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_can_update_variant_stock(): void
    {
        $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);

        $response = $this->patchJson("/api/variants/{$variant->id}/stock", [
            'quantity' => 20,
        ], $this->headers);

        $response->assertOk();
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock_quantity' => 20]);
    }

    public function test_cannot_set_negative_stock(): void
    {
        $variant = ProductVariant::factory()->create();

        $response = $this->patchJson("/api/variants/{$variant->id}/stock", [
            'quantity' => -5,
        ], $this->headers);

        $response->assertUnprocessable();
    }
}
