<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gateway authentication middleware.
 * Validates the Bearer token against the Auth Service and caches the result.
 * Skips validation for public endpoints (health, auth/login, auth/register).
 */
final class AuthenticateGateway
{
    private const PUBLIC_PATHS = [
        'api/v1/auth/login',
        'api/v1/auth/register',
        'health',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $path = ltrim($request->path(), '/');

        // Allow public paths without authentication
        foreach (self::PUBLIC_PATHS as $publicPath) {
            if (str_starts_with($path, $publicPath)) {
                return $next($request);
            }
        }

        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        // Cache token validation result for 60 seconds to reduce auth service load
        $cacheKey = 'gateway:token:' . hash('sha256', $token);
        $user     = Cache::remember($cacheKey, 60, function () use ($token): ?array {
            $authUrl  = config('services.auth.url', env('AUTH_SERVICE_URL'));
            $response = Http::withToken($token)
                ->withHeaders(['X-Tenant-ID' => request()->header('X-Tenant-ID', '')])
                ->get("{$authUrl}/api/v1/auth/me");

            return $response->successful() ? $response->json('data') : null;
        });

        if (!$user) {
            Cache::forget($cacheKey);
            return response()->json(['success' => false, 'message' => 'Invalid or expired token.'], 401);
        }

        // Inject user context into headers for downstream services
        $request->headers->set('X-User-ID', $user['id'] ?? '');
        $request->headers->set('X-User-Roles', implode(',', $user['roles'] ?? []));

        return $next($request);
    }
}
