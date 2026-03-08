<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::connection()->getPdo();
            $dbOk = true;
        } catch (\Exception) {
            $dbOk = false;
        }

        return response()->json([
            'service'   => 'auth-service',
            'status'    => $dbOk ? 'healthy' : 'degraded',
            'checks'    => ['database' => ['status' => $dbOk]],
            'timestamp' => now()->toIso8601String(),
        ], $dbOk ? 200 : 503);
    }
}
