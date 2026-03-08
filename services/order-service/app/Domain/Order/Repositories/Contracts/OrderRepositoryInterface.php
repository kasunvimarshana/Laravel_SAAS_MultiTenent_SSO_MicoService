<?php

declare(strict_types=1);

namespace App\Domain\Order\Repositories\Contracts;

use App\Domain\Order\Models\Order;
use Saas\SharedKernel\Infrastructure\Repositories\Contracts\RepositoryInterface;

/**
 * @extends RepositoryInterface<Order>
 */
interface OrderRepositoryInterface extends RepositoryInterface
{
    public function findByOrderNumber(string $orderNumber): ?Order;
    public function findBySagaId(string $sagaId): ?Order;
}
