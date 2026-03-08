<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Repositories\Contracts;

use App\Domain\Inventory\Models\Category;
use Illuminate\Support\Collection;
use Saas\SharedKernel\Infrastructure\Repositories\Contracts\RepositoryInterface;

/**
 * @extends RepositoryInterface<Category>
 */
interface CategoryRepositoryInterface extends RepositoryInterface
{
    public function findBySlug(string $slug): ?Category;

    /** @return Collection<int, Category> */
    public function findRootCategories(): Collection;

    /** @return Collection<int, Category> */
    public function findChildren(string $parentId): Collection;
}
