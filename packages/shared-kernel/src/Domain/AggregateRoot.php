<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Domain;

use Saas\SharedKernel\Domain\Events\DomainEvent;

/**
 * Base aggregate root that collects domain events for deferred dispatch.
 * Extends this in every domain aggregate (Product, Order, Tenant, …).
 */
abstract class AggregateRoot
{
    /** @var DomainEvent[] */
    private array $domainEvents = [];

    /**
     * Record a domain event to be dispatched after the transaction commits.
     */
    protected function recordEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * Pull all recorded events and clear the internal buffer.
     *
     * @return DomainEvent[]
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }

    /**
     * Peek at recorded events without clearing.
     *
     * @return DomainEvent[]
     */
    public function getDomainEvents(): array
    {
        return $this->domainEvents;
    }
}
