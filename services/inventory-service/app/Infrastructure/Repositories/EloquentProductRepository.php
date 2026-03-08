<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Inventory\Models\Product;
use App\Domain\Inventory\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Saas\SharedKernel\Domain\Exceptions\EntityNotFoundException;
use Saas\SharedKernel\Infrastructure\Repositories\TenantAwareRepository;
use Saas\SharedKernel\Infrastructure\Tenant\TenantContext;

/**
 * Eloquent-backed product repository with full tenant isolation.
 *
 * @extends TenantAwareRepository<Product>
 */
final class EloquentProductRepository extends TenantAwareRepository implements ProductRepositoryInterface
{
    protected string $model = Product::class;

    protected array $searchableColumns = ['name', 'sku', 'description'];

    protected string $defaultSortColumn = 'name';

    public function __construct(TenantContext $tenantContext)
    {
        parent::__construct($tenantContext);
    }

    /** {@inheritdoc} */
    public function findBySku(string $sku): ?Product
    {
        return $this->newQuery()->where('sku', $sku)->first();
    }

    /** {@inheritdoc} */
    public function findBelowReorderLevel(array $criteria = []): Collection|LengthAwarePaginator
    {
        $criteria['filters'] = array_merge($criteria['filters'] ?? [], [
            'stock_quantity' => ['operator' => '<=', 'value' => DB::raw('reorder_level')],
        ]);

        return $this->findAll($criteria);
    }

    /** {@inheritdoc} */
    public function findByCategory(string $categoryId, array $criteria = []): Collection|LengthAwarePaginator
    {
        $criteria['filters'] = array_merge($criteria['filters'] ?? [], [
            'category_id' => $categoryId,
        ]);

        return $this->findAll($criteria);
    }

    /** {@inheritdoc} */
    public function adjustStock(string $productId, int $delta): Product
    {
        return DB::transaction(function () use ($productId, $delta): Product {
            /** @var Product $product */
            $product = $this->newQuery()
                ->lockForUpdate()
                ->findOrFail($productId);

            $newQty = $product->stock_quantity + $delta;

            if ($newQty < 0) {
                throw new \Saas\SharedKernel\Domain\Exceptions\ValidationException(
                    ['stock_quantity' => ['Insufficient stock for this operation.']]
                );
            }

            $product->update(['stock_quantity' => $newQty]);
            return $product->fresh();
        });
    }

    /** {@inheritdoc} */
    public function reserveStock(string $productId, int $quantity): bool
    {
        return (bool) DB::transaction(function () use ($productId, $quantity): int {
            return $this->newQuery()
                ->where('id', $productId)
                ->where('stock_quantity', '>=', $quantity)
                ->decrement('stock_quantity', $quantity);
        });
    }

    /** {@inheritdoc} */
    public function releaseStock(string $productId, int $quantity): bool
    {
        return (bool) $this->newQuery()
            ->where('id', $productId)
            ->increment('stock_quantity', $quantity);
    }
}
