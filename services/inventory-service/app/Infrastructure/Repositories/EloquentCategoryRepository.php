<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Inventory\Models\Category;
use App\Domain\Inventory\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Support\Collection;
use Saas\SharedKernel\Infrastructure\Repositories\TenantAwareRepository;
use Saas\SharedKernel\Infrastructure\Tenant\TenantContext;

/**
 * @extends TenantAwareRepository<Category>
 */
final class EloquentCategoryRepository extends TenantAwareRepository implements CategoryRepositoryInterface
{
    protected string $model = Category::class;

    protected array $searchableColumns = ['name', 'slug', 'description'];

    public function __construct(TenantContext $tenantContext)
    {
        parent::__construct($tenantContext);
    }

    public function findBySlug(string $slug): ?Category
    {
        return $this->newQuery()->where('slug', $slug)->first();
    }

    public function findRootCategories(): Collection
    {
        return $this->newQuery()->whereNull('parent_id')->get();
    }

    public function findChildren(string $parentId): Collection
    {
        return $this->newQuery()->where('parent_id', $parentId)->get();
    }
}
