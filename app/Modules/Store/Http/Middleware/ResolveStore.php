<?php

namespace App\Modules\Store\Http\Middleware;

use App\Modules\Store\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for ResolveStore.
 */
class ResolveStore
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->header('X-Store', 'main');

        $store = Store::where('slug', $slug)->where('is_active', true)->first();

        if (! $store) {
            return response()->json(['message' => 'Store not found or inactive.'], 404);
        }

        app()->instance('current_store', $store);

        return $next($request);
    }
}
