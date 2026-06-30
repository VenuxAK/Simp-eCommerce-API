<?php

namespace Tests\Feature\Api;

use App\Modules\Customer\Models\Customer;
use Tests\ApiTestCase;

class CustomerTest extends ApiTestCase
{
    public function test_can_list_customers(): void
    {
        Customer::factory(3)->create();
        $response = $this->getJson('/api/v1/customers', $this->adminHeaders);
        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_can_search_customers(): void
    {
        Customer::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com', 'phone' => '111']);
        Customer::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com', 'phone' => '222']);

        $response = $this->getJson('/api/v1/customers?search=John', $this->adminHeaders);
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_create_customer(): void
    {
        $response = $this->postJson('/api/v1/customers', [
            'name' => 'Alice', 'email' => 'alice@test.com', 'phone' => '1234567890',
        ], $this->adminHeaders);
        $response->assertCreated()->assertJsonPath('data.name', 'Alice');
    }

    public function test_can_show_customer(): void
    {
        $customer = Customer::factory()->create();
        $response = $this->getJson("/api/v1/customers/{$customer->id}", $this->adminHeaders);
        $response->assertOk()->assertJsonPath('data.id', $customer->id);
    }

    public function test_can_update_customer(): void
    {
        $customer = Customer::factory()->create();
        $response = $this->putJson("/api/v1/customers/{$customer->id}", ['name' => 'Updated'], $this->adminHeaders);
        $response->assertOk()->assertJsonPath('data.name', 'Updated');
    }

    public function test_can_delete_customer(): void
    {
        $customer = Customer::factory()->create();
        $response = $this->deleteJson("/api/v1/customers/{$customer->id}", [], $this->adminHeaders);
        $response->assertOk();
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_can_get_customer_orders(): void
    {
        $customer = Customer::factory()->create();
        $response = $this->getJson("/api/v1/customers/{$customer->id}/orders", $this->adminHeaders);
        $response->assertOk();
    }

    public function test_staff_can_create_customer(): void
    {
        $response = $this->postJson('/api/v1/customers', [
            'name' => 'Staff Customer', 'email' => 'staff@test.com',
        ], $this->staffHeaders);
        $response->assertCreated();
    }

    public function test_staff_cannot_update_customer(): void
    {
        $customer = Customer::factory()->create();
        $response = $this->putJson("/api/v1/customers/{$customer->id}", ['name' => 'Hacked'], $this->staffHeaders);
        $response->assertForbidden();
    }

    public function test_staff_cannot_delete_customer(): void
    {
        $customer = Customer::factory()->create();
        $response = $this->deleteJson("/api/v1/customers/{$customer->id}", [], $this->staffHeaders);
        $response->assertForbidden();
    }
}
