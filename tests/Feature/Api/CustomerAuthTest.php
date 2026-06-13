<?php

namespace Tests\Feature\Api;

use App\Modules\Customer\Models\Customer;
use App\Modules\Store\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    private const STORE_SLUG = 'test-store';

    // ─── Register ──────────────────────────────────────────────

    public function test_customer_can_register(): void
    {
        Store::factory()->create(['slug' => self::STORE_SLUG]);

        $response = $this->withHeader('X-Store', self::STORE_SLUG)
            ->postJson('/api/customer/register', [
                'name' => 'New Customer',
                'email' => 'new@customer.com',
                'password' => 'Pass1234',
                'password_confirmation' => 'Pass1234',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'new@customer.com');

        $this->assertDatabaseHas('customers', ['email' => 'new@customer.com']);
    }

    // ─── Login ─────────────────────────────────────────────────

    public function test_customer_can_login(): void
    {
        Store::factory()->create(['slug' => self::STORE_SLUG]);
        Customer::factory()->create([
            'email' => 'login@test.com',
            'password' => bcrypt('Pass1234'),
        ]);

        $response = $this->withHeader('X-Store', self::STORE_SLUG)
            ->postJson('/api/customer/login', [
                'email' => 'login@test.com',
                'password' => 'Pass1234',
            ]);

        $response->assertOk()
            ->assertJsonPath('customer.email', 'login@test.com');
    }

    public function test_customer_cannot_login_with_wrong_password(): void
    {
        Store::factory()->create(['slug' => self::STORE_SLUG]);
        Customer::factory()->create([
            'email' => 'wrong@test.com',
            'password' => bcrypt('Pass1234'),
        ]);

        $response = $this->withHeader('X-Store', self::STORE_SLUG)
            ->postJson('/api/customer/login', [
                'email' => 'wrong@test.com',
                'password' => 'WrongPass1',
            ]);

        $response->assertUnprocessable();
    }

    // ─── Forgot Password ───────────────────────────────────────

    public function test_customer_can_request_password_reset_link(): void
    {
        $store = Store::factory()->create(['slug' => self::STORE_SLUG]);
        $customer = Customer::factory()->create([
            'email' => 'reset@customer.com',
            'password' => bcrypt('OldPass123'),
            'store_id' => $store->id,
        ]);

        $response = $this->withHeader('X-Store', self::STORE_SLUG)
            ->postJson('/api/customer/forgot-password', [
                'email' => $customer->email,
            ]);

        $response->assertOk();
    }

    public function test_oauth_customer_without_password_cannot_request_reset(): void
    {
        $store = Store::factory()->create(['slug' => self::STORE_SLUG]);
        Customer::factory()->create([
            'email' => 'oauth@customer.com',
            'password' => null,
            'store_id' => $store->id,
        ]);

        $response = $this->withHeader('X-Store', self::STORE_SLUG)
            ->postJson('/api/customer/forgot-password', [
                'email' => 'oauth@customer.com',
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'If the account exists and uses email login, a reset link has been sent.');
    }

    public function test_nonexistent_customer_email_returns_generic_message(): void
    {
        Store::factory()->create(['slug' => self::STORE_SLUG]);

        $response = $this->withHeader('X-Store', self::STORE_SLUG)
            ->postJson('/api/customer/forgot-password', [
                'email' => 'nobody@example.com',
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'If the account exists and uses email login, a reset link has been sent.');
    }

    // ─── Reset Password ────────────────────────────────────────

    public function test_customer_can_reset_password_with_valid_token(): void
    {
        $store = Store::factory()->create(['slug' => self::STORE_SLUG]);
        $customer = Customer::factory()->create([
            'email' => 'customerreset@test.com',
            'password' => bcrypt('OldPass123'),
            'store_id' => $store->id,
        ]);

        $token = Password::broker('customers')->createToken($customer);

        $response = $this->withHeader('X-Store', self::STORE_SLUG)
            ->postJson('/api/customer/reset-password', [
                'token' => $token,
                'email' => $customer->email,
                'password' => 'NewPass456',
                'password_confirmation' => 'NewPass456',
            ]);

        $response->assertOk();

        $this->assertTrue(
            Hash::check('NewPass456', $customer->fresh()->password),
        );
    }

    public function test_customer_cannot_reset_with_invalid_token(): void
    {
        $store = Store::factory()->create(['slug' => self::STORE_SLUG]);
        $customer = Customer::factory()->create([
            'email' => 'bad-token@test.com',
            'password' => bcrypt('OldPass123'),
            'store_id' => $store->id,
        ]);

        $response = $this->withHeader('X-Store', self::STORE_SLUG)
            ->postJson('/api/customer/reset-password', [
                'token' => 'not-a-real-token',
                'email' => $customer->email,
                'password' => 'NewPass456',
                'password_confirmation' => 'NewPass456',
            ]);

        $response->assertUnprocessable();
    }

    // ─── Validation ────────────────────────────────────────────

    public function test_customer_forgot_password_requires_email(): void
    {
        Store::factory()->create(['slug' => self::STORE_SLUG]);

        $response = $this->withHeader('X-Store', self::STORE_SLUG)
            ->postJson('/api/customer/forgot-password', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_customer_reset_password_requires_all_fields(): void
    {
        Store::factory()->create(['slug' => self::STORE_SLUG]);

        $response = $this->withHeader('X-Store', self::STORE_SLUG)
            ->postJson('/api/customer/reset-password', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['token', 'email', 'password']);
    }

    public function test_customer_forgot_password_is_rate_limited(): void
    {
        Store::factory()->create(['slug' => self::STORE_SLUG]);

        for ($i = 0; $i < 10; $i++) {
            $this->withHeader('X-Store', self::STORE_SLUG)
                ->postJson('/api/customer/forgot-password', [
                    'email' => 'rate@customer.com',
                ]);
        }

        $response = $this->withHeader('X-Store', self::STORE_SLUG)
            ->postJson('/api/customer/forgot-password', [
                'email' => 'rate@customer.com',
            ]);

        $response->assertStatus(429);
    }
}
