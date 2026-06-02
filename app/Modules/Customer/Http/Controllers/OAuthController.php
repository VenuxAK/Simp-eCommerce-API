<?php

namespace App\Modules\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Customer\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OAuthController extends Controller
{
    use ApiResponse;

    public function redirect(string $provider): JsonResponse
    {
        $redirectUrl = \Laravel\Socialite\Facades\Socialite::driver($provider)
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return $this->respond(['redirect_url' => $redirectUrl]);
    }

    public function callback(Request $request, string $provider): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        try {
            $socialUser = \Laravel\Socialite\Facades\Socialite::driver($provider)
                ->stateless()
                ->user();
        } catch (\Exception $e) {
            Log::warning('OAuth callback failed', ['provider' => $provider, 'error' => $e->getMessage()]);
            return $this->respondError('OAuth authentication failed.', 422);
        }

        if (!$socialUser->email) {
            return $this->respondError('Email is required from OAuth provider.', 422);
        }

        $customer = Customer::where('email', $socialUser->email)->first();

        if (!$customer) {
            // Resolve store_id from X-Store header for the registration.
            $storeId = null;
            $storeSlug = $request->header('X-Store');
            if ($storeSlug) {
                $store = \App\Modules\Store\Models\Store::where('slug', $storeSlug)->first();
                if ($store) {
                    $storeId = $store->id;
                }
            }

            $customer = Customer::create([
                'name' => $socialUser->name ?? $socialUser->email,
                'email' => $socialUser->email,
                'password' => null,
                'store_id' => $storeId ?? 1,
            ]);
        }

        if ($request->hasSession()) {
            Auth::guard('customer')->login($customer);
            $request->session()->regenerate();
        }

        return $this->respond([
            'customer' => new \App\Modules\Customer\Http\Resources\CustomerResource($customer),
        ]);
    }
}
