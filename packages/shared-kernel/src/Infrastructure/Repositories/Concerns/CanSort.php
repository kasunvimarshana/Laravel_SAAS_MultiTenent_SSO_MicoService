<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Infrastructure\Repositories\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Provides dynamic sorting for Eloquent query builders.
 */
trait CanSort
{
    /**
     * Apply ORDER BY to the query, falling back to the repository's defaults.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model> $query
     * @param  string                                       $column
     * @param  string                                       $direction 'asc'|'desc'
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    protected function applySort(Builder $query, string $column, string $direction = 'desc'): Builder
    {
        $direction = in_array(strtolower($direction), ['asc', 'desc'], true)
            ? strtolower($direction)
            : 'desc';

        return $query->orderBy($column, $direction);
    }
}
