<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Order\Repositories\Contracts\OrderRepositoryInterface;
use App\Infrastructure\Repositories\EloquentOrderRepository;

final class AppServiceProvider extends ServiceProvider
{
    public array $bindings = [
        OrderRepositoryInterface::class => EloquentOrderRepository::class,
    ];

    public function register(): void {}
    public function boot(): void {}
}
