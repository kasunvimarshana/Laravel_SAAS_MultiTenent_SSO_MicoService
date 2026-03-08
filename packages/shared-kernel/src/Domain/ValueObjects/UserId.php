<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Domain\ValueObjects;

use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

/**
 * Strongly-typed user identifier (UUID v4).
 */
final class UserId extends ValueObject
{
    private readonly string $value;

    private function __construct(string $value)
    {
        if (!Uuid::isValid($value)) {
            throw new InvalidArgumentException("Invalid UserId: {$value}");
        }
        $this->value = $value;
    }

    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    public static function from(string $value): self
    {
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    protected function components(): array
    {
        return ['value' => $this->value];
    }
}
