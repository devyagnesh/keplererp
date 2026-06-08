<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Extended health check beyond Laravel /up (SRS ops).
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => false,
            'cache' => false,
        ];

        try {
            DB::connection()->getPdo();
            $checks['database'] = true;
        } catch (\Throwable) {
            $checks['database'] = false;
        }

        try {
            Cache::put('health_probe', 'ok', 10);
            $checks['cache'] = Cache::get('health_probe') === 'ok';
        } catch (\Throwable) {
            $checks['cache'] = false;
        }

        $healthy = $checks['database'] && $checks['cache'];

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }
}
