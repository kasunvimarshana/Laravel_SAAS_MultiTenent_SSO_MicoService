<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

/**
 * Transparent proxy controller.
 * Routes incoming requests to the appropriate downstream microservice.
 */
final class GatewayController extends Controller
{
    /**
     * The service routing map: path prefix → env var for service URL.
     *
     * @var array<string, string>
     */
    private array $serviceMap = [
        'auth'          => 'AUTH_SERVICE_URL',
        'tenants'       => 'TENANT_SERVICE_URL',
        'products'      => 'INVENTORY_SERVICE_URL',
        'categories'    => 'INVENTORY_SERVICE_URL',
        'warehouses'    => 'INVENTORY_SERVICE_URL',
        'stock-movements'=> 'INVENTORY_SERVICE_URL',
        'orders'        => 'ORDER_SERVICE_URL',
        'notifications' => 'NOTIFICATION_SERVICE_URL',
        'sagas'         => 'SAGA_SERVICE_URL',
    ];

    /**
     * Proxy the request to the appropriate service.
     * Matched by first path segment after /api/v1/.
     */
    public function proxy(Request $request, string $service, string $path = ''): Response
    {
        $envKey     = $this->serviceMap[$service] ?? null;
        $serviceUrl = $envKey ? env($envKey) : null;

        if (!$serviceUrl) {
            return response()->json(['success' => false, 'message' => "Service [{$service}] not found."], 404);
        }

        $targetUrl = rtrim($serviceUrl, '/') . '/api/v1/' . $service . ($path ? "/{$path}" : '');
        $query     = $request->getQueryString();
        if ($query) {
            $targetUrl .= '?' . $query;
        }

        $httpRequest = Http::withHeaders($this->forwardHeaders($request))
            ->timeout(30);

        $response = match (strtoupper($request->method())) {
            'GET'    => $httpRequest->get($targetUrl),
            'POST'   => $httpRequest->post($targetUrl, $request->all()),
            'PUT'    => $httpRequest->put($targetUrl, $request->all()),
            'PATCH'  => $httpRequest->patch($targetUrl, $request->all()),
            'DELETE' => $httpRequest->delete($targetUrl),
            default  => $httpRequest->get($targetUrl),
        };

        return response($response->body(), $response->status(), [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Build the headers to forward downstream.
     *
     * @return array<string, string>
     */
    private function forwardHeaders(Request $request): array
    {
        $headers = [
            'X-Tenant-ID'  => $request->header('X-Tenant-ID', ''),
            'X-User-ID'    => $request->header('X-User-ID', ''),
            'X-User-Roles' => $request->header('X-User-Roles', ''),
            'Accept'       => 'application/json',
        ];

        if ($token = $request->bearerToken()) {
            $headers['Authorization'] = "Bearer {$token}";
        }

        return $headers;
    }
}
