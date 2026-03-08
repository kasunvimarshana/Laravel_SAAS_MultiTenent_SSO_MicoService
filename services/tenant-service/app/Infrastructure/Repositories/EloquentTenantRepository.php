<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Repositories\Contracts\TenantRepositoryInterface;
use Saas\SharedKernel\Infrastructure\Repositories\BaseRepository;

/**
 * @extends BaseRepository<Tenant>
 */
final class EloquentTenantRepository extends BaseRepository implements TenantRepositoryInterface
{
    protected string $model = Tenant::class;

    protected array $searchableColumns = ['name', 'slug', 'domain'];

    public function findBySlug(string $slug): ?Tenant
    {
        return $this->newQuery()->where('slug', $slug)->first();
    }

    public function findByDomain(string $domain): ?Tenant
    {
        return $this->newQuery()->where('domain', $domain)->first();
    }
}
