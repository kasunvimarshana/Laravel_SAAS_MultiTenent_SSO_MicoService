<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Infrastructure\Saga;

use RuntimeException;

/**
 * Thrown when a saga fails and compensation has been triggered.
 */
final class SagaException extends RuntimeException {}
