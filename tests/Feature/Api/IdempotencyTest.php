<?php

namespace Tests\Feature\Api;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Customer\Models\Address;
use App\Modules\Customer\Models\Customer;
use App\Modules\ECommerce\Models\CartItem;
use App\Modules\Store\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class IdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private Customer $customer;

    private ProductVariant $variant;

    private Address $address;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::factory()->create(['slug' => 'test-store', 'name' => 'Test Store']);
        $this->customer = Customer::factory()->create(['store_id' => $this->store->id]);
        $this->variant = $this->createVariant(10, 50);

        CartItem::create([
            'customer_id' => $this->customer->id,
            'product_variant_id' => $this->variant->id,
            'quantity' => 1,
        ]);

        $this->address = Address::create([
            'customer_id' => $this->customer->id,
            'type' => 'shipping',
            'name' => 'Test Customer',
            'phone' => '09123456789',
            'street' => '123 Main St',
            'city' => 'Yangon',
            'state' => 'Yangon',
            'postal_code' => '11000',
            'is_default' => true,
        ]);
    }

    private function createVariant(int $stockQty = 10, float $price = 50): ProductVariant
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'base_price' => $price]);

        return ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => $stockQty,
            'price_adjustment' => 0,
        ]);
    }

    public function test_valid_idempotency_key_succeeds_and_caches(): void
    {
        $key = (string) Str::uuid();

        // First request - performs checkout
        $response1 = $this->actingAs($this->customer, 'customer')
            ->withHeader('X-Store', 'test-store')
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/checkout', [
                'address_id' => $this->address->id,
            ]);

        $response1->assertCreated();

        // Second request with same key - returns identical cached response
        $response2 = $this->actingAs($this->customer, 'customer')
            ->withHeader('X-Store', 'test-store')
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/checkout', [
                'address_id' => $this->address->id,
            ]);

        $response2->assertStatus($response1->status());
        $this->assertEquals($response1->content(), $response2->content());
    }

    public function test_invalid_idempotency_key_returns_400(): void
    {
        // Too short key
        $response = $this->actingAs($this->customer, 'customer')
            ->withHeader('X-Store', 'test-store')
            ->withHeader('Idempotency-Key', 'short')
            ->postJson('/api/v1/checkout', [
                'address_id' => $this->address->id,
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Invalid Idempotency-Key format. Must be alphanumeric (plus - and _) between 8 and 128 characters.');

        // Malicious chars
        $response = $this->actingAs($this->customer, 'customer')
            ->withHeader('X-Store', 'test-store')
            ->withHeader('Idempotency-Key', 'key_with_@_special')
            ->postJson('/api/v1/checkout', [
                'address_id' => $this->address->id,
            ]);

        $response->assertStatus(400);
    }
}
