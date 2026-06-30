<?php

namespace Tests\Feature\Api;

use App\Modules\Promotion\Models\Discount;
use Tests\ApiTestCase;

class DiscountTest extends ApiTestCase
{
    public function test_can_create_discount(): void
    {
        $response = $this->postJson('/api/v1/discounts', [
            'name' => 'Summer Sale', 'type' => 'percentage', 'value' => 10,
            'applies_to' => 'all', 'is_active' => true,
        ], $this->adminHeaders);

        $response->assertCreated()->assertJsonPath('data.name', 'Summer Sale');
    }

    public function test_can_list_discounts(): void
    {
        Discount::factory(3)->create();
        $response = $this->getJson('/api/v1/discounts', $this->adminHeaders);
        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_can_get_active_discounts(): void
    {
        Discount::factory()->create(['is_active' => true, 'name' => 'Active One']);
        Discount::factory()->create(['is_active' => false, 'name' => 'Inactive One']);

        $response = $this->getJson('/api/v1/discounts/active', $this->adminHeaders);

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_update_discount(): void
    {
        $discount = Discount::factory()->create(['value' => 5]);
        $this->putJson("/api/v1/discounts/{$discount->id}", ['value' => 15], $this->adminHeaders)
            ->assertOk()->assertJsonPath('data.value', 15);
    }

    public function test_can_delete_discount(): void
    {
        $discount = Discount::factory()->create();
        $this->deleteJson("/api/v1/discounts/{$discount->id}", [], $this->adminHeaders)->assertOk();
        $this->assertDatabaseMissing('discounts', ['id' => $discount->id]);
    }

    public function test_staff_cannot_create_discount(): void
    {
        $response = $this->postJson('/api/v1/discounts', [
            'name' => 'Staff Discount', 'type' => 'percentage', 'value' => 10,
            'applies_to' => 'all', 'is_active' => true,
        ], $this->staffHeaders);

        $response->assertForbidden();
    }

    public function test_staff_cannot_update_discount(): void
    {
        $discount = Discount::factory()->create();
        $response = $this->putJson("/api/v1/discounts/{$discount->id}", ['value' => 50], $this->staffHeaders);
        $response->assertForbidden();
    }

    public function test_staff_cannot_delete_discount(): void
    {
        $discount = Discount::factory()->create();
        $response = $this->deleteJson("/api/v1/discounts/{$discount->id}", [], $this->staffHeaders);
        $response->assertForbidden();
    }
}
