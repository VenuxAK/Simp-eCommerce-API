<?php

namespace App\Modules\Identity\Http\Middleware;

use App\Modules\Core\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate routes by user role — accepts one or more role strings.
 *
 * Handles both enum instances and raw string values because
 * middleware arguments are always strings, while the User model
 * casts 'role' to the UserRole enum.
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // role may be a UserRole enum instance or a raw string depending on context.
        if (! $user || ! in_array($user->role instanceof UserRole ? $user->role->value : $user->role, $roles)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return $next($request);
    }
}
