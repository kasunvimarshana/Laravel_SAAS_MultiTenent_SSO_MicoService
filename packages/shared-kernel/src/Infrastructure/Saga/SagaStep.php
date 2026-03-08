<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Infrastructure\Saga;

/**
 * A single step in a saga.
 * Each step has a forward action and a compensating action (rollback).
 */
final class SagaStep
{
    /**
     * @param  string   $name       Human-readable step name (for logging)
     * @param  callable $execute    The forward action to run
     * @param  callable $compensate The compensating/rollback action to run on failure
     */
    public function __construct(
        private readonly string   $name,
        private readonly callable $execute,
        private readonly callable $compensate
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Execute the forward action with the given context.
     *
     * @param  array<string, mixed> $context
     * @return array<string, mixed> Updated context (merges return value)
     */
    public function execute(array $context): array
    {
        $result = ($this->execute)($context);
        return is_array($result) ? array_merge($context, $result) : $context;
    }

    /**
     * Execute the compensating action to roll back changes.
     *
     * @param  array<string, mixed> $context
     */
    public function compensate(array $context): void
    {
        ($this->compensate)($context);
    }
}
