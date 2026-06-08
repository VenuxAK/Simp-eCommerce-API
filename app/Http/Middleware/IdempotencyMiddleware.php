<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    /**
     * Handle an incoming request and ensure idempotency based on the Idempotency-Key header.
     *
     * Caches successful responses for 24 hours. Concurrent requests with the same key
     * will receive a 409 Conflict.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethodSafe() && $request->hasHeader('Idempotency-Key')) {
            $key = 'idempotency:' . $request->header('Idempotency-Key');

            if (Cache::has($key)) {
                $cached = Cache::get($key);
                return response($cached['content'], $cached['status'], $cached['headers']);
            }

            $lock = Cache::lock($key . ':lock', 15);
            
            if (! $lock->get()) {
                return response()->json(['message' => 'Concurrent request in progress for this Idempotency-Key.'], 409);
            }

            try {
                /** @var Response $response */
                $response = $next($request);

                if ($response->isSuccessful()) {
                    Cache::put($key, [
                        'content' => $response->getContent(),
                        'status' => $response->getStatusCode(),
                        'headers' => $response->headers->all(),
                    ], now()->addHours(24));
                }

                return $response;
            } finally {
                $lock->release();
            }
        }

        return $next($request);
    }
}
