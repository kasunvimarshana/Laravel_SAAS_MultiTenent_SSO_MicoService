<?php

declare(strict_types=1);

use App\Http\Controllers\HealthController;
use App\Http\Controllers\SagaController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::prefix('v1')->group(function (): void {
    Route::get('sagas',               [SagaController::class, 'index']);
    Route::post('sagas',              [SagaController::class, 'start']);
    Route::get('sagas/{id}',          [SagaController::class, 'show']);
    Route::put('sagas/{id}/complete', [SagaController::class, 'complete']);
    Route::put('sagas/{id}/fail',     [SagaController::class, 'fail']);
});
