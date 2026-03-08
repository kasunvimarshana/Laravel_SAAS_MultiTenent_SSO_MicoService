<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Infrastructure\MessageBroker;

use Psr\Log\LoggerInterface;
use Saas\SharedKernel\Infrastructure\MessageBroker\Contracts\MessageBrokerInterface;

/**
 * Synchronous in-process message broker for testing and local development.
 * Messages are dispatched immediately via registered handlers.
 */
final class SyncMessageBroker implements MessageBrokerInterface
{
    /** @var array<string, callable[]> */
    private array $subscribers = [];

    public function __construct(private readonly LoggerInterface $logger) {}

    /** {@inheritdoc} */
    public function publish(string $topic, array $message, array $options = []): void
    {
        $this->logger->debug('[Sync] Publishing', ['topic' => $topic]);

        foreach ($this->subscribers[$topic] ?? [] as $callback) {
            $callback($message);
        }
    }

    /** {@inheritdoc} */
    public function subscribe(string $topic, callable $callback, array $options = []): void
    {
        $this->subscribers[$topic][] = $callback;
    }

    /** {@inheritdoc} */
    public function acknowledge(mixed $messageId): void {}

    /** {@inheritdoc} */
    public function reject(mixed $messageId, bool $requeue = false): void {}

    /** {@inheritdoc} */
    public function getDriverName(): string
    {
        return 'sync';
    }
}
