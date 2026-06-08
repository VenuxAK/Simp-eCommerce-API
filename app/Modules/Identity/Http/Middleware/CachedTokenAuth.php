<?php

namespace App\Modules\Identity\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates API tokens using Redis cache before hitting the database.
 *
 * Drastically reduces database queries for authenticated endpoints by
 * short-circuiting the standard Sanctum validation process. If a valid
 * token is found in the cache, the user is authenticated instantly.
 */
class CachedTokenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip validation if already authenticated (e.g., via actingAs in tests)
        if (Auth::hasUser()) {
            return $next($request);
        }

        $token = $request->bearerToken();

        if (! $token) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $cacheKey = "auth:token:" . hash('sha256', $token);

        $user = Cache::get($cacheKey);

        if ($user) {
            Auth::setUser($user);
        } else {
            $accessToken = PersonalAccessToken::findToken($token);

            if ($accessToken && (! $accessToken->expires_at || $accessToken->expires_at->isFuture())) {
                $user = $accessToken->tokenable;
                Auth::setUser($user);
                // Cache for 15 minutes to reduce DB load
                Cache::put($cacheKey, $user, now()->addMinutes(15));
            } else {
                throw new AuthenticationException('Unauthenticated.');
            }
        }

        return $next($request);
    }
}
