<?php

namespace App\Modules\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Core\Traits\StoreScope;
use App\Modules\Customer\Http\Requests\OAuthCallbackRequest;
use App\Modules\Customer\Http\Resources\CustomerResource;
use App\Modules\Customer\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

/**
 * Social login via Laravel Socialite — stateless OAuth flow.
 *
 * Supports configurable providers (Google, Facebook, etc.).
 * A new customer is auto-registered on first login; subsequent
 * logins link to the existing customer record by email.
 * stateless() is required since SPAs and mobile clients don't
 * maintain a persistent session during the OAuth redirect chain.
 */
class OAuthController extends Controller
{
    use ApiResponse, StoreScope;

    public function redirect(string $provider): JsonResponse
    {
        $redirectUrl = Socialite::driver($provider)
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return $this->respond(['redirect_url' => $redirectUrl]);
    }

    public function callback(OAuthCallbackRequest $request, string $provider): JsonResponse
    {
        try {
            $socialUser = Socialite::driver($provider)
                ->stateless()
                ->user();
        } catch (\Exception $e) {
            Log::warning('OAuth callback failed', ['provider' => $provider, 'error' => $e->getMessage()]);

            return $this->respondError('OAuth authentication failed.', 422);
        }

        if (! $socialUser->email) {
            return $this->respondError('Email is required from OAuth provider.', 422);
        }

        $customer = Customer::where('email', $socialUser->email)->first();

        if (! $customer) {
            $customer = Customer::create([
                'name' => $socialUser->name ?? $socialUser->email,
                'email' => $socialUser->email,
                'password' => null,
                'store_id' => $this->resolveStoreId() ?? 1,
            ]);
        }

        if ($request->hasSession()) {
            Auth::guard('customer')->login($customer);
            $request->session()->regenerate();
        }

        return $this->respond([
            'customer' => new CustomerResource($customer),
        ]);
    }
}
