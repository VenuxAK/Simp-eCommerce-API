<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Throwable;

/**
 * Infrastructure health check — used by Docker, load balancers, and uptime monitors.
 *
 * Returns 200 when all services are reachable, 503 when any critical
 * service (database) is unavailable.
 */
class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
        ];

        $allHealthy = collect($checks)->every(fn (string $status) => $status === 'ok');

        return response()->json([
            'status' => $allHealthy ? 'ok' : 'degraded',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $allHealthy ? 200 : 503);
    }

    private function checkDatabase(): string
    {
        try {
            DB::selectOne('SELECT 1');

            return 'ok';
        } catch (Throwable) {
            return 'error';
        }
    }

    private function checkCache(): string
    {
        try {
            $key = 'health:ping:'.getmypid();
            Cache::put($key, true,  5);
            Cache::forget($key);

            return 'ok';
        } catch (Throwable) {
            return 'error';
        }
    }

    private function checkQueue(): string
    {
        try {
            $connection = config('queue.default');

            // The sync driver is used in testing — it's always operational.
            if ($connection === 'sync') {
                return 'ok';
            }

            Queue::size();

            return 'ok';
        } catch (\Throwable) {
            return 'error';
        }
    }
}
