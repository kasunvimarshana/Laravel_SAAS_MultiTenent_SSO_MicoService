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
        $tenantId = $request->header('X-Tenant-ID');

        if (!$tenantId) {
            $host  = $request->getHost();
            $parts = explode('.', $host);
            if (count($parts) >= 3) {
                $tenantId = $parts[0];
            }
        }

        if ($tenantId) {
            $this->tenantContext->setTenant((string) $tenantId);
        }

        return $next($request);
    }
}
