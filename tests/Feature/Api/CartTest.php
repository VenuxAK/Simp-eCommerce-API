<?php

namespace Tests\Feature\Api;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Customer\Models\Customer;
use App\Modules\ECommerce\Models\CartItem;
use App\Modules\Store\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private Customer $customer;

    private ProductVariant $variant1;

    private ProductVariant $variant2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::factory()->create(['slug' => 'test-store', 'name' => 'Test Store']);
        $this->customer = Customer::factory()->create(['store_id' => $this->store->id]);

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'store_id' => $this->store->id,
        ]);

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'base_price' => 50,
            'store_id' => $this->store->id,
        ]);

        $this->variant1 = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 10,
            'price_adjustment' => 0,
        ]);

        $this->variant2 = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 5,
            'price_adjustment' => 10,
        ]);
    }

    public function test_customer_can_retrieve_cart(): void
    {
        CartItem::create([
            'customer_id' => $this->customer->id,
            'product_variant_id' => $this->variant1->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->withHeader('X-Store', 'test-store')
            ->getJson('/api/v1/cart');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.product_variant_id', $this->variant1->id);
    }

    public function test_customer_can_add_item_to_cart(): void
    {
        $response = $this->actingAs($this->customer, 'customer')
            ->withHeader('X-Store', 'test-store')
            ->postJson('/api/v1/cart', [
                'product_variant_id' => $this->variant1->id,
                'quantity' => 3,
            ]);

        $response->assertCreated()
            ->assertJsonPath('product_variant_id', $this->variant1->id)
            ->assertJsonPath('quantity', 3);

        $this->assertDatabaseHas('cart_items', [
            'customer_id' => $this->customer->id,
            'product_variant_id' => $this->variant1->id,
            'quantity' => 3,
        ]);
    }

    public function test_customer_cannot_add_item_with_insufficient_stock(): void
    {
        $response = $this->actingAs($this->customer, 'customer')
            ->withHeader('X-Store', 'test-store')
            ->postJson('/api/v1/cart', [
                'product_variant_id' => $this->variant2->id,
                'quantity' => 10, // Stock is only 5
            ]);

        $response->assertStatus(422);
    }

    public function test_customer_can_batch_sync_cart(): void
    {
        // Existing cart item
        CartItem::create([
            'customer_id' => $this->customer->id,
            'product_variant_id' => $this->variant1->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->withHeader('X-Store', 'test-store')
            ->postJson('/api/v1/cart/sync', [
                'items' => [
                    [
                        'product_variant_id' => $this->variant1->id, // Should sum with existing: 1 + 2 = 3
                        'quantity' => 2,
                    ],
                    [
                        'product_variant_id' => $this->variant2->id, // New item
                        'quantity' => 2,
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJsonCount(2);

        $this->assertDatabaseHas('cart_items', [
            'customer_id' => $this->customer->id,
            'product_variant_id' => $this->variant1->id,
            'quantity' => 3,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'customer_id' => $this->customer->id,
            'product_variant_id' => $this->variant2->id,
            'quantity' => 2,
        ]);
    }

    public function test_customer_can_update_cart_item_quantity(): void
    {
        $item = CartItem::create([
            'customer_id' => $this->customer->id,
            'product_variant_id' => $this->variant1->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->withHeader('X-Store', 'test-store')
            ->putJson("/api/v1/cart/{$item->id}", [
                'quantity' => 5,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('cart_items', [
            'id' => $item->id,
            'quantity' => 5,
        ]);
    }

    public function test_customer_can_remove_cart_item(): void
    {
        $item = CartItem::create([
            'customer_id' => $this->customer->id,
            'product_variant_id' => $this->variant1->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->withHeader('X-Store', 'test-store')
            ->deleteJson("/api/v1/cart/{$item->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('cart_items', [
            'id' => $item->id,
        ]);
    }

    public function test_customer_can_clear_cart(): void
    {
        CartItem::create([
            'customer_id' => $this->customer->id,
            'product_variant_id' => $this->variant1->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->withHeader('X-Store', 'test-store')
            ->deleteJson('/api/v1/cart');

        $response->assertOk();
        $this->assertDatabaseMissing('cart_items', [
            'customer_id' => $this->customer->id,
        ]);
    }
}
