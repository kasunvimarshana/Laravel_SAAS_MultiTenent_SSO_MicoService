<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Notification\Models\Notification;
use Illuminate\Support\Facades\Mail;
use Psr\Log\LoggerInterface;
use Saas\SharedKernel\Infrastructure\MessageBroker\Contracts\MessageBrokerInterface;

/**
 * Notification dispatch service.
 * Handles multi-channel notifications: email, webhook, in-app.
 */
final class NotificationService
{
    public function __construct(
        private readonly MessageBrokerInterface $messageBroker,
        private readonly LoggerInterface        $logger
    ) {}

    /**
     * Send a notification through the specified channel.
     *
     * @param  array<string, mixed> $payload
     * @param  array<string, mixed> $recipient
     */
    public function send(
        string $tenantId,
        string $type,
        string $channel,
        array  $payload,
        array  $recipient,
        ?string $userId = null
    ): Notification {
        $notification = Notification::create([
            'tenant_id' => $tenantId,
            'user_id'   => $userId,
            'channel'   => $channel,
            'type'      => $type,
            'status'    => 'pending',
            'payload'   => $payload,
            'recipient' => $recipient,
        ]);

        try {
            match ($channel) {
                'email'   => $this->sendEmail($notification),
                'webhook' => $this->sendWebhook($notification),
                'in_app'  => $this->sendInApp($notification),
                default   => throw new \InvalidArgumentException("Unsupported channel: {$channel}"),
            };

            $notification->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (\Throwable $e) {
            $notification->update(['status' => 'failed', 'error' => $e->getMessage()]);
            $this->logger->error('[Notification] Dispatch failed', [
                'type'    => $type,
                'channel' => $channel,
                'error'   => $e->getMessage(),
            ]);
        }

        return $notification->fresh();
    }

    private function sendEmail(Notification $notification): void
    {
        $to = $notification->recipient['email'] ?? null;
        if (!$to) {
            throw new \InvalidArgumentException('Email recipient not specified.');
        }

        Mail::raw(
            view: json_encode($notification->payload),
            callback: fn($message) => $message
                ->to($to)
                ->subject($notification->payload['subject'] ?? $notification->type)
        );

        $this->logger->info('[Notification] Email sent', ['to' => $to, 'type' => $notification->type]);
    }

    private function sendWebhook(Notification $notification): void
    {
        $url = $notification->recipient['url'] ?? null;
        if (!$url) {
            throw new \InvalidArgumentException('Webhook URL not specified.');
        }

        $this->messageBroker->publish("webhook.{$notification->type}", [
            'tenant_id' => $notification->tenant_id,
            'type'      => $notification->type,
            'payload'   => $notification->payload,
        ]);
    }

    private function sendInApp(Notification $notification): void
    {
        $this->messageBroker->publish("notification.in_app.{$notification->tenant_id}", [
            'notification_id' => $notification->id,
            'user_id'         => $notification->user_id,
            'type'            => $notification->type,
            'payload'         => $notification->payload,
        ]);
    }

    /**
     * List notifications for a tenant with optional pagination.
     */
    public function list(string $tenantId, array $criteria = []): mixed
    {
        $query = Notification::where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc');

        if (isset($criteria['per_page'])) {
            return $query->paginate((int) $criteria['per_page'], ['*'], 'page', (int) ($criteria['page'] ?? 1));
        }

        return $query->get();
    }
}
