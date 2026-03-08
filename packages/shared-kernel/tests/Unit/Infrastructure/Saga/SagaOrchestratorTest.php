<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Tests\Unit\Infrastructure\Saga;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Saas\SharedKernel\Infrastructure\Saga\SagaException;
use Saas\SharedKernel\Infrastructure\Saga\SagaOrchestrator;
use Saas\SharedKernel\Infrastructure\Saga\SagaStep;

final class SagaOrchestratorTest extends TestCase
{
    private SagaOrchestrator $orchestrator;

    protected function setUp(): void
    {
        $this->orchestrator = new SagaOrchestrator(new NullLogger());
    }

    public function test_executes_all_steps_successfully(): void
    {
        $executed = [];

        $steps = [
            new SagaStep(
                name:      'step-1',
                execute:   function (array $ctx) use (&$executed): array {
                    $executed[] = 'step-1';
                    return ['step1_done' => true];
                },
                compensate: fn(array $ctx): null => null
            ),
            new SagaStep(
                name:      'step-2',
                execute:   function (array $ctx) use (&$executed): array {
                    $executed[] = 'step-2';
                    return ['step2_done' => true];
                },
                compensate: fn(array $ctx): null => null
            ),
        ];

        $result = $this->orchestrator->run($steps, []);

        $this->assertSame(['step-1', 'step-2'], $executed);
        $this->assertTrue($result['step1_done']);
        $this->assertTrue($result['step2_done']);
    }

    public function test_compensates_on_failure(): void
    {
        $compensated = [];

        $steps = [
            new SagaStep(
                name:      'step-1',
                execute:   fn(array $ctx): array => ['done' => 1],
                compensate: function (array $ctx) use (&$compensated): void {
                    $compensated[] = 'step-1';
                }
            ),
            new SagaStep(
                name:      'step-2',
                execute:   fn(array $ctx): array => ['done' => 2],
                compensate: function (array $ctx) use (&$compensated): void {
                    $compensated[] = 'step-2';
                }
            ),
            new SagaStep(
                name:      'step-3-fails',
                execute:   fn(array $ctx): never => throw new \RuntimeException('Step 3 failed'),
                compensate: function (array $ctx) use (&$compensated): void {
                    $compensated[] = 'step-3'; // Should NOT be called – step-3 did not execute
                }
            ),
        ];

        $this->expectException(SagaException::class);
        $this->expectExceptionMessageMatches('/step-3-fails/');

        try {
            $this->orchestrator->run($steps, []);
        } finally {
            // Compensation runs in REVERSE for executed steps only
            $this->assertSame(['step-2', 'step-1'], $compensated);
        }
    }

    public function test_passes_context_between_steps(): void
    {
        $steps = [
            new SagaStep(
                name:      'produce-value',
                execute:   fn(array $ctx): array => ['value' => 42],
                compensate: fn(array $ctx): null => null
            ),
            new SagaStep(
                name:      'consume-value',
                execute:   function (array $ctx): array {
                    $this->assertSame(42, $ctx['value']);
                    return ['doubled' => $ctx['value'] * 2];
                },
                compensate: fn(array $ctx): null => null
            ),
        ];

        $result = $this->orchestrator->run($steps, []);
        $this->assertSame(84, $result['doubled']);
    }

    public function test_empty_steps_returns_initial_context(): void
    {
        $context = ['initial' => 'data'];
        $result  = $this->orchestrator->run([], $context);
        $this->assertSame($context, $result);
    }
}
