<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Infrastructure\Authorization\Policies;

/**
 * Registry for ABAC policies.
 * Policies are registered at boot time and evaluated at runtime.
 */
final class PolicyRegistry
{
    /** @var array<string, PolicyInterface> */
    private array $policies = [];

    public function register(PolicyInterface $policy): void
    {
        $this->policies[$policy->getName()] = $policy;
    }

    public function get(string $name): ?PolicyInterface
    {
        return $this->policies[$name] ?? null;
    }

    /**
     * @return string[]
     */
    public function getRegisteredNames(): array
    {
        return array_keys($this->policies);
    }
}
