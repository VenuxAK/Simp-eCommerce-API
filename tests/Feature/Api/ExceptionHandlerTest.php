<?php

namespace Tests\Feature\Api;

use App\Modules\Identity\Models\User;
use Tests\ApiTestCase;

/**
 * Tests for the global structured JSON exception handler.
 *
 * Verifies that all API error responses conform to the consistent envelope:
 *   - 404: { "message": "..." }
 *   - 405: { "message": "..." }
 *   - 401: { "message": "..." }
 *   - 422: { "message": "...", "errors": { ... } }
 */
class ExceptionHandlerTest extends ApiTestCase
{
    public function test_unknown_route_returns_404_json(): void
    {
        $response = $this->getJson('/api/v1/this-route-does-not-exist');

        $response->assertNotFound()
            ->assertJsonStructure(['message'])
            ->assertJsonPath('message', 'The requested URL does not exist.');
    }

    public function test_missing_model_returns_404_json(): void
    {
        $response = $this->getJson('/api/v1/orders/999999', $this->adminHeaders);

        $response->assertNotFound()
            ->assertJsonStructure(['message'])
            ->assertJsonPath('message', 'Resource not found.');
    }

    public function test_unauthenticated_request_returns_401_json(): void
    {
        $response = $this->getJson('/api/v1/orders');

        $response->assertUnauthorized()
            ->assertJsonStructure(['message'])
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_method_not_allowed_returns_405_json(): void
    {
        // POST to a GET-only route.
        $response = $this->postJson('/api/v1/auth/me', [], $this->adminHeaders);

        $response->assertStatus(405)
            ->assertJsonStructure(['message'])
            ->assertJsonPath('message', 'Method not allowed.');
    }

    public function test_validation_failure_returns_422_with_errors_envelope(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors']);
    }

    public function test_forbidden_action_returns_403_json(): void
    {
        // Staff cannot update order status — permission:orders.update-status
        $staffUser = User::factory()->salesStaff()->create(['store_id' => $this->staffUser->store_id]);
        $staffHeaders = ['Authorization' => "Bearer {$staffUser->createToken('test')->plainTextToken}"];

        // Create an order first
        $variant = $this->createVariant(10, 50);
        $order = $this->postJson('/api/v1/orders', [
            'items' => [['product_variant_id' => $variant->id, 'quantity' => 1]],
            'payment' => ['method' => 'cash', 'amount' => 50],
        ], $this->adminHeaders)->assertCreated();

        $response = $this->patchJson(
            '/api/v1/orders/'.$order->json('data.id').'/status',
            ['status' => 'cancelled'],
            $staffHeaders,
        );

        $response->assertForbidden()
            ->assertJsonStructure(['message']);
    }
}
