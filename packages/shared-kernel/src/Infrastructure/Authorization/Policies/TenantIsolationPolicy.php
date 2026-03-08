<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Infrastructure\Authorization\Policies;

/**
 * Ensures a user can only access resources within their own tenant.
 * This is the most fundamental ABAC policy in a multi-tenant system.
 */
final class TenantIsolationPolicy implements PolicyInterface
{
    public function getName(): string
    {
        return 'tenant.isolation';
    }

    /**
     * Grant access only if the subject's tenant_id matches the resource's tenant_id.
     */
    public function evaluate(array $subject, array $resource, array $environment): bool
    {
        $subjectTenant  = $subject['tenant_id']  ?? null;
        $resourceTenant = $resource['tenant_id'] ?? null;

        // Super-admins bypass tenant isolation
        if (in_array('super-admin', $subject['roles'] ?? [], true)) {
            return true;
        }

        return $subjectTenant !== null
            && $resourceTenant !== null
            && $subjectTenant === $resourceTenant;
    }
}
