<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Factories\CategoryFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscountTest extends TestCase
{
    use RefreshDatabase;

    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->headers = ['Authorization' => "Bearer {$user->createToken('test')->plainTextToken}"];
    }

    public function test_can_create_discount(): void
    {
        $response = $this->postJson('/api/discounts', [
            'name' => 'Summer Sale', 'type' => 'percentage', 'value' => 10,
            'applies_to' => 'all', 'is_active' => true,
        ], $this->headers);

        $response->assertCreated()->assertJsonPath('data.name', 'Summer Sale');
    }

    public function test_can_list_discounts(): void
    {
        Discount::factory(3)->create();
        $response = $this->getJson('/api/discounts', $this->headers);
        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_can_get_active_discounts(): void
    {
        Discount::factory()->create(['is_active' => true, 'name' => 'Active One']);
        Discount::factory()->create(['is_active' => false, 'name' => 'Inactive One']);

        $response = $this->getJson('/api/discounts/active', $this->headers);

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_update_discount(): void
    {
        $discount = Discount::factory()->create(['value' => 5]);
        $this->putJson("/api/discounts/{$discount->id}", ['value' => 15], $this->headers)
            ->assertOk()->assertJsonPath('data.value', 15);
    }

    public function test_can_delete_discount(): void
    {
        $discount = Discount::factory()->create();
        $this->deleteJson("/api/discounts/{$discount->id}", [], $this->headers)->assertOk();
        $this->assertDatabaseMissing('discounts', ['id' => $discount->id]);
    }
}
