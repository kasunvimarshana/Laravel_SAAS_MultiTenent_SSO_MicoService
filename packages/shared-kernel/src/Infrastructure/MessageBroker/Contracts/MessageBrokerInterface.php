<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Infrastructure\MessageBroker\Contracts;

/**
 * Pluggable message broker interface.
 * Implementations: KafkaMessageBroker, RabbitMqMessageBroker, SyncMessageBroker.
 */
interface MessageBrokerInterface
{
    /**
     * Publish a message to a topic/exchange.
     *
     * @param  string               $topic   Topic or routing key
     * @param  array<string, mixed> $message Serialisable message payload
     * @param  array<string, mixed> $options Driver-specific options (headers, partition, etc.)
     */
    public function publish(string $topic, array $message, array $options = []): void;

    /**
     * Subscribe to a topic/queue and invoke the callback for each message.
     *
     * @param  string                              $topic
     * @param  callable(array<string,mixed>): void $callback
     * @param  array<string, mixed>                $options
     */
    public function subscribe(string $topic, callable $callback, array $options = []): void;

    /**
     * Acknowledge a message (required by some brokers like RabbitMQ).
     *
     * @param  mixed $messageId Broker-specific message identifier
     */
    public function acknowledge(mixed $messageId): void;

    /**
     * Reject / nack a message.
     *
     * @param  mixed $messageId
     * @param  bool  $requeue   Whether the broker should re-queue the message
     */
    public function reject(mixed $messageId, bool $requeue = false): void;

    /**
     * Return the name of the broker implementation (for logging/observability).
     */
    public function getDriverName(): string;
}
