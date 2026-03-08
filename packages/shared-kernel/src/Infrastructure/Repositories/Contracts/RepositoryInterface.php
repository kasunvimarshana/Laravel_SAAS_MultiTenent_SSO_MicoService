<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Infrastructure\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Generic repository contract.
 * All concrete repositories must implement this interface.
 *
 * @template TModel of Model
 */
interface RepositoryInterface
{
    /**
     * Find a single entity by its primary key.
     *
     * @return TModel|null
     */
    public function findById(int|string $id): ?Model;

    /**
     * Find a single entity by primary key or throw EntityNotFoundException.
     *
     * @return TModel
     */
    public function findOrFail(int|string $id): Model;

    /**
     * Return all entities (or paginated when criteria includes pagination params).
     *
     * @param  array<string, mixed> $criteria
     * @return Collection<int, TModel>|LengthAwarePaginator
     */
    public function findAll(array $criteria = []): Collection|LengthAwarePaginator;

    /**
     * Persist a new or existing entity and return it.
     *
     * @param  array<string, mixed> $attributes
     * @return TModel
     */
    public function create(array $attributes): Model;

    /**
     * Update an entity by primary key.
     *
     * @param  array<string, mixed> $attributes
     * @return TModel
     */
    public function update(int|string $id, array $attributes): Model;

    /**
     * Delete an entity by primary key.
     */
    public function delete(int|string $id): bool;

    /**
     * Check whether an entity with the given primary key exists.
     */
    public function exists(int|string $id): bool;
}
