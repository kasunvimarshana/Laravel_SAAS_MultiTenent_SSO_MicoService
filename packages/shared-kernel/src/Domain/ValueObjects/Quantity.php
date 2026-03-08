<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Non-negative quantity value object.
 */
final class Quantity extends ValueObject
{
    private function __construct(
        private readonly int $value,
        private readonly string $unit = 'unit'
    ) {
        if ($value < 0) {
            throw new InvalidArgumentException("Quantity cannot be negative: {$value}");
        }
    }

    public static function of(int $value, string $unit = 'unit'): self
    {
        return new self($value, $unit);
    }

    public static function zero(string $unit = 'unit'): self
    {
        return new self(0, $unit);
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function add(Quantity $other): self
    {
        return new self($this->value + $other->value, $this->unit);
    }

    public function subtract(Quantity $other): self
    {
        return new self($this->value - $other->value, $this->unit);
    }

    public function isZero(): bool
    {
        return $this->value === 0;
    }

    public function isGreaterThan(Quantity $other): bool
    {
        return $this->value > $other->value;
    }

    public function isLessThan(Quantity $other): bool
    {
        return $this->value < $other->value;
    }

    protected function components(): array
    {
        return ['value' => $this->value, 'unit' => $this->unit];
    }
}
