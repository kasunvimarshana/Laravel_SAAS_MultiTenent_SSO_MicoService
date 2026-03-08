<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

/**
 * Gateway health endpoint – aggregates health of all downstream services.
 */
final class HealthController extends Controller
{
    private array $services = [
        'auth-service'         => 'AUTH_SERVICE_URL',
        'tenant-service'       => 'TENANT_SERVICE_URL',
        'inventory-service'    => 'INVENTORY_SERVICE_URL',
        'order-service'        => 'ORDER_SERVICE_URL',
        'notification-service' => 'NOTIFICATION_SERVICE_URL',
        'saga-orchestrator'    => 'SAGA_SERVICE_URL',
    ];

    public function __invoke(): JsonResponse
    {
        $checks    = [];
        $allHealthy = true;

        foreach ($this->services as $name => $envKey) {
            $url = env($envKey);
            if (!$url) {
                $checks[$name] = ['status' => false, 'message' => 'URL not configured'];
                $allHealthy = false;
                continue;
            }

            try {
                $response      = Http::timeout((int) env('GATEWAY_HEALTH_TIMEOUT', 5))->get("{$url}/health");
                $isHealthy     = $response->successful() && ($response->json('status') === 'healthy');
                $checks[$name] = [
                    'status'  => $isHealthy,
                    'message' => $response->json('status', 'unknown'),
                ];
                if (!$isHealthy) {
                    $allHealthy = false;
                }
            } catch (\Throwable $e) {
                $checks[$name] = ['status' => false, 'message' => $e->getMessage()];
                $allHealthy    = false;
            }
        }

        return response()->json([
            'service'   => 'api-gateway',
            'status'    => $allHealthy ? 'healthy' : 'degraded',
            'checks'    => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $allHealthy ? 200 : 503);
    }
}
