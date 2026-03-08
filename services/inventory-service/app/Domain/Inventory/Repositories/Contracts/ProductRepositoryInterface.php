<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Repositories\Contracts;

use App\Domain\Inventory\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Saas\SharedKernel\Infrastructure\Repositories\Contracts\RepositoryInterface;

/**
 * Product repository contract.
 *
 * @extends RepositoryInterface<Product>
 */
interface ProductRepositoryInterface extends RepositoryInterface
{
    /**
     * Find a product by its SKU within the current tenant.
     */
    public function findBySku(string $sku): ?Product;

    /**
     * Find products below their reorder level.
     *
     * @return Collection<int, Product>|LengthAwarePaginator
     */
    public function findBelowReorderLevel(array $criteria = []): Collection|LengthAwarePaginator;

    /**
     * Find products by category.
     *
     * @return Collection<int, Product>|LengthAwarePaginator
     */
    public function findByCategory(string $categoryId, array $criteria = []): Collection|LengthAwarePaginator;

    /**
     * Adjust the stock quantity of a product atomically.
     * Positive delta = stock in; negative delta = stock out.
     */
    public function adjustStock(string $productId, int $delta): Product;

    /**
     * Reserve stock for an order (reduces available but not actual quantity).
     */
    public function reserveStock(string $productId, int $quantity): bool;

    /**
     * Release previously reserved stock.
     */
    public function releaseStock(string $productId, int $quantity): bool;
}
