<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Tests\Unit\Domain\ValueObjects;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saas\SharedKernel\Domain\ValueObjects\Email;

final class EmailTest extends TestCase
{
    public function test_creates_valid_email(): void
    {
        $email = Email::from('User@Example.COM');
        $this->assertSame('user@example.com', $email->toString());
    }

    public function test_throws_on_invalid_email(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Email::from('not-an-email');
    }

    public function test_normalises_to_lowercase(): void
    {
        $email = Email::from('ADMIN@SAAS.IO');
        $this->assertSame('admin@saas.io', $email->toString());
    }

    public function test_equality(): void
    {
        $a = Email::from('test@example.com');
        $b = Email::from('TEST@EXAMPLE.COM');
        $c = Email::from('other@example.com');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }
}
