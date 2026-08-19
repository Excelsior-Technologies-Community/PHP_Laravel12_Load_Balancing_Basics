<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Health Check Controller
 * 
 * Provides health check endpoints for monitoring server status,
 * liveness probes, and readiness probes.
 */
class HealthCheckController extends Controller
{
    /**
     * Display health check page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $db = $this->checkDatabase();
        $mem = $this->getMemoryUsage();

        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'uptime_seconds' => $this->getUptime(),
            'memory' => ['used' => $mem['current'], 'peak' => $mem['peak']],
            'server' => [
                'hostname' => gethostname() ?: 'localhost',
                'ip' => request()->server('SERVER_ADDR', '127.0.0.1'),
                'port' => request()->server('SERVER_PORT', '8000'),
                'php_version' => PHP_VERSION,
            ],
            'database' => [
                'status' => $db['status'] === 'connected' ? 'ok' : 'error',
                'server_instances' => $db['status'] === 'connected' ? \App\Models\ServerInstance::count() : 0,
            ],
            'cache' => ['status' => $this->checkCache()],
        ];

        $live = ['status' => 'alive', 'timestamp' => now()->toIso8601String()];

        $isReady = $db['status'] === 'connected';
        $ready = [
            'status' => $isReady ? 'ready' : 'not_ready',
            'timestamp' => now()->toIso8601String(),
            'checks' => [
                'database' => ['status' => $db['status'] === 'connected' ? 'ok' : 'error'],
                'cache' => ['status' => $this->checkCache()],
            ],
        ];

        return view('health', compact('health', 'live', 'ready'));
    }

    /**
     * Liveness probe - checks if the application is running.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function live(): JsonResponse
    {
        return response()->json([
            'status' => 'alive',
            'timestamp' => now()->toIso8601String(),
        ])->setStatusCode(200);
    }

    /**
     * Readiness probe - checks if the application is ready to accept traffic.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function ready(): JsonResponse
    {
        $isReady = $this->checkDatabase()['status'] === 'connected';

        return response()->json([
            'status' => $isReady ? 'ready' : 'not_ready',
            'timestamp' => now()->toIso8601String(),
            'checks' => [
                'database' => $this->checkDatabase(),
            ],
        ])->setStatusCode($isReady ? 200 : 503);
    }

    /**
     * Get server uptime in seconds.
     *
     * @return int
     */
    protected function getUptime(): int
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return (int) ($load[0] * 100);
        }
        
        return 0;
    }

    /**
     * Get current memory usage.
     *
     * @return array
     */
    protected function getMemoryUsage(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        
        return [
            'current' => $this->formatBytes($memoryUsage),
            'peak' => $this->formatBytes(memory_get_peak_usage(true)),
            'limit' => $memoryLimit,
        ];
    }

    /**
     * Check database connection status.
     *
     * @return array
     */
    protected function checkDatabase(): array
    {
        try {
            \DB::connection()->getPdo();
            return [
                'status' => 'connected',
                'connection' => config('database.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'disconnected',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache connection.
     *
     * @return string
     */
    protected function checkCache(): string
    {
        try {
            \Cache::put('health_check', true, 5);
            return \Cache::get('health_check') ? 'ok' : 'error';
        } catch (\Exception $e) {
            return 'error';
        }
    }

    /**
     * Format bytes to human readable format.
     *
     * @param  int  $bytes
     * @return string
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
