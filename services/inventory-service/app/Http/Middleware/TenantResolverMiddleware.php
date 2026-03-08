<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Saas\SharedKernel\Infrastructure\Tenant\TenantContext;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Resolves the tenant from the incoming request and hydrates TenantContext.
 *
 * Resolution order:
 *  1. X-Tenant-ID header
 *  2. Subdomain (e.g. tenant-slug.api.example.com)
 *  3. Bearer token claims (JWT / Passport introspection)
 */
final class TenantResolverMiddleware
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        // 1. Explicit header (preferred for service-to-service calls)
        $tenantId = $request->header('X-Tenant-ID');

        // 2. Fallback: subdomain
        if (!$tenantId) {
            $host = $request->getHost();
            $parts = explode('.', $host);
            // Expect format: <slug>.<domain>.<tld>
            if (count($parts) >= 3) {
                $tenantId = $parts[0]; // treat first subdomain as tenant slug (resolved upstream)
            }
        }

        // 3. Fallback: Passport token claim
        if (!$tenantId && $user = $request->user()) {
            $tenantId = $user->tenant_id ?? null;
        }

        if ($tenantId) {
            $this->tenantContext->setTenant((string) $tenantId);
        }

        return $next($request);
    }
}
