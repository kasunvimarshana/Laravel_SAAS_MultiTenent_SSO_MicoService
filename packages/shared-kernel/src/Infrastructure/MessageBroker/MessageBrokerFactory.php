<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Infrastructure\MessageBroker;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Saas\SharedKernel\Infrastructure\MessageBroker\Contracts\MessageBrokerInterface;

/**
 * Factory that resolves the correct message broker implementation
 * based on runtime configuration (supports per-tenant overrides).
 */
final class MessageBrokerFactory
{
    public function __construct(private readonly LoggerInterface $logger) {}

    /**
     * @param  array<string, mixed> $config
     */
    public function make(string $driver, array $config = []): MessageBrokerInterface
    {
        return match ($driver) {
            'rabbitmq' => new RabbitMqMessageBroker(
                host:     $config['host']     ?? 'localhost',
                port:     (int) ($config['port'] ?? 5672),
                username: $config['username'] ?? 'guest',
                password: $config['password'] ?? 'guest',
                vhost:    $config['vhost']    ?? '/',
                logger:   $this->logger
            ),
            'kafka' => new KafkaMessageBroker(
                brokers: $config['brokers'] ?? 'localhost:9092',
                groupId: $config['group_id'] ?? 'saas-consumer',
                logger:  $this->logger
            ),
            'sync'  => new SyncMessageBroker($this->logger),
            default => throw new InvalidArgumentException("Unsupported message broker driver: {$driver}"),
        };
    }
}
