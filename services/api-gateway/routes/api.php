<?php

declare(strict_types=1);

use App\Http\Controllers\GatewayController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Gateway Routes
|--------------------------------------------------------------------------
| All requests are proxied to the appropriate downstream microservice.
| The gateway handles: authentication, rate limiting, request routing.
*/

Route::get('/health', HealthController::class);

// Proxy all service requests
Route::any('v1/{service}/{path?}', [GatewayController::class, 'proxy'])
    ->where('path', '.*');
