<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces a maximum execution time for API requests.
 * Helps prevent runaway processes from consuming server resources.
 */
class RequestTimeoutMiddleware
{
    public function handle(Request $request, Closure $next, $timeout = 15): Response
    {
        // Enforce the execution time limit if the function is available and not restricted
        if (function_exists('set_time_limit') && ! in_array('set_time_limit', explode(',', ini_get('disable_functions')))) {
            set_time_limit((int) $timeout);
        }

        return $next($request);
    }
}
