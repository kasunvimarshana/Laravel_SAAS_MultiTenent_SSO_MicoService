<?php

declare(strict_types=1);

use App\Http\Middleware\TenantResolverMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api:       __DIR__ . '/../routes/api.php',
        commands:  __DIR__ . '/../routes/console.php',
        health:    '/health',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            TenantResolverMiddleware::class,
        ]);

        $middleware->alias([
            'tenant'     => TenantResolverMiddleware::class,
            'auth.api'   => \Laravel\Passport\Http\Middleware\CheckForAnyScope::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (
            \Saas\SharedKernel\Domain\Exceptions\EntityNotFoundException $e,
            \Illuminate\Http\Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        });

        $exceptions->render(function (
            \Saas\SharedKernel\Domain\Exceptions\ValidationException $e,
            \Illuminate\Http\Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors'  => $e->getErrors(),
            ], 422);
        });

        $exceptions->render(function (
            \Saas\SharedKernel\Domain\Exceptions\UnauthorizedException $e,
            \Illuminate\Http\Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        });
    })
    ->create();
