<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Infrastructure\Authorization\Policies;

/**
 * ABAC policy contract.
 * Each named policy encapsulates a specific access rule.
 */
interface PolicyInterface
{
    /**
     * Evaluate the policy.
     *
     * @param  array<string, mixed> $subject     User attributes (id, roles, tenant_id, plan)
     * @param  array<string, mixed> $resource    Resource attributes (type, owner_id, tenant_id, status)
     * @param  array<string, mixed> $environment Contextual attributes (ip, timestamp, device)
     * @return bool  true = access granted, false = denied
     */
    public function evaluate(array $subject, array $resource, array $environment): bool;

    /**
     * Unique policy name used for registration and lookup.
     */
    public function getName(): string;
}
