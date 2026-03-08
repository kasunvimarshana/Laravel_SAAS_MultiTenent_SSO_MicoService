<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Domain\Exceptions;

use Throwable;

/**
 * Thrown when a user lacks permission for a given action.
 */
final class UnauthorizedException extends DomainException
{
    public function __construct(
        string $action = 'perform this action',
        int $code = 403,
        ?Throwable $previous = null
    ) {
        parent::__construct("You are not authorised to {$action}.", $code, $previous);
    }
}
