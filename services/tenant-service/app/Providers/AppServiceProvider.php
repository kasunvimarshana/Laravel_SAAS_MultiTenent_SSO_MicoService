<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Tenant\Repositories\Contracts\TenantRepositoryInterface;
use App\Infrastructure\Repositories\EloquentTenantRepository;

final class AppServiceProvider extends ServiceProvider
{
    public array $bindings = [
        TenantRepositoryInterface::class => EloquentTenantRepository::class,
    ];

    public function register(): void {}
    public function boot(): void {}
}
