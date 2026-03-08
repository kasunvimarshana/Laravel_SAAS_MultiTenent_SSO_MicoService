<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Infrastructure\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Saas\SharedKernel\Domain\Exceptions\EntityNotFoundException;
use Saas\SharedKernel\Infrastructure\Repositories\Concerns\CanFilter;
use Saas\SharedKernel\Infrastructure\Repositories\Concerns\CanPaginate;
use Saas\SharedKernel\Infrastructure\Repositories\Concerns\CanSearch;
use Saas\SharedKernel\Infrastructure\Repositories\Concerns\CanSort;
use Saas\SharedKernel\Infrastructure\Repositories\Contracts\RepositoryInterface;

/**
 * Fully-dynamic, extensible Base Repository.
 *
 * Features:
 *  - CRUD operations
 *  - Conditional pagination: returns all when `per_page` is absent
 *  - Filtering, searching, sorting via pluggable Concerns
 *  - Cross-service data access via iterable pagination
 *  - Tenant-aware queries when the model has a `tenant_id` column
 *
 * @template TModel of Model
 * @implements RepositoryInterface<TModel>
 */
abstract class BaseRepository implements RepositoryInterface
{
    use CanFilter;
    use CanSearch;
    use CanSort;
    use CanPaginate;

    /**
     * The Eloquent model class this repository manages.
     *
     * @var class-string<TModel>
     */
    protected string $model;

    /**
     * Default columns returned by findAll queries.
     *
     * @var string[]
     */
    protected array $defaultColumns = ['*'];

    /**
     * Columns that can be used for full-text search.
     *
     * @var string[]
     */
    protected array $searchableColumns = [];

    /**
     * Default column used for sorting when none is specified.
     */
    protected string $defaultSortColumn = 'created_at';

    /** @var 'asc'|'desc' */
    protected string $defaultSortDirection = 'desc';

    // ──────────────────────────────────────────────────────────────────────────
    // RepositoryInterface implementation
    // ──────────────────────────────────────────────────────────────────────────

    /** {@inheritdoc} */
    public function findById(int|string $id): ?Model
    {
        return $this->newQuery()->find($id);
    }

    /** {@inheritdoc} */
    public function findOrFail(int|string $id): Model
    {
        return $this->findById($id)
            ?? throw new EntityNotFoundException(class_basename($this->model), (string) $id);
    }

    /**
     * {@inheritdoc}
     *
     * Supported criteria keys:
     *  - filters    (array)   – column => value pairs
     *  - search     (string)  – free-text search across searchableColumns
     *  - sort_by    (string)  – sort column
     *  - sort_dir   (string)  – 'asc' | 'desc'
     *  - per_page   (int)     – enables pagination; absence returns full Collection
     *  - page       (int)     – current page (used with per_page)
     *  - columns    (array)   – override selected columns
     *  - with       (array)   – eager-loaded relations
     *  - scopes     (array)   – named Eloquent scopes to apply
     */
    public function findAll(array $criteria = []): Collection|LengthAwarePaginator
    {
        $query = $this->newQuery()->select($criteria['columns'] ?? $this->defaultColumns);

        // Eager loading
        if (!empty($criteria['with'])) {
            $query->with($criteria['with']);
        }

        // Named scopes
        foreach ($criteria['scopes'] ?? [] as $scope => $args) {
            $query->{$scope}(...(is_array($args) ? $args : [$args]));
        }

        // Filtering
        $query = $this->applyFilters($query, $criteria['filters'] ?? []);

        // Searching
        $query = $this->applySearch($query, $criteria['search'] ?? null, $this->searchableColumns);

        // Sorting
        $query = $this->applySort(
            $query,
            $criteria['sort_by']  ?? $this->defaultSortColumn,
            $criteria['sort_dir'] ?? $this->defaultSortDirection
        );

        // Conditional pagination
        return $this->conditionalPaginate($query, $criteria);
    }

    /** {@inheritdoc} */
    public function create(array $attributes): Model
    {
        return $this->newQuery()->create($attributes);
    }

    /** {@inheritdoc} */
    public function update(int|string $id, array $attributes): Model
    {
        $entity = $this->findOrFail($id);
        $entity->update($attributes);
        return $entity->fresh();
    }

    /** {@inheritdoc} */
    public function delete(int|string $id): bool
    {
        $entity = $this->findOrFail($id);
        return (bool) $entity->delete();
    }

    /** {@inheritdoc} */
    public function exists(int|string $id): bool
    {
        return $this->newQuery()->where($this->getModel()->getKeyName(), $id)->exists();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Create a new Eloquent query builder instance for the managed model.
     *
     * @return Builder<TModel>
     */
    protected function newQuery(): Builder
    {
        return $this->getModel()->newQuery();
    }

    /**
     * Return a fresh model instance (not bound to any row).
     *
     * @return TModel
     */
    protected function getModel(): Model
    {
        return app($this->model);
    }

    /**
     * Paginate an arbitrary iterable (array, Collection, API response, etc.)
     * using the same `per_page` / `page` criteria convention.
     *
     * @param  iterable<mixed>      $items
     * @param  array<string, mixed> $criteria
     * @return Collection<int, mixed>|LengthAwarePaginator
     */
    public function paginateIterable(iterable $items, array $criteria = []): Collection|LengthAwarePaginator
    {
        $collection = collect($items);
        return $this->conditionalPaginateCollection($collection, $criteria);
    }
}
