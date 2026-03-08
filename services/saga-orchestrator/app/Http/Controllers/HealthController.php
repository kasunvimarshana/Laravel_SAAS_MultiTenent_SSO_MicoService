<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try { DB::connection()->getPdo(); $ok = true; }
        catch (\Exception) { $ok = false; }

        return response()->json([
            'service'   => 'saga-orchestrator',
            'status'    => $ok ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
        ], $ok ? 200 : 503);
    }
}
