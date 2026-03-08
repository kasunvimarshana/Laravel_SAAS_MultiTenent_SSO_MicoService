<?php

declare(strict_types=1);

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Inventory Service API Routes – v1
|--------------------------------------------------------------------------
*/

Route::get('/health', HealthController::class);

Route::prefix('v1')->middleware(['auth:api'])->group(function (): void {

    // ── Products ──────────────────────────────────────────────────────────
    Route::apiResource('products', ProductController::class);
    Route::post('products/{product}/adjust-stock', [ProductController::class, 'adjustStock'])
        ->name('products.adjust-stock');

    // ── Categories ────────────────────────────────────────────────────────
    Route::apiResource('categories', CategoryController::class);

    // ── Warehouses ────────────────────────────────────────────────────────
    Route::apiResource('warehouses', WarehouseController::class);

    // ── Stock Movements ───────────────────────────────────────────────────
    Route::get('stock-movements',            [StockMovementController::class, 'index'])
        ->name('stock-movements.index');
    Route::get('stock-movements/{movement}', [StockMovementController::class, 'show'])
        ->name('stock-movements.show');
    Route::get('products/{product}/stock-movements', [StockMovementController::class, 'byProduct'])
        ->name('products.stock-movements');
});
