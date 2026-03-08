<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Domain\ValueObjects;

/**
 * Base Value Object implementing equality by value.
 * Immutable – no setters allowed.
 */
abstract class ValueObject
{
    /**
     * Return all scalar components that define equality.
     *
     * @return array<string, mixed>
     */
    abstract protected function components(): array;

    /**
     * Value objects are equal when all their components are equal.
     */
    final public function equals(self $other): bool
    {
        return get_class($this) === get_class($other)
            && $this->components() === $other->components();
    }

    public function __toString(): string
    {
        return implode('|', array_values($this->components()));
    }
}
