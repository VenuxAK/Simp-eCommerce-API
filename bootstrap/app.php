<?php

use App\Modules\Identity\Http\Middleware\CachedTokenAuth;
use App\Modules\Store\Http\Middleware\ResolveStore;
use App\Http\Middleware\IdempotencyMiddleware;
use App\Http\Middleware\RequestTimeoutMiddleware;
use App\Http\Middleware\SetLocale;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withProviders([
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(null);
        $middleware->alias([
            'permission' => PermissionMiddleware::class,
            'store' => ResolveStore::class,
            'stateful' => EnsureFrontendRequestsAreStateful::class,
            'cached.auth' => CachedTokenAuth::class,
            'idempotent' => IdempotencyMiddleware::class,
            'timeout' => RequestTimeoutMiddleware::class,
            'locale' => SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request): bool {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Resource not found.'], 404);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
        });
    })->create();
