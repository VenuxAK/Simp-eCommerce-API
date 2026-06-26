<?php

namespace Tests\Feature\Api;

use App\Modules\Customer\Models\Address;
use App\Modules\Customer\Models\Customer;
use App\Modules\ECommerce\Models\CartItem;
use App\Modules\Payment\Models\PaymentTransaction;
use App\Modules\Store\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentTest extends TestCase
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

    public function test_payment_intent_creates_transaction_record(): void
    {
        Http::fake([
            '*' => Http::response([
                'transactionId' => 'mmp_txn_test_001',
                'qrCode' => 'data:image/png;base64,xxxx',
                'qrUrl' => 'https://pay.myanmyanpay.com/qr/test',
                'status' => 'pending',
            ], 200),
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->withHeader('X-Store', 'test-store')
            ->postJson('/api/checkout/payment-intent', [
                'payment_method' => 'mmpay',
            ]);

        $response->assertOk()
            ->assertJson([
                'gateway' => 'mmpay',
                'transaction_id' => 'mmp_txn_test_001',
                'status' => 'pending',
            ]);

        $this->assertDatabaseHas('payment_transactions', [
            'transaction_id' => 'mmp_txn_test_001',
            'gateway' => 'mmpay',
            'gateway_status' => 'pending',
        ]);
    }

    public function test_payment_intent_requires_auth(): void
    {
        $response = $this->postJson('/api/checkout/payment-intent', [
            'payment_method' => 'mmpay',
        ]);

        $response->assertUnauthorized();
    }

    public function test_payment_intent_requires_valid_method(): void
    {
        $response = $this->actingAs($this->customer, 'customer')
            ->withHeader('X-Store', 'test-store')
            ->postJson('/api/checkout/payment-intent', [
                'payment_method' => 'invalid',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_method']);
    }

    public function test_payment_transaction_created(): void
    {
        Http::fake([
            '*' => Http::response([
                'transactionId' => 'mmp_txn_test_002',
                'qrCode' => 'data:image/png;base64,xxxx',
                'qrUrl' => 'https://pay.myanmyanpay.com/qr/test',
                'status' => 'pending',
            ], 200),
        ]);

        $intentResponse = $this->actingAs($this->customer, 'customer')
            ->withHeader('X-Store', 'test-store')
            ->postJson('/api/checkout/payment-intent', [
                'payment_method' => 'mmpay',
            ]);

        $intentResponse->assertOk();

        $txId = $intentResponse->json('transaction_id');        // Checkout using the transaction.
        $checkoutResponse = $this->actingAs($this->customer, 'customer')
            ->withHeader('X-Store', 'test-store')
            ->postJson('/api/checkout', [
                'address_id' => $this->address->id,
                'payment_method' => 'mmpay',
                'payment_transaction_id' => $txId,
            ]);

        $checkoutResponse->assertCreated()
            ->assertJsonPath('order.status', 'processing');

        // Verify payment was recorded.
        $orderId = $checkoutResponse->json('order.id');
        $this->assertDatabaseHas('payments', [
            'order_id' => $orderId,
            'method' => 'mmpay',
            'gateway' => 'mmpay',
            'transaction_id' => $txId,
        ]);

        // Verify transaction was linked to order and payment.
        $this->assertDatabaseHas('payment_transactions', [
            'transaction_id' => $txId,
            'order_id' => $orderId,
        ]);
    }
}
