<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Tests\Unit\Domain\ValueObjects;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saas\SharedKernel\Domain\ValueObjects\Money;

final class MoneyTest extends TestCase
{
    public function test_creates_money_from_amount_and_currency(): void
    {
        $money = Money::of(1000, 'USD');

        $this->assertSame(1000, $money->getAmount());
        $this->assertSame('USD', $money->getCurrency());
    }

    public function test_normalises_currency_to_uppercase(): void
    {
        $money = Money::of(500, 'usd');
        $this->assertSame('USD', $money->getCurrency());
    }

    public function test_throws_on_invalid_currency(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::of(100, 'INVALID');
    }

    public function test_addition_of_same_currency(): void
    {
        $a      = Money::of(100, 'USD');
        $b      = Money::of(200, 'USD');
        $result = $a->add($b);

        $this->assertSame(300, $result->getAmount());
    }

    public function test_subtraction_of_same_currency(): void
    {
        $a      = Money::of(500, 'USD');
        $b      = Money::of(200, 'USD');
        $result = $a->subtract($b);

        $this->assertSame(300, $result->getAmount());
    }

    public function test_throws_on_currency_mismatch(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::of(100, 'USD')->add(Money::of(100, 'EUR'));
    }

    public function test_equality(): void
    {
        $a = Money::of(100, 'USD');
        $b = Money::of(100, 'USD');
        $c = Money::of(200, 'USD');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function test_to_float_conversion(): void
    {
        $money = Money::of(1999, 'USD');
        $this->assertEqualsWithDelta(19.99, $money->toFloat(), 0.001);
    }

    public function test_zero_factory(): void
    {
        $zero = Money::zero('GBP');
        $this->assertTrue($zero->isZero());
        $this->assertSame('GBP', $zero->getCurrency());
    }

    public function test_multiply(): void
    {
        $money  = Money::of(1000, 'USD');
        $result = $money->multiply(2.5);
        $this->assertSame(2500, $result->getAmount());
    }
}
