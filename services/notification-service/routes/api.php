<?php

declare(strict_types=1);

use App\Http\Controllers\HealthController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::prefix('v1')->group(function (): void {
    Route::get('notifications',  [NotificationController::class, 'index']);
    Route::post('notifications', [NotificationController::class, 'send']);
});
