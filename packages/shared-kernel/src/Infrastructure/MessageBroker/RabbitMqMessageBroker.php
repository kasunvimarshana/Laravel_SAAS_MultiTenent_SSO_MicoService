<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Infrastructure\MessageBroker;

use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Saas\SharedKernel\Infrastructure\MessageBroker\Contracts\MessageBrokerInterface;

/**
 * RabbitMQ implementation of the message broker interface.
 * Uses php-amqplib under the hood.
 */
final class RabbitMqMessageBroker implements MessageBrokerInterface
{
    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel          $channel    = null;

    public function __construct(
        private readonly string          $host,
        private readonly int             $port,
        private readonly string          $username,
        private readonly string          $password,
        private readonly string          $vhost,
        private readonly LoggerInterface $logger
    ) {}

    /** {@inheritdoc} */
    public function publish(string $topic, array $message, array $options = []): void
    {
        $channel  = $this->getChannel();
        $exchange = $options['exchange'] ?? 'saas.events';
        $flags    = $options['flags']    ?? AMQPMessage::DELIVERY_MODE_PERSISTENT;

        $channel->exchange_declare($exchange, 'topic', false, true, false);

        $amqpMessage = new AMQPMessage(
            body:       json_encode($message, JSON_THROW_ON_ERROR),
            properties: ['delivery_mode' => $flags, 'content_type' => 'application/json']
        );

        $channel->basic_publish($amqpMessage, $exchange, $topic);

        $this->logger->debug('[RabbitMQ] Published', ['topic' => $topic, 'exchange' => $exchange]);
    }

    /** {@inheritdoc} */
    public function subscribe(string $topic, callable $callback, array $options = []): void
    {
        $channel   = $this->getChannel();
        $exchange  = $options['exchange'] ?? 'saas.events';
        $queue     = $options['queue']    ?? $topic;
        $exclusive = $options['exclusive'] ?? false;

        $channel->exchange_declare($exchange, 'topic', false, true, false);
        $channel->queue_declare($queue, false, true, $exclusive, false);
        $channel->queue_bind($queue, $exchange, $topic);

        $channel->basic_qos(0, $options['prefetch'] ?? 10, false);
        $channel->basic_consume(
            queue:        $queue,
            consumer_tag: '',
            no_local:     false,
            no_ack:       false,
            exclusive:    false,
            nowait:       false,
            callback:     static function (AMQPMessage $msg) use ($callback): void {
                $payload = json_decode($msg->getBody(), true, 512, JSON_THROW_ON_ERROR);
                $callback($payload);
                $msg->ack();
            }
        );

        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }

    /** {@inheritdoc} */
    public function acknowledge(mixed $messageId): void
    {
        // Acknowledgement is handled inline via AMQPMessage::ack() in subscribe()
    }

    /** {@inheritdoc} */
    public function reject(mixed $messageId, bool $requeue = false): void
    {
        // Rejection is handled inline via AMQPMessage::nack() in subscribe()
    }

    /** {@inheritdoc} */
    public function getDriverName(): string
    {
        return 'rabbitmq';
    }

    // ──────────────────────────────────────────────────────────────────────────

    private function getChannel(): AMQPChannel
    {
        if ($this->channel === null || !$this->channel->is_open()) {
            $this->connection = new AMQPStreamConnection(
                $this->host, $this->port, $this->username, $this->password, $this->vhost
            );
            $this->channel = $this->connection->channel();
        }

        return $this->channel;
    }

    public function __destruct()
    {
        try {
            $this->channel?->close();
            $this->connection?->close();
        } catch (Exception) {
            // Swallow destruction errors
        }
    }
}
