<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Per-tenant/IP rate limiting middleware.
 */
final class RateLimitMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->header('X-Tenant-ID', $request->ip());
        $key      = "gateway:rate:{$tenantId}";
        $limit    = (int) env('RATE_LIMIT_PER_MINUTE', 120);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $retryAfter = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Rate limit exceeded. Retry after {$retryAfter} seconds.",
            ], 429, ['Retry-After' => $retryAfter]);
        }

        RateLimiter::hit($key, 60);

        $response = $next($request);

        // Inject rate limit headers
        $response->headers->set('X-RateLimit-Limit', (string) $limit);
        $response->headers->set('X-RateLimit-Remaining', (string) RateLimiter::remaining($key, $limit));

        return $response;
    }
}
