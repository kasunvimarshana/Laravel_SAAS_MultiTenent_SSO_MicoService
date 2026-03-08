<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Validated email address value object.
 */
final class Email extends ValueObject
{
    private readonly string $value;

    private function __construct(string $value)
    {
        $normalised = strtolower(trim($value));
        if (!filter_var($normalised, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email address: {$value}");
        }
        $this->value = $normalised;
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
