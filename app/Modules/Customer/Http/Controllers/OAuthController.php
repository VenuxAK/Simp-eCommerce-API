<?php

namespace App\Modules\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Customer\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            $customer = Customer::create([
                'name' => $socialUser->name ?? $socialUser->email,
                'email' => $socialUser->email,
                'password' => null,
            ]);
        }

        $customer->tokens()->delete();
        $token = $customer->createToken('storefront-token', ['customer:*'], now()->addDays(7))->plainTextToken;

        return $this->respond([
            'token' => $token,
            'customer' => new \App\Modules\Customer\Http\Resources\CustomerResource($customer),
        ]);
    }
}
