<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Infrastructure\Repositories\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Provides full-text search across multiple searchable columns.
 */
trait CanSearch
{
    /**
     * Wrap all searchable columns in an OR-LIKE query when a search term is provided.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model> $query
     * @param  string|null                                  $term
     * @param  string[]                                     $columns
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    protected function applySearch(Builder $query, ?string $term, array $columns): Builder
    {
        if (empty($term) || empty($columns)) {
            return $query;
        }

        $sanitised = '%' . addcslashes($term, '%_\\') . '%';

        return $query->where(static function (Builder $q) use ($columns, $sanitised): void {
            foreach ($columns as $column) {
                $q->orWhere($column, 'LIKE', $sanitised);
            }
        });
    }
}
