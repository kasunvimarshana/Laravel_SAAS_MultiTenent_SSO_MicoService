<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Repositories\Contracts\WarehouseRepositoryInterface;
use Saas\SharedKernel\Infrastructure\Repositories\TenantAwareRepository;
use Saas\SharedKernel\Infrastructure\Tenant\TenantContext;

/**
 * @extends TenantAwareRepository<Warehouse>
 */
final class EloquentWarehouseRepository extends TenantAwareRepository implements WarehouseRepositoryInterface
{
    protected string $model = Warehouse::class;

    protected array $searchableColumns = ['name', 'code'];

    public function __construct(TenantContext $tenantContext)
    {
        parent::__construct($tenantContext);
    }

    public function findByCode(string $code): ?Warehouse
    {
        return $this->newQuery()->where('code', $code)->first();
    }
}
