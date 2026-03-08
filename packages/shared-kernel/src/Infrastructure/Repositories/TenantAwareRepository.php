<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Infrastructure\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Saas\SharedKernel\Infrastructure\Tenant\TenantContext;

/**
 * Repository that automatically scopes all queries to the current tenant.
 * Extend this instead of BaseRepository in multi-tenant modules.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 * @extends  BaseRepository<TModel>
 */
abstract class TenantAwareRepository extends BaseRepository
{
    public function __construct(private readonly TenantContext $tenantContext)
    {
        // Intentionally blank – dependencies injected via constructor injection
    }

    /**
     * Override to inject tenant scope on every query.
     *
     * @return Builder<TModel>
     */
    protected function newQuery(): Builder
    {
        $query = parent::newQuery();

        if ($tenantId = $this->tenantContext->getTenantId()) {
            $query->where('tenant_id', $tenantId);
        }

        return $query;
    }

    /**
     * Automatically inject tenant_id when creating entities.
     */
    public function create(array $attributes): \Illuminate\Database\Eloquent\Model
    {
        if ($tenantId = $this->tenantContext->getTenantId()) {
            $attributes['tenant_id'] = $tenantId;
        }

        return parent::create($attributes);
    }
}
