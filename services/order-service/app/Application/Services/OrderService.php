<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\DTOs\CreateOrderDto;
use App\Domain\Order\Models\Order;
use App\Domain\Order\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Saas\SharedKernel\Application\DTOs\PaginationDto;
use Saas\SharedKernel\Infrastructure\MessageBroker\Contracts\MessageBrokerInterface;
use Saas\SharedKernel\Infrastructure\Saga\SagaOrchestrator;
use Saas\SharedKernel\Infrastructure\Saga\SagaStep;
use Saas\SharedKernel\Infrastructure\Tenant\TenantContext;

/**
 * Order application service with Saga orchestration for distributed transactions.
 *
 * The `CreateOrder` flow uses the Saga pattern:
 *  1. Create order record (pending)
 *  2. Reserve stock in Inventory Service
 *  3. Confirm order (active)
 *  4. Publish order.created event
 *
 * On any failure, compensating transactions run in reverse order.
 */
final class OrderService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly SagaOrchestrator         $sagaOrchestrator,
        private readonly MessageBrokerInterface   $messageBroker,
        private readonly TenantContext            $tenantContext
    ) {}

    /**
     * List orders with filtering and pagination.
     *
     * @return Collection<int, Order>|LengthAwarePaginator
     */
    public function list(PaginationDto $pagination, array $filters = []): Collection|LengthAwarePaginator
    {
        return $this->orderRepository->findAll($pagination->mergeToCriteria(['filters' => $filters]));
    }

    /**
     * Get a single order.
     */
    public function get(string $id): Order
    {
        return $this->orderRepository->findOrFail($id);
    }

    /**
     * Create an order using the Saga orchestration pattern.
     * All steps are wrapped in compensating transactions.
     */
    public function create(CreateOrderDto $dto): Order
    {
        $tenantId  = $this->tenantContext->getTenantId();
        $sagaId    = \Ramsey\Uuid\Uuid::uuid4()->toString();

        $steps = [
            // Step 1: Create order record in pending state
            new SagaStep(
                name:      'create-order-record',
                execute:   function (array $ctx) use ($dto, $tenantId, $sagaId): array {
                    $orderNumber = 'ORD-' . strtoupper(substr($sagaId, 0, 8));
                    $subtotal    = array_sum(array_map(
                        fn($item) => $item['quantity'] * $item['unit_price'],
                        $dto->items
                    ));

                    $order = $this->orderRepository->create([
                        'tenant_id'        => $tenantId,
                        'customer_id'      => $dto->customerId,
                        'order_number'     => $orderNumber,
                        'status'           => 'pending',
                        'subtotal'         => $subtotal,
                        'tax'              => (int) round($subtotal * 0.1),
                        'shipping'         => $dto->shippingCost,
                        'total'            => $subtotal + (int) round($subtotal * 0.1) + $dto->shippingCost,
                        'currency'         => $dto->currency,
                        'shipping_address' => $dto->shippingAddress,
                        'saga_id'          => $sagaId,
                        'metadata'         => $dto->metadata,
                    ]);

                    // Create order items
                    foreach ($dto->items as $item) {
                        $order->items()->create([
                            'product_id'   => $item['product_id'],
                            'product_name' => $item['product_name'],
                            'product_sku'  => $item['product_sku'],
                            'quantity'     => $item['quantity'],
                            'unit_price'   => $item['unit_price'],
                            'subtotal'     => $item['quantity'] * $item['unit_price'],
                        ]);
                    }

                    return ['order_id' => $order->id, 'order' => $order];
                },
                compensate: function (array $ctx): void {
                    // Compensate: delete the order
                    if (isset($ctx['order_id'])) {
                        $this->orderRepository->delete($ctx['order_id']);
                    }
                }
            ),

            // Step 2: Reserve stock via Inventory Service
            new SagaStep(
                name:      'reserve-inventory-stock',
                execute:   function (array $ctx) use ($dto, $tenantId): array {
                    $inventoryUrl = config('services.inventory.url', env('INVENTORY_SERVICE_URL'));

                    $reservations = [];
                    foreach ($dto->items as $item) {
                        $response = Http::withHeaders([
                            'X-Tenant-ID'   => $tenantId,
                            'X-Saga-ID'     => $ctx['order_id'],
                        ])->post("{$inventoryUrl}/api/v1/products/{$item['product_id']}/adjust-stock", [
                            'delta'        => -$item['quantity'],
                            'type'         => 'out',
                            'warehouse_id' => $item['warehouse_id'] ?? $dto->warehouseId,
                            'reference_type' => 'Order',
                            'reference_id'   => $ctx['order_id'],
                            'notes'          => "Reserved for order {$ctx['order_id']}",
                        ]);

                        if (!$response->successful()) {
                            throw new \RuntimeException(
                                "Failed to reserve stock for product {$item['product_id']}: " .
                                $response->json('message', 'Unknown error')
                            );
                        }

                        $reservations[] = [
                            'product_id' => $item['product_id'],
                            'quantity'   => $item['quantity'],
                            'warehouse_id' => $item['warehouse_id'] ?? $dto->warehouseId,
                        ];
                    }

                    return ['reservations' => $reservations];
                },
                compensate: function (array $ctx) use ($tenantId): void {
                    // Compensate: release the reserved stock
                    $inventoryUrl = config('services.inventory.url', env('INVENTORY_SERVICE_URL'));

                    foreach ($ctx['reservations'] ?? [] as $res) {
                        Http::withHeaders(['X-Tenant-ID' => $tenantId])
                            ->post("{$inventoryUrl}/api/v1/products/{$res['product_id']}/adjust-stock", [
                                'delta'        => $res['quantity'],
                                'type'         => 'in',
                                'warehouse_id' => $res['warehouse_id'],
                                'notes'        => 'Stock released due to order saga rollback',
                            ])->throw();
                    }
                }
            ),

            // Step 3: Confirm the order
            new SagaStep(
                name:      'confirm-order',
                execute:   function (array $ctx): array {
                    $order = $this->orderRepository->update($ctx['order_id'], ['status' => 'confirmed']);
                    return ['order' => $order];
                },
                compensate: function (array $ctx): void {
                    // Compensate: revert to pending (allows further cleanup)
                    if (isset($ctx['order_id'])) {
                        $this->orderRepository->update($ctx['order_id'], ['status' => 'pending']);
                    }
                }
            ),

            // Step 4: Publish event
            new SagaStep(
                name:      'publish-order-created-event',
                execute:   function (array $ctx) use ($tenantId): array {
                    $this->messageBroker->publish('order.created', [
                        'tenant_id' => $tenantId,
                        'order_id'  => $ctx['order_id'],
                        'saga_id'   => $ctx['order']['saga_id'] ?? null,
                    ]);
                    return [];
                },
                compensate: function (array $ctx) use ($tenantId): void {
                    $this->messageBroker->publish('order.cancelled', [
                        'tenant_id' => $tenantId,
                        'order_id'  => $ctx['order_id'] ?? null,
                    ]);
                }
            ),
        ];

        $result = $this->sagaOrchestrator->run($steps, []);

        return $result['order'];
    }

    /**
     * Cancel an order and release reserved stock.
     */
    public function cancel(string $id, string $reason = ''): Order
    {
        return DB::transaction(function () use ($id, $reason): Order {
            $order = $this->orderRepository->findOrFail($id);

            if (!$order->canBeCancelled()) {
                throw new \Saas\SharedKernel\Domain\Exceptions\ValidationException(
                    ['status' => ['Order cannot be cancelled in its current status.']]
                );
            }

            $order = $this->orderRepository->update($id, [
                'status'   => 'cancelled',
                'metadata' => array_merge($order->metadata ?? [], ['cancel_reason' => $reason]),
            ]);

            $this->messageBroker->publish('order.cancelled', [
                'tenant_id' => $this->tenantContext->getTenantId(),
                'order_id'  => $id,
                'reason'    => $reason,
            ]);

            return $order;
        });
    }
}
