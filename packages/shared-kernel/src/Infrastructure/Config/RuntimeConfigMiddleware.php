<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Infrastructure\Config;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware that applies tenant-specific runtime configurations
 * to the Laravel config repository on every request.
 *
 * Must run AFTER TenantResolverMiddleware.
 */
final class RuntimeConfigMiddleware
{
    public function __construct(private readonly RuntimeConfigManager $configManager) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->configManager->applyTenantConfig();
        return $next($request);
    }
}
