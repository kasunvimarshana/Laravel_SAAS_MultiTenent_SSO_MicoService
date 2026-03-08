<?php

declare(strict_types=1);

namespace Saas\SharedKernel\Providers;

use Illuminate\Support\ServiceProvider;
use Saas\SharedKernel\Application\Pipeline\LaravelPipeline;
use Saas\SharedKernel\Application\Pipeline\PipelineInterface;
use Saas\SharedKernel\Domain\Events\DomainEventDispatcher;
use Saas\SharedKernel\Infrastructure\MessageBroker\MessageBrokerFactory;
use Saas\SharedKernel\Infrastructure\MessageBroker\Contracts\MessageBrokerInterface;
use Saas\SharedKernel\Infrastructure\Saga\SagaOrchestrator;
use Saas\SharedKernel\Infrastructure\Tenant\TenantContext;

/**
 * Registers all shared kernel services into the Laravel IoC container.
 */
final class SharedKernelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Tenant context – request-scoped singleton
        $this->app->singleton(TenantContext::class);

        // Pipeline
        $this->app->bind(PipelineInterface::class, LaravelPipeline::class);

        // Domain event dispatcher
        $this->app->singleton(DomainEventDispatcher::class);

        // Message broker factory
        $this->app->singleton(MessageBrokerFactory::class);

        // Default message broker resolved from config
        $this->app->singleton(MessageBrokerInterface::class, function ($app): MessageBrokerInterface {
            /** @var MessageBrokerFactory $factory */
            $factory = $app->make(MessageBrokerFactory::class);
            $driver  = config('broker.driver', 'sync');
            $config  = config("broker.connections.{$driver}", []);
            return $factory->make($driver, $config);
        });

        // Saga orchestrator
        $this->app->singleton(SagaOrchestrator::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/broker.php' => config_path('broker.php'),
            ], 'saas-config');
        }
    }
}
