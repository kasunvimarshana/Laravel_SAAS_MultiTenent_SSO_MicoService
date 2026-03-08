<?php

declare(strict_types=1);

use App\Http\Controllers\HealthController;
use App\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::prefix('v1')->middleware(['auth:api'])->group(function (): void {
    Route::apiResource('tenants', TenantController::class);
    Route::put('tenants/{tenant}/config', [TenantController::class, 'updateConfig'])
        ->name('tenants.config');
});
