<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Infrastructure\Saga;

use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Saga Orchestrator – executes a sequence of SagaSteps and performs
 * compensating transactions in reverse order if any step fails.
 *
 * Guarantees:
 *  - Steps are executed sequentially
 *  - On failure, all successfully-executed steps are compensated in reverse order
 *  - Full audit log via PSR-3 logger
 */
final class SagaOrchestrator
{
    public function __construct(private readonly LoggerInterface $logger) {}

    /**
     * Execute the saga.
     *
     * @param  SagaStep[]           $steps          Ordered list of saga steps
     * @param  array<string, mixed> $initialContext Data shared across steps
     * @return array<string, mixed>                 Final context after all steps
     *
     * @throws SagaException If a step fails and compensation is triggered
     */
    public function run(array $steps, array $initialContext = []): array
    {
        $context  = $initialContext;
        $executed = [];

        foreach ($steps as $step) {
            try {
                $this->logger->info('[Saga] Executing step', ['step' => $step->getName()]);
                $context    = $step->execute($context);
                $executed[] = $step;
                $this->logger->info('[Saga] Step succeeded', ['step' => $step->getName()]);
            } catch (Throwable $e) {
                $this->logger->error('[Saga] Step failed, starting compensation', [
                    'step'  => $step->getName(),
                    'error' => $e->getMessage(),
                ]);

                $this->compensate($executed, $context);

                throw new SagaException(
                    "Saga failed at step [{$step->getName()}]: {$e->getMessage()}",
                    $e->getCode(),
                    $e
                );
            }
        }

        $this->logger->info('[Saga] All steps completed successfully');

        return $context;
    }

    /**
     * Run compensating transactions in reverse order.
     *
     * @param  SagaStep[]           $executedSteps
     * @param  array<string, mixed> $context
     */
    private function compensate(array $executedSteps, array $context): void
    {
        foreach (array_reverse($executedSteps) as $step) {
            try {
                $this->logger->info('[Saga] Compensating step', ['step' => $step->getName()]);
                $step->compensate($context);
                $this->logger->info('[Saga] Compensation succeeded', ['step' => $step->getName()]);
            } catch (Throwable $e) {
                // Log but continue compensating remaining steps
                $this->logger->critical('[Saga] Compensation failed – manual intervention required', [
                    'step'  => $step->getName(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
