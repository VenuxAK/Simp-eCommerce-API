<?php

namespace Tests\Feature\Api;

use App\Modules\Core\Enums\StockMovementReason;
use Tests\ApiTestCase;

class StockMovementTest extends ApiTestCase
{
    private int $variantId;

    protected function setUp(): void
    {
        parent::setUp();
        $variant = $this->createVariant(10, 50);
        $this->variantId = $variant->id;
    }

    public function test_stock_movement_created_on_order(): void
    {
        $this->postJson('/api/v1/orders', [
            'items' => [['product_variant_id' => $this->variantId, 'quantity' => 2]],
            'payment' => ['method' => 'cash', 'amount' => 100],
        ], $this->adminHeaders)->assertCreated();

        $response = $this->getJson('/api/v1/stock-movements', $this->adminHeaders);
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(-2, $response->json('data.0.quantity_change'));
        $this->assertEquals('sale', $response->json('data.0.reason'));
    }

    public function test_stock_movement_created_on_adjustment(): void
    {
        $this->patchJson("/api/v1/variants/{$this->variantId}/stock", ['quantity' => 20], $this->adminHeaders);

        $response = $this->getJson('/api/v1/stock-movements', $this->adminHeaders);
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(10, $response->json('data.0.quantity_change'));
        $this->assertEquals('adjustment', $response->json('data.0.reason'));
    }

    public function test_stock_movement_created_on_cancel(): void
    {
        $createRes = $this->postJson('/api/v1/orders', [
            'items' => [['product_variant_id' => $this->variantId, 'quantity' => 2]],
            'payment' => ['method' => 'cash', 'amount' => 100],
        ], $this->adminHeaders);
        $orderId = $createRes->json('data.id');

        $this->patchJson("/api/v1/orders/{$orderId}/status", ['status' => 'cancelled'], $this->adminHeaders);

        $response = $this->getJson('/api/v1/stock-movements', $this->adminHeaders);
        $this->assertEquals(2, $response->json('data.1.quantity_change'));
        $this->assertEquals(StockMovementReason::Return->value, $response->json('data.1.reason'));
    }

    public function test_staff_cannot_list_stock_movements(): void
    {
        $response = $this->getJson('/api/v1/stock-movements', $this->staffHeaders);
        $response->assertForbidden();
    }
}
