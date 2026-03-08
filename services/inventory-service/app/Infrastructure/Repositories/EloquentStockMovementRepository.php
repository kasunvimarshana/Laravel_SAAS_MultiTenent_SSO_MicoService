<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Inventory\Repositories\Contracts\StockMovementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Saas\SharedKernel\Infrastructure\Repositories\TenantAwareRepository;
use Saas\SharedKernel\Infrastructure\Tenant\TenantContext;

/**
 * @extends TenantAwareRepository<StockMovement>
 */
final class EloquentStockMovementRepository extends TenantAwareRepository implements StockMovementRepositoryInterface
{
    protected string $model = StockMovement::class;

    protected string $defaultSortColumn = 'created_at';

    public function __construct(TenantContext $tenantContext)
    {
        parent::__construct($tenantContext);
    }

    public function findByProduct(string $productId, array $criteria = []): Collection|LengthAwarePaginator
    {
        $criteria['filters'] = array_merge($criteria['filters'] ?? [], ['product_id' => $productId]);
        return $this->findAll($criteria);
    }

    public function findByWarehouse(string $warehouseId, array $criteria = []): Collection|LengthAwarePaginator
    {
        $criteria['filters'] = array_merge($criteria['filters'] ?? [], ['warehouse_id' => $warehouseId]);
        return $this->findAll($criteria);
    }

    public function record(array $data): StockMovement
    {
        return $this->create($data);
    }
}
