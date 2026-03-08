<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Infrastructure\Repositories\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as ConcretePaginator;
use Illuminate\Support\Collection;

/**
 * Conditional pagination for queries, arrays, collections, and API responses.
 *
 * Rules:
 *  - If `per_page` key is present in $criteria → paginate
 *  - Otherwise → return all results as a Collection
 *  - Supports both `page` and `per_page` parameters
 */
trait CanPaginate
{
    /**
     * Conditionally paginate an Eloquent query.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model> $query
     * @param  array<string, mixed>                         $criteria
     * @return Collection<int, \Illuminate\Database\Eloquent\Model>|LengthAwarePaginator
     */
    protected function conditionalPaginate(
        Builder $query,
        array $criteria
    ): Collection|LengthAwarePaginator {
        if (!array_key_exists('per_page', $criteria)) {
            return $query->get();
        }

        $perPage = max(1, (int) $criteria['per_page']);
        $page    = max(1, (int) ($criteria['page'] ?? 1));

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Conditionally paginate any Collection (from arrays, API calls, etc.).
     *
     * @param  Collection<int, mixed>  $collection
     * @param  array<string, mixed>    $criteria
     * @return Collection<int, mixed>|LengthAwarePaginator
     */
    protected function conditionalPaginateCollection(
        Collection $collection,
        array $criteria
    ): Collection|LengthAwarePaginator {
        if (!array_key_exists('per_page', $criteria)) {
            return $collection;
        }

        $perPage = max(1, (int) $criteria['per_page']);
        $page    = max(1, (int) ($criteria['page'] ?? 1));
        $total   = $collection->count();
        $items   = $collection->forPage($page, $perPage)->values();

        return new ConcretePaginator(
            items:       $items,
            total:       $total,
            perPage:     $perPage,
            currentPage: $page,
            options:     ['path' => request()?->url() ?? '/']
        );
    }
}
