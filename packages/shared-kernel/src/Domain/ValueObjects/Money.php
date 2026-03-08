<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Immutable monetary value with currency.
 */
final class Money extends ValueObject
{
    private function __construct(
        private readonly int $amount,   // stored as smallest unit (cents)
        private readonly string $currency // ISO 4217
    ) {
        if (strlen($currency) !== 3) {
            throw new InvalidArgumentException("Currency must be ISO 4217 (3 chars): {$currency}");
        }
    }

    public static function of(int $amount, string $currency): self
    {
        return new self($amount, strtoupper($currency));
    }

    public static function zero(string $currency = 'USD'): self
    {
        return new self(0, strtoupper($currency));
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amount - $other->amount, $this->currency);
    }

    public function multiply(int|float $factor): self
    {
        return new self((int) round($this->amount * $factor), $this->currency);
    }

    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function toFloat(): float
    {
        return $this->amount / 100;
    }

    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Currency mismatch: {$this->currency} vs {$other->currency}"
            );
        }
    }

    protected function components(): array
    {
        return ['amount' => $this->amount, 'currency' => $this->currency];
    }

    public function __toString(): string
    {
        return number_format($this->toFloat(), 2) . ' ' . $this->currency;
    }
}
