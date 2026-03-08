<?php

declare(strict_types=1);

namespace App\Application\Listeners;

use App\Application\Services\NotificationService;
use Psr\Log\LoggerInterface;

/**
 * Listens for order events from the message broker and dispatches notifications.
 * This would be called by a queue worker that consumes from RabbitMQ/Kafka.
 */
final class OrderEventListener
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly LoggerInterface     $logger
    ) {}

    /**
     * Handle an order.created event payload.
     *
     * @param array<string, mixed> $payload
     */
    public function onOrderCreated(array $payload): void
    {
        $this->logger->info('[OrderEventListener] order.created received', $payload);

        $this->notificationService->send(
            tenantId:  $payload['tenant_id'],
            type:      'order.created',
            channel:   'in_app',
            payload:   ['order_id' => $payload['order_id'], 'message' => 'Your order has been created.'],
            recipient: ['user_id' => $payload['customer_id'] ?? null],
            userId:    $payload['customer_id'] ?? null
        );
    }

    /**
     * Handle an inventory.product.low_stock event.
     *
     * @param array<string, mixed> $payload
     */
    public function onLowStock(array $payload): void
    {
        $this->logger->info('[OrderEventListener] inventory.product.low_stock received', $payload);

        $this->notificationService->send(
            tenantId:  $payload['tenant_id'],
            type:      'inventory.low_stock',
            channel:   'in_app',
            payload:   [
                'product_id'     => $payload['product_id'],
                'stock_quantity' => $payload['stock_quantity'],
                'reorder_level'  => $payload['reorder_level'],
                'message'        => 'Product stock is below reorder level.',
            ],
            recipient: []
        );
    }
}
