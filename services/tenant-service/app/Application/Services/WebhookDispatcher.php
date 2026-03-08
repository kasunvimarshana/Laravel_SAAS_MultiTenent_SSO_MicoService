<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Tenant\Models\TenantWebhook;
use Illuminate\Support\Facades\Http;
use Psr\Log\LoggerInterface;

/**
 * Dispatches outbound webhooks to registered tenant endpoints.
 * Signs each request with HMAC-SHA256 for verification.
 */
final class WebhookDispatcher
{
    public function __construct(private readonly LoggerInterface $logger) {}

    /**
     * Dispatch an event payload to all matching webhooks for a tenant.
     *
     * @param  string               $tenantId
     * @param  string               $event     e.g. "inventory.product.created"
     * @param  array<string, mixed> $payload
     */
    public function dispatch(string $tenantId, string $event, array $payload): void
    {
        $webhooks = TenantWebhook::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        foreach ($webhooks as $webhook) {
            if (!in_array($event, $webhook->events ?? [], true)) {
                continue;
            }

            $this->send($webhook, $event, $payload);
        }
    }

    /**
     * Send a single webhook request.
     */
    private function send(TenantWebhook $webhook, string $event, array $payload): void
    {
        $body      = json_encode(['event' => $event, 'payload' => $payload, 'timestamp' => now()->toIso8601String()], JSON_THROW_ON_ERROR);
        $signature = 'sha256=' . hash_hmac('sha256', $body, $webhook->secret);

        try {
            Http::timeout(10)
                ->withHeaders([
                    'Content-Type'        => 'application/json',
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event'     => $event,
                ])
                ->post($webhook->url, json_decode($body, true));

            $this->logger->info('[Webhook] Dispatched', ['url' => $webhook->url, 'event' => $event]);
        } catch (\Throwable $e) {
            $this->logger->error('[Webhook] Failed', [
                'url'   => $webhook->url,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
