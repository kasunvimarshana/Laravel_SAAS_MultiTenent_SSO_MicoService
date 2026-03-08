<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Application\Pipeline;

use Illuminate\Pipeline\Pipeline;

/**
 * Thin adapter that wraps Laravel's built-in Pipeline
 * behind the PipelineInterface contract.
 */
final class LaravelPipeline implements PipelineInterface
{
    private readonly Pipeline $pipeline;

    public function __construct(\Illuminate\Contracts\Container\Container $container)
    {
        $this->pipeline = new Pipeline($container);
    }

    /** {@inheritdoc} */
    public function send(mixed $payload): static
    {
        $this->pipeline->send($payload);
        return $this;
    }

    /** {@inheritdoc} */
    public function through(array $pipes): static
    {
        $this->pipeline->through($pipes);
        return $this;
    }

    /** {@inheritdoc} */
    public function then(callable $destination): mixed
    {
        return $this->pipeline->then($destination);
    }

    /** {@inheritdoc} */
    public function thenReturn(): mixed
    {
        return $this->pipeline->thenReturn();
    }
}
