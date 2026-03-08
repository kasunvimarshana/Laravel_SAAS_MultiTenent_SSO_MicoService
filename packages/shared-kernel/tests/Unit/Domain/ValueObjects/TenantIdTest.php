<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Tests\Unit\Domain\ValueObjects;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saas\SharedKernel\Domain\ValueObjects\TenantId;

final class TenantIdTest extends TestCase
{
    public function test_generates_valid_uuid(): void
    {
        $id = TenantId::generate();
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $id->toString()
        );
    }

    public function test_creates_from_valid_uuid(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $id   = TenantId::from($uuid);
        $this->assertSame($uuid, $id->toString());
    }

    public function test_throws_on_invalid_uuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TenantId::from('not-a-uuid');
    }

    public function test_equality(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $a    = TenantId::from($uuid);
        $b    = TenantId::from($uuid);
        $c    = TenantId::generate();

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }
}
