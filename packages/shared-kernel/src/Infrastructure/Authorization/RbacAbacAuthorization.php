<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Infrastructure\Authorization;

use Psr\Log\LoggerInterface;
use Saas\SharedKernel\Infrastructure\Authorization\Contracts\AuthorizationInterface;
use Saas\SharedKernel\Infrastructure\Authorization\Policies\PolicyRegistry;

/**
 * Combined RBAC + ABAC authorization implementation.
 *
 * RBAC  – roles/permissions are stored per-tenant in the auth service database
 *         (synced via the Spatie permission package on each user model).
 * ABAC  – named policies are evaluated against subject, resource, and environment
 *         attribute bags. Policies are registered in PolicyRegistry.
 */
abstract class RbacAbacAuthorization implements AuthorizationInterface
{
    public function __construct(
        private readonly PolicyRegistry  $policyRegistry,
        private readonly LoggerInterface $logger
    ) {}

    /** {@inheritdoc} */
    public function hasRole(string $userId, string|array $roles, string $tenantId = ''): bool
    {
        // Delegate to Spatie HasRoles at the HTTP layer (via Gate/Policy).
        // This method is provided for programmatic cross-service checks.
        $this->logger->debug('[Authorization] Role check', [
            'user_id'   => $userId,
            'roles'     => $roles,
            'tenant_id' => $tenantId,
        ]);

        return false; // Overridden in concrete implementations
    }

    /** {@inheritdoc} */
    public function hasPermission(string $userId, string|array $permissions, string $tenantId = ''): bool
    {
        $this->logger->debug('[Authorization] Permission check', [
            'user_id'     => $userId,
            'permissions' => $permissions,
            'tenant_id'   => $tenantId,
        ]);

        return false; // Overridden in concrete implementations
    }

    /** {@inheritdoc} */
    public function evaluatePolicy(
        string $policyName,
        array  $subject,
        array  $resource,
        array  $environment = []
    ): bool {
        $policy = $this->policyRegistry->get($policyName);

        if (!$policy) {
            $this->logger->warning('[Authorization] Policy not found', ['policy' => $policyName]);
            return false;
        }

        $result = $policy->evaluate($subject, $resource, $environment);

        $this->logger->debug('[Authorization] Policy evaluated', [
            'policy' => $policyName,
            'result' => $result,
        ]);

        return $result;
    }

    /** {@inheritdoc} */
    public function getPermissions(string $userId, string $tenantId = ''): array
    {
        return []; // Overridden in concrete implementations
    }
}
