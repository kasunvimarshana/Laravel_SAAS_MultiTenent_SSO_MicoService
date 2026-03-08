<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Domain\Events;

use Illuminate\Contracts\Events\Dispatcher;

/**
 * Thin wrapper that dispatches domain events through Laravel's event bus
 * so infrastructure-level listeners (e.g. projectors, message brokers) can react.
 */
final class DomainEventDispatcher
{
    public function __construct(private readonly Dispatcher $dispatcher) {}

    /**
     * Dispatch a single domain event.
     */
    public function dispatch(DomainEvent $event): void
    {
        $this->dispatcher->dispatch($event);
    }

    /**
     * Dispatch a collection of domain events, typically after aggregate changes.
     *
     * @param DomainEvent[] $events
     */
    public function dispatchAll(array $events): void
    {
        foreach ($events as $event) {
            $this->dispatch($event);
        }
    }
}
