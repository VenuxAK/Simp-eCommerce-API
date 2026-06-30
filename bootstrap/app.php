<?php

use App\Http\Middleware\IdempotencyMiddleware;
use App\Http\Middleware\RequestTimeoutMiddleware;
use App\Http\Middleware\SetLocale;
use App\Modules\Identity\Http\Middleware\CachedTokenAuth;
use App\Modules\Store\Http\Middleware\ResolveStore;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
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
        // Render all API exceptions as JSON.
        $exceptions->shouldRenderJsonWhen(function (Request $request): bool {
            return $request->is('api/*') || $request->expectsJson();
        });

        // 404 — model or route not found.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                $message = $e->getPrevious() instanceof ModelNotFoundException
                    ? 'Resource not found.'
                    : 'The requested URL does not exist.';

                return response()->json(['message' => $message], 404);
            }
        });

        // 405 — method not allowed.
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Method not allowed.'], 405);
            }
        });

        // 401 — unauthenticated.
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
        });

        // 403 — authorization failed (Laravel gates and policies).
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'This action is unauthorized.'], 403);
            }
        });

        // 422 — validation errors: always wrap in { message, errors }.
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        // HttpException — covers Spatie UnauthorizedException (403), and any other
        // Symfony HTTP exceptions not already caught above (e.g. 429, 503).
        // Must be registered BEFORE the Throwable catch-all.
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($request->is('api/*')) {
                $status = $e->getStatusCode();
                $message = $e->getMessage() ?: match ($status) {
                    403 => 'This action is unauthorized.',
                    429 => 'Too many requests.',
                    503 => 'Service unavailable.',
                    default => 'HTTP error.',
                };

                return response()->json(['message' => $message], $status);
            }
        });

        // 500 — unhandled exceptions: log and return safe generic message.
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                report($e);

                return response()->json(['message' => 'An unexpected error occurred. Please try again later.'], 500);
            }
        });
    })->create();
