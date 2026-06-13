<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorsCsrfTest extends TestCase
{
    use RefreshDatabase;

    public function test_sanctum_csrf_cookie_endpoint_returns_cookie(): void
    {
        $response = $this->get('/sanctum/csrf-cookie');

        $response->assertNoContent();

        $response->assertCookie('XSRF-TOKEN');
    }

    public function test_api_response_has_cors_headers(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Accept' => 'application/json',
        ])->get('/api/storefront/products');

        $response->assertHeader('Access-Control-Allow-Origin');
    }

    public function test_cors_headers_include_credentials(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Accept' => 'application/json',
        ])->get('/api/storefront/products');

        $this->assertEquals('http://localhost:5173', $response->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_options_preflight_returns_cors_headers(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Access-Control-Request-Method' => 'POST',
        ])->options('/api/auth/login');

        $response->assertStatus(204)
            ->assertHeader('Access-Control-Allow-Origin')
            ->assertHeader('Access-Control-Allow-Methods')
            ->assertHeader('Access-Control-Allow-Headers');
    }

    public function test_sanctum_stateful_domains_config_exists(): void
    {
        $stateful = config('sanctum.stateful');

        $this->assertIsArray($stateful);
        $this->assertNotEmpty($stateful);
    }

    public function test_cors_supports_credentials(): void
    {
        $this->assertTrue(config('cors.supports_credentials'));
    }

    public function test_cors_paths_include_api_and_csrf(): void
    {
        $paths = config('cors.paths');

        $this->assertContains('api/*', $paths);
        $this->assertContains('sanctum/csrf-cookie', $paths);
    }
}
