<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Domain\Events;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

/**
 * Base domain event carrying metadata for distributed messaging.
 */
abstract class DomainEvent
{
    public readonly string $eventId;
    public readonly DateTimeImmutable $occurredAt;
    public readonly int $version;

    public function __construct(
        public readonly string $aggregateId,
        public readonly string $tenantId,
        int $version = 1
    ) {
        $this->eventId    = Uuid::uuid4()->toString();
        $this->occurredAt = new DateTimeImmutable();
        $this->version    = $version;
    }

    /**
     * Unique event type name for routing (e.g. "inventory.product.created").
     */
    abstract public function getEventName(): string;

    /**
     * Payload serialised for transport over message brokers.
     *
     * @return array<string, mixed>
     */
    abstract public function getPayload(): array;

    /**
     * Full envelope for message broker transport.
     *
     * @return array<string, mixed>
     */
    final public function toEnvelope(): array
    {
        return [
            'event_id'     => $this->eventId,
            'event_name'   => $this->getEventName(),
            'aggregate_id' => $this->aggregateId,
            'tenant_id'    => $this->tenantId,
            'version'      => $this->version,
            'occurred_at'  => $this->occurredAt->format(DateTimeImmutable::ATOM),
            'payload'      => $this->getPayload(),
        ];
    }
}
