<?php

namespace Tests\Feature\Api;

use App\Modules\Customer\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class OAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirect_returns_google_url(): void
    {
        $mockRedirect = Mockery::mock(Provider::class);
        $mockRedirect->shouldReceive('stateless->with->redirect->getTargetUrl')
            ->andReturn('https://accounts.google.com/o/oauth2/auth?client_id=xxx');

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn($mockRedirect);

        $response = $this->getJson('/api/auth/oauth/google/redirect');

        $response->assertOk()
            ->assertJsonStructure(['redirect_url']);
    }

    public function test_callback_creates_new_customer(): void
    {
        $socialiteUser = new SocialiteUser;
        $socialiteUser->id = '12345';
        $socialiteUser->name = 'Google User';
        $socialiteUser->email = 'googleuser@example.com';
        $socialiteUser->token = 'mock-token';
        $socialiteUser->refreshToken = 'mock-refresh';

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('stateless->user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->withSession([])->getJson('/api/auth/oauth/google/callback?code=valid_code');

        $response->assertRedirect();
        $redirectUrl = $response->headers->get('Location');
        $this->assertStringContainsString('/auth/callback', $redirectUrl);

        $this->assertDatabaseHas('customers', [
            'email' => 'googleuser@example.com',
            'name' => 'Google User',
            'password' => null,
        ]);
    }

    public function test_callback_logs_existing_customer(): void
    {
        Customer::factory()->create([
            'email' => 'existing@example.com',
            'name' => 'Existing',
        ]);

        $socialiteUser = new SocialiteUser;
        $socialiteUser->id = '12345';
        $socialiteUser->name = 'Existing';
        $socialiteUser->email = 'existing@example.com';
        $socialiteUser->token = 'mock-token';

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('stateless->user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->withSession([])->getJson('/api/auth/oauth/google/callback?code=valid_code');

        $response->assertRedirect();
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_callback_fails_without_code(): void
    {
        $response = $this->withSession([])->getJson('/api/auth/oauth/google/callback');
        $response->assertUnprocessable();
    }
}
