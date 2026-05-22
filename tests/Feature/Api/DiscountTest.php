<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscountTest extends TestCase
{
    use RefreshDatabase;

    private array $adminHeaders;
    private array $staffHeaders;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);
        $this->adminHeaders = ['Authorization' => "Bearer {$admin->createToken('test')->plainTextToken}"];
        $this->staffHeaders = ['Authorization' => "Bearer {$staff->createToken('test')->plainTextToken}"];
    }

    public function test_can_create_discount(): void
    {
        $response = $this->postJson('/api/discounts', [
            'name' => 'Summer Sale', 'type' => 'percentage', 'value' => 10,
            'applies_to' => 'all', 'is_active' => true,
        ], $this->adminHeaders);

        $response->assertCreated()->assertJsonPath('data.name', 'Summer Sale');
    }

    public function test_can_list_discounts(): void
    {
        Discount::factory(3)->create();
        $response = $this->getJson('/api/discounts', $this->adminHeaders);
        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_can_get_active_discounts(): void
    {
        Discount::factory()->create(['is_active' => true, 'name' => 'Active One']);
        Discount::factory()->create(['is_active' => false, 'name' => 'Inactive One']);

        $response = $this->getJson('/api/discounts/active', $this->adminHeaders);

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_update_discount(): void
    {
        $discount = Discount::factory()->create(['value' => 5]);
        $this->putJson("/api/discounts/{$discount->id}", ['value' => 15], $this->adminHeaders)
            ->assertOk()->assertJsonPath('data.value', 15);
    }

    public function test_can_delete_discount(): void
    {
        $discount = Discount::factory()->create();
        $this->deleteJson("/api/discounts/{$discount->id}", [], $this->adminHeaders)->assertOk();
        $this->assertDatabaseMissing('discounts', ['id' => $discount->id]);
    }

    public function test_staff_cannot_create_discount(): void
    {
        $response = $this->postJson('/api/discounts', [
            'name' => 'Staff Discount', 'type' => 'percentage', 'value' => 10,
            'applies_to' => 'all', 'is_active' => true,
        ], $this->staffHeaders);

        $response->assertForbidden();
    }

    public function test_staff_cannot_update_discount(): void
    {
        $discount = Discount::factory()->create();
        $response = $this->putJson("/api/discounts/{$discount->id}", ['value' => 50], $this->staffHeaders);
        $response->assertForbidden();
    }

    public function test_staff_cannot_delete_discount(): void
    {
        $discount = Discount::factory()->create();
        $response = $this->deleteJson("/api/discounts/{$discount->id}", [], $this->staffHeaders);
        $response->assertForbidden();
    }
}
