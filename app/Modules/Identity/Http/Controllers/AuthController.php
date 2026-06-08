<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Identity\Http\Requests\LoginRequest;
use App\Modules\Identity\Http\Resources\UserResource;
use App\Modules\Identity\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

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

    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {

        $user = $this->userRepository->findByEmail($request->email);

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
        $tokenStr = $request->bearerToken();

        if ($tokenStr) {
            $token = PersonalAccessToken::findToken($tokenStr);
            if ($token) {
                $token->delete();
                Cache::forget("auth:token:" . hash('sha256', $tokenStr));
            }
        }

        return $this->respondMessage('Logged out.');
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
