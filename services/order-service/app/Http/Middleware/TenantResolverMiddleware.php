<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Saas\SharedKernel\Infrastructure\Tenant\TenantContext;
use Symfony\Component\HttpFoundation\Response;

final class TenantResolverMiddleware
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($tenantId = $request->header('X-Tenant-ID')) {
            $this->tenantContext->setTenant((string) $tenantId);
        }
        return $next($request);
    }
}
