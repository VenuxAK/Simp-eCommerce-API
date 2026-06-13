<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the application locale based on the Accept-Language header.
 *
 * Clients send `Accept-Language: my` to receive Myanmar translations,
 * or omit the header (or send `en`) for English. Falls back to English
 * for any unsupported locale.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->getPreferredLanguage(['en', 'my']);

        app()->setLocale($locale ?? 'en');

        return $next($request);
    }
}
