<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Inventory\Repositories\Contracts\ProductRepositoryInterface;
use App\Domain\Inventory\Repositories\Contracts\CategoryRepositoryInterface;
use App\Domain\Inventory\Repositories\Contracts\WarehouseRepositoryInterface;
use App\Domain\Inventory\Repositories\Contracts\StockMovementRepositoryInterface;
use App\Infrastructure\Repositories\EloquentProductRepository;
use App\Infrastructure\Repositories\EloquentCategoryRepository;
use App\Infrastructure\Repositories\EloquentWarehouseRepository;
use App\Infrastructure\Repositories\EloquentStockMovementRepository;

/**
 * Application service provider – binds interfaces to implementations.
 * Following the Dependency Inversion Principle: depend on abstractions.
 */
final class AppServiceProvider extends ServiceProvider
{
    /**
     * All repository interface → implementation bindings.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        ProductRepositoryInterface::class       => EloquentProductRepository::class,
        CategoryRepositoryInterface::class      => EloquentCategoryRepository::class,
        WarehouseRepositoryInterface::class     => EloquentWarehouseRepository::class,
        StockMovementRepositoryInterface::class => EloquentStockMovementRepository::class,
    ];

    public function register(): void
    {
        // Additional singleton / service bindings go here
    }

    public function boot(): void
    {
        //
    }
}
