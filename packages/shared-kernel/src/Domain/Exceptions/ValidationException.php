<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Domain\Exceptions;

use Throwable;

/**
 * Thrown when domain invariants are violated.
 */
final class ValidationException extends DomainException
{
    /** @var array<string, string[]> */
    private array $errors;

    /**
     * @param array<string, string[]> $errors
     */
    public function __construct(
        array $errors,
        string $message = 'Validation failed.',
        int $code = 422,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    /** @return array<string, string[]> */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
