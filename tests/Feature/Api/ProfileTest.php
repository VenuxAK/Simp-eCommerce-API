<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private array $headers;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['name' => 'Original']);
        $this->headers = ['Authorization' => "Bearer {$this->user->createToken('test')->plainTextToken}"];
    }

    public function test_can_get_profile(): void
    {
        $response = $this->getJson('/api/profile', $this->headers);
        $response->assertOk();
        $response->assertJson(['data' => ['id' => $this->user->id]]);
    }

    public function test_can_update_profile_name(): void
    {
        $response = $this->putJson('/api/profile', [
            'name' => 'New Name', 'email' => $this->user->email,
        ], $this->headers);
        $response->assertOk();
        $response->assertJson(['data' => ['name' => 'New Name']]);

        $this->assertEquals('New Name', $this->user->fresh()->name);
    }

    public function test_can_update_profile_password(): void
    {
        $this->putJson('/api/profile', [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'password' => 'new-password',
        ], $this->headers)->assertOk();

        $this->assertTrue(Hash::check('new-password', $this->user->fresh()->password));
    }

    public function test_profile_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'other@test.com']);

        $this->putJson('/api/profile', [
            'name' => 'Test', 'email' => 'other@test.com',
        ], $this->headers)->assertUnprocessable();
    }
}
