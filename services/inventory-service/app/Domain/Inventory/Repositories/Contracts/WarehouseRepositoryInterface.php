<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Repositories\Contracts;

use App\Domain\Inventory\Models\Warehouse;
use Saas\SharedKernel\Infrastructure\Repositories\Contracts\RepositoryInterface;

/**
 * @extends RepositoryInterface<Warehouse>
 */
interface WarehouseRepositoryInterface extends RepositoryInterface
{
    public function findByCode(string $code): ?Warehouse;
}
