<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashSessionTest extends TestCase
{
    use RefreshDatabase;

    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->headers = ['Authorization' => "Bearer {$user->createToken('test')->plainTextToken}"];
    }

    public function test_can_open_session(): void
    {
        $response = $this->postJson('/api/cash-sessions/open', [
            'opening_balance' => 1000,
        ], $this->headers);

        $response->assertCreated()->assertJsonPath('data.opening_balance', 1000);
    }

    public function test_cannot_open_two_sessions(): void
    {
        $this->postJson('/api/cash-sessions/open', ['opening_balance' => 1000], $this->headers);
        $response = $this->postJson('/api/cash-sessions/open', ['opening_balance' => 500], $this->headers);
        $response->assertUnprocessable();
    }

    public function test_can_close_session(): void
    {
        $this->postJson('/api/cash-sessions/open', ['opening_balance' => 1000], $this->headers);

        $response = $this->postJson('/api/cash-sessions/close', ['closing_balance' => 1000], $this->headers);
        $response->assertOk();
    }

    public function test_active_returns_null_when_no_session(): void
    {
        $response = $this->getJson('/api/cash-sessions/active', $this->headers);
        $response->assertOk();
        $this->assertNull($response->json('data'));
    }

    public function test_can_list_sessions(): void
    {
        $this->postJson('/api/cash-sessions/open', ['opening_balance' => 500], $this->headers);
        $this->getJson('/api/cash-sessions', $this->headers)->assertOk()->assertJsonCount(1, 'data');
    }
}
