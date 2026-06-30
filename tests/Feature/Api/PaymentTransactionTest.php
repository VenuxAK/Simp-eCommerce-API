<?php

namespace Tests\Feature\Api;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Identity\Models\User;
use App\Modules\Payment\Gateways\StripeGateway;
use App\Modules\Payment\Models\PaymentTransaction;
use App\Modules\Sales\Models\Order;
use App\Modules\Sales\Models\Payment;
use App\Modules\Store\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\ApiTestCase;

class PaymentTransactionTest extends ApiTestCase
{
    use RefreshDatabase;

    private Order $order;

    private Payment $payment;

    private PaymentTransaction $transaction;

    protected function setUp(): void
    {
        parent::setUp();

        $store = Store::where('slug', 'main')->first();

        // Create an order and payment to reference in transactions
        $category = Category::create([
            'name' => 'Cat', 'slug' => 'cat', 'store_id' => $store->id,
        ]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'base_price' => 100,
            'store_id' => $store->id,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 10,
        ]);

        $this->order = Order::create([
            'store_id' => $store->id,
            'user_id' => $this->adminUser->id,
            'order_number' => 'ORD-20260630-0001',
            'total_amount' => 100,
            'status' => 'processing',
            'source' => 'pos',
        ]);

        $this->payment = Payment::create([
            'order_id' => $this->order->id,
            'method' => 'stripe',
            'amount' => 100,
            'gateway' => 'stripe',
            'transaction_id' => 'pi_test_webhook',
            'gateway_status' => 'pending',
        ]);

        $this->transaction = PaymentTransaction::create([
            'payment_id' => $this->payment->id,
            'order_id' => $this->order->id,
            'gateway' => 'stripe',
            'transaction_id' => 'pi_test_webhook',
            'gateway_status' => 'pending',
            'amount' => 100,
            'currency' => 'MMK',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_staff_with_permission_can_list_payment_transactions(): void
    {
        $response = $this->getJson('/api/v1/payment-transactions', $this->adminHeaders);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'payment_id',
                        'order_id',
                        'gateway',
                        'transaction_id',
                        'gateway_status',
                        'amount',
                        'currency',
                    ],
                ],
            ]);
    }

    public function test_staff_without_permission_cannot_list_payment_transactions(): void
    {
        $unprivilegedUser = User::factory()->create(['store_id' => $this->staffUser->store_id]);
        $headers = ['Authorization' => "Bearer {$unprivilegedUser->createToken('test')->plainTextToken}"];

        $response = $this->getJson('/api/v1/payment-transactions', $headers);

        $response->assertForbidden();
    }

    public function test_stripe_webhook_rejects_invalid_signature(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_secret']);

        // Since signature is not valid, it should fail validation
        $response = $this->withHeader('Stripe-Signature', 'invalid')
            ->postJson('/api/v1/stripe/webhook', [
                'type' => 'payment_intent.succeeded',
                'data' => [
                    'object' => [
                        'id' => 'pi_test_webhook',
                    ],
                ],
            ]);

        $response->assertStatus(400);
    }

    public function test_stripe_webhook_processes_valid_signature(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_secret']);

        $mock = Mockery::mock(StripeGateway::class);
        $mock->shouldReceive('verifyWebhook')
            ->once()
            ->andReturn(true);
        $this->app->instance(StripeGateway::class, $mock);

        $response = $this->withHeader('Stripe-Signature', 'valid_sig')
            ->postJson('/api/v1/stripe/webhook', [
                'type' => 'payment_intent.succeeded',
                'data' => [
                    'object' => [
                        'id' => 'pi_test_webhook',
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'OK');

        $this->assertDatabaseHas('payment_transactions', [
            'transaction_id' => 'pi_test_webhook',
            'gateway_status' => 'paid',
        ]);

        $this->assertDatabaseHas('payments', [
            'transaction_id' => 'pi_test_webhook',
            'gateway_status' => 'paid',
        ]);
    }
}
