<?php

namespace Tests\Feature\Api;

use App\Modules\Payment\Gateways\StripeGateway;
use App\Modules\Customer\Models\Address;
use App\Modules\Customer\Models\Customer;
use App\Modules\ECommerce\Models\CartItem;
use App\Modules\Store\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class StripePaymentTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private Customer $customer;

    private Address $address;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::factory()->create(['slug' => 'test-store', 'name' => 'Test Store']);

        $this->customer = Customer::factory()->create([
            'store_id' => $this->store->id,
        ]);

        $category = \App\Modules\Catalog\Models\Category::create([
            'name' => 'Test Category', 'slug' => 'test-cat', 'store_id' => $this->store->id,
        ]);

        $product = \App\Modules\Catalog\Models\Product::factory()->create([
            'category_id' => $category->id,
            'base_price' => 50,
            'store_id' => $this->store->id,
        ]);

        $variant = \App\Modules\Catalog\Models\ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 10,
            'price_adjustment' => 0,
        ]);

        CartItem::create([
            'customer_id' => $this->customer->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $this->address = Address::create([
            'customer_id' => $this->customer->id,
            'type' => 'shipping',
            'name' => 'Test',
            'phone' => '09123456789',
            'street' => '123 Main St',
            'city' => 'Yangon',
            'state' => 'Yangon',
            'postal_code' => '11000',
            'is_default' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function mockStripeGateway(): void
    {
        $mock = Mockery::mock(StripeGateway::class);
        $mock->shouldReceive('createIntent')
            ->andReturn([
                'gateway' => 'stripe',
                'transaction_id' => 'pi_test_001',
                'client_secret' => 'pi_test_001_secret_xxx',
                'status' => 'requires_payment_method',
            ]);

        $this->app->instance(StripeGateway::class, $mock);
    }

    public function test_stripe_payment_intent_creates_transaction(): void
    {
        $this->mockStripeGateway();

        $response = $this->actingAs($this->customer, 'customer')
            ->withHeader('X-Store', 'test-store')
            ->postJson('/api/checkout/payment-intent', [
                'payment_method' => 'stripe',
            ]);

        $response->assertOk()
            ->assertJson([
                'gateway' => 'stripe',
                'transaction_id' => 'pi_test_001',
                'status' => 'requires_payment_method',
            ]);
    }

    public function test_stripe_checkout_creates_payment_record(): void
    {
        $this->mockStripeGateway();

        // Create intent.
        $intentRes = $this->actingAs($this->customer, 'customer')
            ->withHeader('X-Store', 'test-store')
            ->postJson('/api/checkout/payment-intent', [
                'payment_method' => 'stripe',
            ]);

        $intentRes->assertOk();
        $txId = $intentRes->json('transaction_id');

        // Checkout with Stripe.
        $checkoutRes = $this->actingAs($this->customer, 'customer')
            ->withHeader('X-Store', 'test-store')
            ->postJson('/api/checkout', [
                'address_id' => $this->address->id,
                'payment_method' => 'stripe',
                'payment_intent_id' => $txId,
            ]);

        $checkoutRes->assertCreated()
            ->assertJsonPath('order.status', 'processing');

        $orderId = $checkoutRes->json('order.id');

        $this->assertDatabaseHas('payments', [
            'order_id' => $orderId,
            'method' => 'stripe',
            'gateway' => 'stripe',
            'transaction_id' => $txId,
            'gateway_status' => 'pending',
        ]);
    }

    public function test_stripe_intent_requires_auth(): void
    {
        $response = $this->postJson('/api/checkout/payment-intent', [
            'payment_method' => 'stripe',
        ]);

        $response->assertUnauthorized();
    }
}
