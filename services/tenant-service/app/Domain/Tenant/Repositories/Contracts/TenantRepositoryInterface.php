<?php

declare(strict_types=1);

namespace App\Domain\Tenant\Repositories\Contracts;

use App\Domain\Tenant\Models\Tenant;
use Saas\SharedKernel\Infrastructure\Repositories\Contracts\RepositoryInterface;

/**
 * @extends RepositoryInterface<Tenant>
 */
interface TenantRepositoryInterface extends RepositoryInterface
{
    public function findBySlug(string $slug): ?Tenant;
    public function findByDomain(string $domain): ?Tenant;
}
