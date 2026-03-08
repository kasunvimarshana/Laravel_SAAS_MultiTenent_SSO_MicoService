<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\DTOs\CreateProductDto;
use App\Application\DTOs\UpdateProductDto;
use App\Domain\Inventory\Models\Product;
use App\Domain\Inventory\Repositories\Contracts\ProductRepositoryInterface;
use App\Domain\Inventory\Repositories\Contracts\StockMovementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Saas\SharedKernel\Application\DTOs\PaginationDto;
use Saas\SharedKernel\Infrastructure\MessageBroker\Contracts\MessageBrokerInterface;
use Saas\SharedKernel\Infrastructure\Tenant\TenantContext;

/**
 * Product application service – all business logic lives here.
 * Controller stays thin; it only delegates and formats responses.
 */
final class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface       $productRepository,
        private readonly StockMovementRepositoryInterface $stockMovementRepository,
        private readonly MessageBrokerInterface           $messageBroker,
        private readonly TenantContext                    $tenantContext
    ) {}

    /**
     * List products with optional filtering, searching, sorting, and pagination.
     *
     * @return Collection<int, Product>|LengthAwarePaginator
     */
    public function listProducts(PaginationDto $pagination, array $filters = []): Collection|LengthAwarePaginator
    {
        $criteria = $pagination->mergeToCriteria(['filters' => $filters]);
        return $this->productRepository->findAll($criteria);
    }

    /**
     * Retrieve a single product by ID.
     */
    public function getProduct(string $id): Product
    {
        return $this->productRepository->findOrFail($id);
    }

    /**
     * Create a new product and publish a domain event.
     */
    public function createProduct(CreateProductDto $dto): Product
    {
        return DB::transaction(function () use ($dto): Product {
            $product = $this->productRepository->create([
                'tenant_id'      => $this->tenantContext->getTenantId(),
                'category_id'    => $dto->categoryId,
                'sku'            => $dto->sku,
                'name'           => $dto->name,
                'description'    => $dto->description,
                'price'          => $dto->priceInCents,
                'currency'       => $dto->currency,
                'stock_quantity' => $dto->initialStock,
                'reorder_level'  => $dto->reorderLevel,
                'status'         => 'active',
                'attributes'     => $dto->attributes,
            ]);

            // Record initial stock movement
            if ($dto->initialStock > 0) {
                $this->stockMovementRepository->record([
                    'tenant_id'       => $this->tenantContext->getTenantId(),
                    'product_id'      => $product->id,
                    'warehouse_id'    => $dto->warehouseId,
                    'type'            => 'in',
                    'quantity'        => $dto->initialStock,
                    'quantity_before' => 0,
                    'quantity_after'  => $dto->initialStock,
                    'notes'           => 'Initial stock on product creation',
                    'performed_by'    => auth()->id() ?? 'system',
                ]);
            }

            // Publish event to message broker
            $this->messageBroker->publish('inventory.product.created', [
                'tenant_id'  => $this->tenantContext->getTenantId(),
                'product_id' => $product->id,
                'sku'        => $product->sku,
                'name'       => $product->name,
            ]);

            return $product;
        });
    }

    /**
     * Update an existing product.
     */
    public function updateProduct(string $id, UpdateProductDto $dto): Product
    {
        return DB::transaction(function () use ($id, $dto): Product {
            $product = $this->productRepository->update($id, array_filter([
                'category_id'   => $dto->categoryId,
                'name'          => $dto->name,
                'description'   => $dto->description,
                'price'         => $dto->priceInCents,
                'reorder_level' => $dto->reorderLevel,
                'status'        => $dto->status,
                'attributes'    => $dto->attributes,
            ], fn($v) => $v !== null));

            $this->messageBroker->publish('inventory.product.updated', [
                'tenant_id'  => $this->tenantContext->getTenantId(),
                'product_id' => $product->id,
            ]);

            return $product;
        });
    }

    /**
     * Delete (soft-delete) a product.
     */
    public function deleteProduct(string $id): void
    {
        DB::transaction(function () use ($id): void {
            $this->productRepository->delete($id);

            $this->messageBroker->publish('inventory.product.deleted', [
                'tenant_id'  => $this->tenantContext->getTenantId(),
                'product_id' => $id,
            ]);
        });
    }

    /**
     * Adjust stock level for a product.
     */
    public function adjustStock(
        string $productId,
        int    $delta,
        string $type,
        string $warehouseId,
        ?string $referenceType = null,
        ?string $referenceId   = null,
        ?string $notes         = null
    ): Product {
        return DB::transaction(function () use ($productId, $delta, $type, $warehouseId, $referenceType, $referenceId, $notes): Product {
            $before  = $this->productRepository->findOrFail($productId)->stock_quantity;
            $product = $this->productRepository->adjustStock($productId, $delta);

            $this->stockMovementRepository->record([
                'tenant_id'       => $this->tenantContext->getTenantId(),
                'product_id'      => $productId,
                'warehouse_id'    => $warehouseId,
                'type'            => $type,
                'quantity'        => abs($delta),
                'quantity_before' => $before,
                'quantity_after'  => $product->stock_quantity,
                'reference_type'  => $referenceType,
                'reference_id'    => $referenceId,
                'notes'           => $notes,
                'performed_by'    => auth()->id() ?? 'system',
            ]);

            if ($product->isBelowReorderLevel()) {
                $this->messageBroker->publish('inventory.product.low_stock', [
                    'tenant_id'      => $this->tenantContext->getTenantId(),
                    'product_id'     => $productId,
                    'stock_quantity' => $product->stock_quantity,
                    'reorder_level'  => $product->reorder_level,
                ]);
            }

            return $product;
        });
    }
}
