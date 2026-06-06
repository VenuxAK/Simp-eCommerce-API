<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Identity\Http\Resources\UserResource;
use App\Modules\Identity\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Staff/internal authentication — uses Sanctum tokens with 24h expiry.
 *
 * Separate from the customer auth guard. Tokens are scoped with wildcard
 * abilities but could be tightened per-route for finer access control.
 * Previous tokens are invalidated on each login (single-session model).
 */
class AuthController extends Controller
{
    use ApiResponse;

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Revoke all existing tokens to enforce single-session-per-user.
        $user->tokens()->delete();
        $token = $user->createToken('pos-token', ['*'], now()->addHours(24))->plainTextToken;

        return $this->respond([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        // currentAccessToken is null when the token was already revoked or never issued.
        $token = $request->user()->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return $this->respondMessage('Logged out.');
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
