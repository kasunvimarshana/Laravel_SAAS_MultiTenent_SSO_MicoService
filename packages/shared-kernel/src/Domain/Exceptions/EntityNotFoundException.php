<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Domain\Exceptions;

use Throwable;

/**
 * Thrown when a requested entity/aggregate cannot be found.
 */
final class EntityNotFoundException extends DomainException
{
    public function __construct(
        string $entityType,
        string|int $id,
        int $code = 404,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            "{$entityType} with id [{$id}] was not found.",
            $code,
            $previous
        );
    }
}
