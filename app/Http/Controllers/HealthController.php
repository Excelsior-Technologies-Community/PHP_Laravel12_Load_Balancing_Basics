<?php

namespace App\Http\Controllers;

use App\Models\ServerInstance;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function index()
    {
        $uptime = time() - (int) Cache::remember('app_start_time', 86400, fn() => time());
        $memUsage = memory_get_usage(true);
        $memPeak = memory_get_peak_usage(true);

        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'uptime_seconds' => $uptime,
            'memory' => [
                'used' => $this->formatBytes($memUsage),
                'peak' => $this->formatBytes($memPeak),
                'used_bytes' => $memUsage,
            ],
            'server' => [
                'hostname' => gethostname(),
                'ip' => request()->server('SERVER_ADDR', '127.0.0.1'),
                'port' => request()->server('SERVER_PORT', 8000),
                'php_version' => PHP_VERSION,
            ],
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
        ];

        if (request()->wantsJson()) {
            return response()->json($health);
        }

        $live = ['status' => 'alive', 'timestamp' => now()->toIso8601String()];
        $dbOk = $health['database']['status'] === 'ok';
        $ready = [
            'status' => $dbOk ? 'ready' : 'not_ready',
            'checks' => ['database' => $health['database'], 'cache' => $health['cache']],
        ];

        return view('health', compact('health', 'live', 'ready'));
    }

    public function live()
    {
        return response()->json([
            'status' => 'alive',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function ready()
    {
        $dbOk = $this->checkDatabase()['status'] === 'ok';
        $status = $dbOk ? 'ready' : 'not_ready';
        $code = $dbOk ? 200 : 503;

        return response()->json([
            'status' => $status,
            'checks' => [
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
            ],
            'timestamp' => now()->toIso8601String(),
        ], $code);
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $serverCount = ServerInstance::count();
            return ['status' => 'ok', 'server_instances' => $serverCount];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        try {
            Cache::put('health_check', true, 5);
            $ok = Cache::get('health_check') === true;
            return ['status' => $ok ? 'ok' : 'error'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function formatBytes(int $bytes): string
    {
        return round($bytes / 1024 / 1024, 2) . ' MB';
    }
}
