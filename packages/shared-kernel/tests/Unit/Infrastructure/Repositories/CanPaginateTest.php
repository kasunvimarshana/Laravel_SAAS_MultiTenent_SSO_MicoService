<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Tests\Unit\Infrastructure\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Saas\SharedKernel\Infrastructure\Repositories\Concerns\CanPaginate;

/**
 * Tests the conditional pagination trait in isolation.
 */
final class CanPaginateTest extends TestCase
{
    private object $subject;

    protected function setUp(): void
    {
        // Create an anonymous class that uses the trait so we can call protected methods
        $this->subject = new class {
            use CanPaginate;

            public function testConditionalPaginateCollection(Collection $items, array $criteria): Collection|LengthAwarePaginator
            {
                return $this->conditionalPaginateCollection($items, $criteria);
            }
        };
    }

    public function test_returns_all_items_when_per_page_absent(): void
    {
        $items  = collect([1, 2, 3, 4, 5]);
        $result = $this->subject->testConditionalPaginateCollection($items, []);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(5, $result);
    }

    public function test_returns_paginator_when_per_page_present(): void
    {
        $items  = collect(range(1, 20));
        $result = $this->subject->testConditionalPaginateCollection($items, ['per_page' => 5, 'page' => 2]);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertSame(5, $result->perPage());
        $this->assertSame(2, $result->currentPage());
        $this->assertSame(20, $result->total());
        $this->assertCount(5, $result->items());
    }

    public function test_first_page_when_page_not_specified(): void
    {
        $items  = collect(range(1, 10));
        $result = $this->subject->testConditionalPaginateCollection($items, ['per_page' => 3]);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertSame(1, $result->currentPage());
        $this->assertSame(3, $result->perPage());
    }

    public function test_per_page_clamped_to_minimum_one(): void
    {
        $items  = collect([1, 2, 3]);
        $result = $this->subject->testConditionalPaginateCollection($items, ['per_page' => 0]);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertSame(1, $result->perPage());
    }

    public function test_page_clamped_to_minimum_one(): void
    {
        $items  = collect([1, 2, 3]);
        $result = $this->subject->testConditionalPaginateCollection($items, ['per_page' => 2, 'page' => -5]);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertSame(1, $result->currentPage());
    }
}
