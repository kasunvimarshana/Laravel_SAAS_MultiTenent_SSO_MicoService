<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Infrastructure\Authorization\Contracts;

/**
 * Unified RBAC + ABAC authorization interface.
 * All authorization checks flow through this contract.
 */
interface AuthorizationInterface
{
    /**
     * Check if a user has a specific role (RBAC).
     *
     * @param  string|string[] $roles  Single role or list of roles (OR logic)
     */
    public function hasRole(string $userId, string|array $roles, string $tenantId = ''): bool;

    /**
     * Check if a user has a specific permission (RBAC).
     *
     * @param  string|string[] $permissions
     */
    public function hasPermission(string $userId, string|array $permissions, string $tenantId = ''): bool;

    /**
     * Attribute-based access control check.
     * Evaluates a named policy against the provided attributes.
     *
     * @param  array<string, mixed> $subject    Attributes of the user/actor
     * @param  array<string, mixed> $resource   Attributes of the resource being accessed
     * @param  array<string, mixed> $environment Contextual attributes (time, IP, etc.)
     */
    public function evaluatePolicy(
        string $policyName,
        array  $subject,
        array  $resource,
        array  $environment = []
    ): bool;

    /**
     * Return all permissions assigned to a user (direct + through roles).
     *
     * @return string[]
     */
    public function getPermissions(string $userId, string $tenantId = ''): array;
}
