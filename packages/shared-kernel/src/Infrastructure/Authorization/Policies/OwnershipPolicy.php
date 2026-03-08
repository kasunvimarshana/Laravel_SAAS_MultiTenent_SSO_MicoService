<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Infrastructure\Authorization\Policies;

/**
 * Grants access to resource owners and admins.
 */
final class OwnershipPolicy implements PolicyInterface
{
    public function getName(): string
    {
        return 'resource.ownership';
    }

    public function evaluate(array $subject, array $resource, array $environment): bool
    {
        $userId = $subject['id'] ?? null;
        $ownerId = $resource['owner_id'] ?? $resource['user_id'] ?? null;

        // Admins and super-admins bypass ownership check
        $adminRoles = ['admin', 'super-admin', 'tenant-admin'];
        foreach ($adminRoles as $role) {
            if (in_array($role, $subject['roles'] ?? [], true)) {
                return true;
            }
        }

        return $userId !== null && $userId === $ownerId;
    }
}
