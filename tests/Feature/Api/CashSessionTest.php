<?php

namespace Tests\Feature\Api;

use App\Modules\Identity\Models\User;
use App\Modules\Store\Models\Store;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashSessionTest extends TestCase
{
    use RefreshDatabase;

    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $store = Store::firstOrCreate(
            ['slug' => 'main'],
            ['name' => 'Test Store', 'is_active' => true],
        );

        $user = User::factory()->create(['store_id' => $store->id]);
        $user->assignRole('sales_staff');
        $this->headers = ['Authorization' => "Bearer {$user->createToken('test')->plainTextToken}", 'X-Store' => 'main'];
    }

    public function test_can_open_session(): void
    {
        $response = $this->postJson('/api/v1/cash-sessions/open', [
            'opening_balance' => 1000,
        ], $this->headers);

        $response->assertCreated()->assertJsonPath('data.opening_balance', 1000);
    }

    public function test_cannot_open_two_sessions(): void
    {
        $this->postJson('/api/v1/cash-sessions/open', ['opening_balance' => 1000], $this->headers);
        $response = $this->postJson('/api/v1/cash-sessions/open', ['opening_balance' => 500], $this->headers);
        $response->assertUnprocessable();
    }

    public function test_can_close_session(): void
    {
        $this->postJson('/api/v1/cash-sessions/open', ['opening_balance' => 1000], $this->headers);

        $response = $this->postJson('/api/v1/cash-sessions/close', ['closing_balance' => 1000], $this->headers);
        $response->assertOk();
    }

    public function test_active_returns_null_when_no_session(): void
    {
        $response = $this->getJson('/api/v1/cash-sessions/active', $this->headers);
        $response->assertOk();
        $this->assertNull($response->json('data'));
    }

    public function test_can_list_sessions(): void
    {
        $this->postJson('/api/v1/cash-sessions/open', ['opening_balance' => 500], $this->headers);
        $this->getJson('/api/v1/cash-sessions', $this->headers)->assertOk()->assertJsonCount(1, 'data');
    }
}
