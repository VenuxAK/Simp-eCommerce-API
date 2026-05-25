<?php

namespace Tests\Feature\Api;

use App\Modules\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);

        $response = $this->withSession([])->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'role']]);
    }

    public function test_cannot_login_with_invalid_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);

        $response = $this->withSession([])->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable();
    }

    public function test_cannot_login_with_nonexistent_email(): void
    {
        $response = $this->withSession([])->postJson('/api/auth/login', [
            'email' => 'nobody@test.com',
            'password' => 'password',
        ]);

        $response->assertUnprocessable();
    }

    public function test_requires_email_and_password(): void
    {
        $response = $this->withSession([])->postJson('/api/auth/login', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout');

        $response->assertOk();
    }

    public function test_can_get_authenticated_user(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me');

        $response->assertOk()
            ->assertJson(['data' => ['id' => $user->id, 'email' => $user->email]]);
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $response = $this->getJson('/api/auth/me');
        $response->assertUnauthorized();
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->withSession([])->postJson('/api/auth/login', [
                'email' => 'test@test.com',
                'password' => 'password',
            ]);
        }

        $response = $this->withSession([])->postJson('/api/auth/login', [
            'email' => 'test@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(429);
    }

    public function test_login_returns_user_data(): void
    {
        $user = User::factory()->create(['password' => bcrypt('Pass1234')]);

        $response = $this->withSession([])->postJson('/api/auth/login', [
            'email' => $user->email, 'password' => 'Pass1234',
        ]);

        $response->assertOk();
        $this->assertArrayHasKey('user', $response->json());
        $this->assertEquals($user->email, $response->json('user.email'));
    }
}
