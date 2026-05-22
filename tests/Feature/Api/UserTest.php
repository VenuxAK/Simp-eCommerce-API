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
            'name' => 'New User', 'email' => 'new@test.com', 'password' => 'Pass1234', 'role' => 'staff',
        ], $this->adminHeaders)->assertCreated();
    }

    public function test_staff_cannot_create_user(): void
    {
        $this->postJson('/api/users', [
            'name' => 'Hacker', 'email' => 'hacker@test.com', 'password' => 'Pass1234', 'role' => 'admin',
        ], $this->staffHeaders)->assertForbidden();
    }

    public function test_admin_can_update_user(): void
    {
        $user = User::factory()->create();
        $this->putJson("/api/users/{$user->id}", ['name' => 'Updated'], $this->adminHeaders)->assertOk();
    }

    public function test_staff_cannot_update_user(): void
    {
        $user = User::factory()->create();
        $this->putJson("/api/users/{$user->id}", ['name' => 'Hacked'], $this->staffHeaders)->assertForbidden();
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->deleteJson("/api/users/{$admin->id}", [], $this->adminHeaders)->assertUnprocessable();
    }

    public function test_admin_cannot_delete_another_admin(): void
    {
        $admin2 = User::factory()->create(['role' => 'admin']);
        $response = $this->deleteJson("/api/users/{$admin2->id}", [], $this->adminHeaders);
        $response->assertUnprocessable();
        $this->assertDatabaseHas('users', ['id' => $admin2->id]);
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

    public function test_staff_cannot_delete_user(): void
    {
        $user = User::factory()->create();
        $this->deleteJson("/api/users/{$user->id}", [], $this->staffHeaders)->assertForbidden();
    }

    public function test_create_user_rejects_password_without_uppercase(): void
    {
        $this->postJson('/api/users', [
            'name' => 'Test', 'email' => 'test@test.com', 'password' => 'alllowercase1', 'role' => 'staff',
        ], $this->adminHeaders)->assertUnprocessable();
    }

    public function test_create_user_rejects_password_without_lowercase(): void
    {
        $this->postJson('/api/users', [
            'name' => 'Test', 'email' => 'test2@test.com', 'password' => 'ALLUPPERCASE1', 'role' => 'staff',
        ], $this->adminHeaders)->assertUnprocessable();
    }

    public function test_create_user_rejects_password_without_digit(): void
    {
        $this->postJson('/api/users', [
            'name' => 'Test', 'email' => 'test3@test.com', 'password' => 'NoDigitsHere', 'role' => 'staff',
        ], $this->adminHeaders)->assertUnprocessable();
    }

    public function test_create_user_rejects_short_password(): void
    {
        $this->postJson('/api/users', [
            'name' => 'Test', 'email' => 'test4@test.com', 'password' => 'Ab1', 'role' => 'staff',
        ], $this->adminHeaders)->assertUnprocessable();
    }
}
