<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Infrastructure\MessageBroker;

use Exception;
use Psr\Log\LoggerInterface;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\Producer;
use Saas\SharedKernel\Infrastructure\MessageBroker\Contracts\MessageBrokerInterface;

/**
 * Apache Kafka implementation of the message broker interface.
 * Requires the php-rdkafka extension.
 */
final class KafkaMessageBroker implements MessageBrokerInterface
{
    private ?Producer $producer = null;

    public function __construct(
        private readonly string          $brokers,
        private readonly string          $groupId,
        private readonly LoggerInterface $logger
    ) {}

    /** {@inheritdoc} */
    public function publish(string $topic, array $message, array $options = []): void
    {
        $producer      = $this->getProducer();
        $kafkaTopic    = $producer->newTopic($topic);
        $partition     = $options['partition'] ?? RD_KAFKA_PARTITION_UA;
        $msgFlags      = $options['msgflags']  ?? 0;
        $key           = $options['key']       ?? null;

        $kafkaTopic->produce(
            $partition,
            $msgFlags,
            json_encode($message, JSON_THROW_ON_ERROR),
            $key
        );

        $producer->flush(10_000);

        $this->logger->debug('[Kafka] Published', ['topic' => $topic]);
    }

    /** {@inheritdoc} */
    public function subscribe(string $topic, callable $callback, array $options = []): void
    {
        $conf = new Conf();
        $conf->set('group.id',             $options['group_id']           ?? $this->groupId);
        $conf->set('metadata.broker.list', $options['brokers']            ?? $this->brokers);
        $conf->set('auto.offset.reset',    $options['auto_offset_reset']  ?? 'earliest');
        $conf->set('enable.auto.commit',   $options['auto_commit']        ?? 'true');

        $consumer = new KafkaConsumer($conf);
        $consumer->subscribe([$topic]);

        while (true) {
            $message = $consumer->consume((int) ($options['timeout_ms'] ?? 120_000));
            match ($message->err) {
                RD_KAFKA_RESP_ERR_NO_ERROR  => $callback(json_decode($message->payload, true, 512, JSON_THROW_ON_ERROR)),
                RD_KAFKA_RESP_ERR__TIMED_OUT => null, // normal timeout, continue
                default => $this->logger->warning('[Kafka] Consumer error', ['err' => $message->errstr()]),
            };
        }
    }

    /** {@inheritdoc} */
    public function acknowledge(mixed $messageId): void
    {
        // Kafka commits offsets automatically or via consumer->commit()
    }

    /** {@inheritdoc} */
    public function reject(mixed $messageId, bool $requeue = false): void
    {
        // Kafka does not have native nack; skip offset commit to replay
    }

    /** {@inheritdoc} */
    public function getDriverName(): string
    {
        return 'kafka';
    }

    // ──────────────────────────────────────────────────────────────────────────

    private function getProducer(): Producer
    {
        if ($this->producer === null) {
            $conf = new Conf();
            $conf->set('metadata.broker.list', $this->brokers);
            $this->producer = new Producer($conf);
        }

        return $this->producer;
    }
}
