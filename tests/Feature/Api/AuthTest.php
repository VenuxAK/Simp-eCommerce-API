<?php

namespace Tests\Feature\Api;

use App\Modules\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ─── Login ─────────────────────────────────────────────────

    public function test_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role']]);
    }

    public function test_cannot_login_with_invalid_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable();
    }

    public function test_cannot_login_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@test.com',
            'password' => 'password',
        ]);

        $response->assertUnprocessable();
    }

    public function test_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $response->assertOk();
        $this->assertCount(0, $user->fresh()->tokens);
    }

    public function test_can_get_authenticated_user(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJson(['data' => ['id' => $user->id, 'email' => $user->email]]);
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $response = $this->getJson('/api/v1/auth/me');
        $response->assertUnauthorized();
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'test@test.com',
                'password' => 'password',
            ]);
        }

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(429);
    }

    public function test_old_token_invalid_after_re_login(): void
    {
        $user = User::factory()->create(['password' => bcrypt('Pass1234')]);

        $login1 = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email, 'password' => 'Pass1234',
        ]);
        $oldToken = $login1->json('token');

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email, 'password' => 'Pass1234',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$oldToken}")
            ->getJson('/api/v1/auth/me');

        $response->assertUnauthorized();
    }

    // ─── Password Reset (Staff) ─────────────────────────────────

    public function test_can_request_password_reset_link(): void
    {
        $user = User::factory()->create(['password' => bcrypt('Pass1234')]);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertOk();
    }

    public function test_cannot_request_reset_for_nonexistent_email(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'nobody@example.com',
        ]);

        $response->assertUnprocessable();
    }

    public function test_forgot_password_requires_email(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create(['password' => bcrypt('OldPass123')]);

        $token = Password::broker('users')->createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPass456',
            'password_confirmation' => 'NewPass456',
        ]);

        $response->assertOk();

        $this->assertTrue(
            Hash::check('NewPass456', $user->fresh()->password),
        );
    }

    public function test_cannot_reset_with_invalid_token(): void
    {
        $user = User::factory()->create(['password' => bcrypt('OldPass123')]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'garbage-token',
            'email' => $user->email,
            'password' => 'NewPass456',
            'password_confirmation' => 'NewPass456',
        ]);

        $response->assertUnprocessable();
    }

    public function test_cannot_reset_with_weak_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('OldPass123')]);
        $token = Password::broker('users')->createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_cannot_reset_with_mismatched_confirmation(): void
    {
        $user = User::factory()->create(['password' => bcrypt('OldPass123')]);
        $token = Password::broker('users')->createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPass456',
            'password_confirmation' => 'DifferentPass456',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_forgot_password_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/auth/forgot-password', [
                'email' => 'rate@test.com',
            ]);
        }

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'rate@test.com',
        ]);

        $response->assertStatus(429);
    }
}
