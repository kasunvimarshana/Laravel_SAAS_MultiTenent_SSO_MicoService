<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Infrastructure\Repositories\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Provides dynamic filter application for Eloquent query builders.
 */
trait CanFilter
{
    /**
     * Apply an associative array of filters to the query.
     *
     * Supports:
     *  - Simple equality:        ['status' => 'active']
     *  - Operator filter:        ['price' => ['operator' => '>=', 'value' => 100]]
     *  - Null checks:            ['deleted_at' => ['operator' => 'null']]
     *  - Not-null checks:        ['deleted_at' => ['operator' => 'not_null']]
     *  - In-list:                ['status' => ['operator' => 'in', 'value' => ['a','b']]]
     *  - Not-in-list:            ['status' => ['operator' => 'not_in', 'value' => ['a','b']]]
     *  - Between:                ['created_at' => ['operator' => 'between', 'value' => ['2023-01-01','2023-12-31']]]
     *  - Like:                   ['name' => ['operator' => 'like', 'value' => '%foo%']]
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model> $query
     * @param  array<string, mixed>                         $filters
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $column => $value) {
            if (is_array($value) && isset($value['operator'])) {
                $query = $this->applyOperatorFilter($query, $column, $value);
            } else {
                $query->where($column, $value);
            }
        }

        return $query;
    }

    /**
     * Apply an operator-based filter to the query.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model> $query
     * @param  array<string, mixed>                         $filter
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    private function applyOperatorFilter(Builder $query, string $column, array $filter): Builder
    {
        $operator = strtolower($filter['operator']);
        $value    = $filter['value'] ?? null;

        return match ($operator) {
            'null'      => $query->whereNull($column),
            'not_null'  => $query->whereNotNull($column),
            'in'        => $query->whereIn($column, (array) $value),
            'not_in'    => $query->whereNotIn($column, (array) $value),
            'between'   => $query->whereBetween($column, (array) $value),
            'like'      => $query->where($column, 'LIKE', $value),
            default     => $query->where($column, $operator, $value),
        };
    }
}
