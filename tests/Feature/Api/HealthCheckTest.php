<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Health check endpoint tests.
 *
 * The /api/health endpoint is intentionally outside the /v1/ prefix
 * so infrastructure tooling (Docker HEALTHCHECK, load balancers) can
 * always reach it regardless of API versioning.
 */
class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_ok_status(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk()
            ->assertJsonStructure(['status', 'checks', 'timestamp'])
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database', 'ok')
            ->assertJsonPath('checks.cache', 'ok');
    }

    public function test_health_endpoint_requires_no_authentication(): void
    {
        // No auth headers — must be publicly accessible for infrastructure.
        $response = $this->getJson('/api/health');

        $response->assertOk();
    }

    public function test_health_response_includes_timestamp(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk();
        $this->assertNotEmpty($response->json('timestamp'));
    }
}
