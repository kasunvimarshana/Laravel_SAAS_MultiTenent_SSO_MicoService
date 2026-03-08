<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Domain\Exceptions;

use RuntimeException;

/**
 * Base domain exception for all business-rule violations.
 */
abstract class DomainException extends RuntimeException {}
