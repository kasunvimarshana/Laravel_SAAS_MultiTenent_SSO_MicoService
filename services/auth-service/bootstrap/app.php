<?php

declare(strict_types=1);

use App\Http\Middleware\TenantResolverMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api:      __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health:   '/health',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [TenantResolverMiddleware::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (
            \Saas\SharedKernel\Domain\Exceptions\EntityNotFoundException $e
        ) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        });
    })
    ->create();
