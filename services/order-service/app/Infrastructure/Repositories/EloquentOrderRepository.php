<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Order\Models\Order;
use App\Domain\Order\Repositories\Contracts\OrderRepositoryInterface;
use Saas\SharedKernel\Infrastructure\Repositories\TenantAwareRepository;
use Saas\SharedKernel\Infrastructure\Tenant\TenantContext;

/**
 * @extends TenantAwareRepository<Order>
 */
final class EloquentOrderRepository extends TenantAwareRepository implements OrderRepositoryInterface
{
    protected string $model = Order::class;

    protected array $searchableColumns = ['order_number', 'customer_id'];

    public function __construct(TenantContext $tenantContext)
    {
        parent::__construct($tenantContext);
    }

    public function findByOrderNumber(string $orderNumber): ?Order
    {
        return $this->newQuery()->where('order_number', $orderNumber)->first();
    }

    public function findBySagaId(string $sagaId): ?Order
    {
        return $this->newQuery()->where('saga_id', $sagaId)->first();
    }
}
