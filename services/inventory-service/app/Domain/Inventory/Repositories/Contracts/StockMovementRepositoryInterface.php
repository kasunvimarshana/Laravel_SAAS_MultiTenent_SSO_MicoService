<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Repositories\Contracts;

use App\Domain\Inventory\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Saas\SharedKernel\Infrastructure\Repositories\Contracts\RepositoryInterface;

/**
 * @extends RepositoryInterface<StockMovement>
 */
interface StockMovementRepositoryInterface extends RepositoryInterface
{
    /**
     * @return Collection<int, StockMovement>|LengthAwarePaginator
     */
    public function findByProduct(string $productId, array $criteria = []): Collection|LengthAwarePaginator;

    /**
     * @return Collection<int, StockMovement>|LengthAwarePaginator
     */
    public function findByWarehouse(string $warehouseId, array $criteria = []): Collection|LengthAwarePaginator;

    /**
     * Record a stock movement with before/after quantities.
     */
    public function record(array $data): StockMovement;
}
