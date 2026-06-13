<?php

namespace Tests\Feature\Api;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Customer\Models\Address;
use App\Modules\Customer\Models\Customer;
use App\Modules\ECommerce\Models\CartItem;
use App\Modules\ECommerce\Notifications\OrderConfirmationNotification;
use App\Modules\Store\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CheckoutTest extends TestCase
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

        $this->customer = Customer::factory()->create([
            'store_id' => $this->store->id,
        ]);

        $this->variant = $this->createVariant(10, 50);

        CartItem::create([
            'customer_id' => $this->customer->id,
            'product_variant_id' => $this->variant->id,
            'quantity' => 2,
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
        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'store_id' => $this->store->id,
        ]);

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'base_price' => $price,
            'store_id' => $this->store->id,
        ]);

        return ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => $stockQty,
            'price_adjustment' => 0,
        ]);
    }

    public function test_customer_can_checkout(): void
    {
        $response = $this->actingAs($this->customer, 'customer')
            ->withHeader('X-Store', 'test-store')
            ->postJson('/api/checkout', [
                'address_id' => $this->address->id,
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'message', 'order' => ['id', 'order_number', 'total_amount', 'status', 'source'],
            ]);

        $orderData = $response->json('order');
        $this->assertEquals('processing', $orderData['status']);
        $this->assertEquals('online', $orderData['source']);
        $this->assertEquals(100, $orderData['total_amount']);
    }

    public function test_checkout_clears_cart(): void
    {
        $this->actingAs($this->customer, 'customer')
            ->withHeader('X-Store', 'test-store')
            ->postJson('/api/checkout', [
                'address_id' => $this->address->id,
            ]);

        $this->assertDatabaseMissing('cart_items', [
            'customer_id' => $this->customer->id,
        ]);
    }

    public function test_checkout_deducts_stock(): void
    {
        $this->actingAs($this->customer, 'customer')
            ->withHeader('X-Store', 'test-store')
            ->postJson('/api/checkout', [
                'address_id' => $this->address->id,
            ]);

        $this->assertDatabaseHas('product_variants', [
            'id' => $this->variant->id,
            'stock_quantity' => 8,
        ]);
    }

    public function test_checkout_creates_invoice_and_shipment(): void
    {
        $response = $this->actingAs($this->customer, 'customer')
            ->withHeader('X-Store', 'test-store')
            ->postJson('/api/checkout', [
                'address_id' => $this->address->id,
            ]);

        $orderId = $response->json('order.id');

        $this->assertDatabaseHas('invoices', ['order_id' => $orderId]);
        $this->assertDatabaseHas('shipments', ['order_id' => $orderId, 'address_id' => $this->address->id]);
    }

    public function test_checkout_dispatches_order_confirmation_notification(): void
    {
        Notification::fake();

        $this->actingAs($this->customer, 'customer')
            ->withHeader('X-Store', 'test-store')
            ->postJson('/api/checkout', [
                'address_id' => $this->address->id,
            ]);

        Notification::assertSentTo(
            $this->customer,
            OrderConfirmationNotification::class,
        );
    }

    public function test_checkout_fails_with_empty_cart(): void
    {
        CartItem::where('customer_id', $this->customer->id)->delete();

        $response = $this->actingAs($this->customer, 'customer')
            ->withHeader('X-Store', 'test-store')
            ->postJson('/api/checkout', [
                'address_id' => $this->address->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Cart is empty.');
    }

    public function test_checkout_fails_with_invalid_address(): void
    {
        $response = $this->actingAs($this->customer, 'customer')
            ->withHeader('X-Store', 'test-store')
            ->postJson('/api/checkout', [
                'address_id' => 9999,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['address_id']);
    }

    public function test_checkout_fails_with_insufficient_stock(): void
    {
        CartItem::where('customer_id', $this->customer->id)->update(['quantity' => 999]);

        $response = $this->actingAs($this->customer, 'customer')
            ->withHeader('X-Store', 'test-store')
            ->postJson('/api/checkout', [
                'address_id' => $this->address->id,
            ]);

        $response->assertUnprocessable();
    }

    public function test_checkout_requires_authentication(): void
    {
        $response = $this->postJson('/api/checkout', [
            'address_id' => $this->address->id,
        ]);

        $response->assertUnauthorized();
    }

    public function test_validate_stock_returns_warnings(): void
    {
        $response = $this->actingAs($this->customer, 'customer')
            ->withHeader('X-Store', 'test-store')
            ->getJson('/api/checkout/validate');

        $response->assertOk()
            ->assertJsonStructure(['items']);
    }
}
