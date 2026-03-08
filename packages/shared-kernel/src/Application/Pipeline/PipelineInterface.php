<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Application\Pipeline;

/**
 * A pipeline through which a payload passes through a series of stages (pipes).
 */
interface PipelineInterface
{
    /**
     * Set the payload to be sent through the pipeline.
     *
     * @param  mixed $payload
     * @return static
     */
    public function send(mixed $payload): static;

    /**
     * Set the array of stages (pipes).
     *
     * @param  array<int, class-string|callable> $pipes
     * @return static
     */
    public function through(array $pipes): static;

    /**
     * Execute the pipeline and pass the result to the final callback.
     *
     * @param  callable $destination
     * @return mixed
     */
    public function then(callable $destination): mixed;

    /**
     * Execute the pipeline and return the result.
     *
     * @return mixed
     */
    public function thenReturn(): mixed;
}
