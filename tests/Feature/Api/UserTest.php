<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
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

    public function test_admin_can_list_users(): void
    {
        $this->getJson('/api/users', $this->adminHeaders)->assertOk();
    }

    public function test_staff_cannot_list_users(): void
    {
        $this->getJson('/api/users', $this->staffHeaders)->assertForbidden();
    }

    public function test_admin_can_create_user(): void
    {
        $this->postJson('/api/users', [
            'name' => 'New User', 'email' => 'new@test.com', 'password' => 'password', 'role' => 'staff',
        ], $this->adminHeaders)->assertCreated();
    }

    public function test_admin_can_update_user(): void
    {
        $user = User::factory()->create();
        $this->putJson("/api/users/{$user->id}", ['name' => 'Updated'], $this->adminHeaders)->assertOk();
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->deleteJson("/api/users/{$admin->id}", [], $this->adminHeaders)->assertUnprocessable();
    }

    public function test_admin_cannot_delete_user_with_orders(): void
    {
        $staff = User::where('role', 'staff')->first();
        Order::factory()->create(['user_id' => $staff->id]);

        $this->deleteJson("/api/users/{$staff->id}", [], $this->adminHeaders)->assertUnprocessable();
        $this->assertDatabaseHas('users', ['id' => $staff->id]);
    }

    public function test_admin_can_delete_user_without_orders(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $this->deleteJson("/api/users/{$staff->id}", [], $this->adminHeaders)->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $staff->id]);
    }
}
