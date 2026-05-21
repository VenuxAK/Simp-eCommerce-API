<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->headers = ['Authorization' => "Bearer {$user->createToken('test')->plainTextToken}"];
    }

    public function test_can_list_customers(): void
    {
        Customer::factory(3)->create();
        $response = $this->getJson('/api/customers', $this->headers);
        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_can_search_customers(): void
    {
        Customer::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com', 'phone' => '111']);
        Customer::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com', 'phone' => '222']);

        $response = $this->getJson('/api/customers?search=John', $this->headers);
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_create_customer(): void
    {
        $response = $this->postJson('/api/customers', [
            'name' => 'Alice', 'email' => 'alice@test.com', 'phone' => '1234567890',
        ], $this->headers);
        $response->assertCreated()->assertJsonPath('data.name', 'Alice');
    }

    public function test_can_show_customer(): void
    {
        $customer = Customer::factory()->create();
        $response = $this->getJson("/api/customers/{$customer->id}", $this->headers);
        $response->assertOk()->assertJsonPath('data.id', $customer->id);
    }

    public function test_can_update_customer(): void
    {
        $customer = Customer::factory()->create();
        $response = $this->putJson("/api/customers/{$customer->id}", ['name' => 'Updated'], $this->headers);
        $response->assertOk()->assertJsonPath('data.name', 'Updated');
    }

    public function test_can_delete_customer(): void
    {
        $customer = Customer::factory()->create();
        $response = $this->deleteJson("/api/customers/{$customer->id}", [], $this->headers);
        $response->assertOk();
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_can_get_customer_orders(): void
    {
        $customer = Customer::factory()->create();
        $response = $this->getJson("/api/customers/{$customer->id}/orders", $this->headers);
        $response->assertOk();
    }
}
