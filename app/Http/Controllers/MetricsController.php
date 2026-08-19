<?php

namespace App\Http\Controllers;

use App\Models\LoadMetric;
use App\Models\RequestLog;
use App\Models\ServerInstance;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Metrics Controller
 * 
 * Provides load statistics, performance metrics, and
 * analytics for the load balancing system.
 */
class MetricsController extends Controller
{
    /**
     * Display load stats page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $servers = ServerInstance::all();

        $recentLogs = RequestLog::with('server')->latest('created_at')->limit(20)->get();

        $metrics = [
            'total_requests' => RequestLog::count(),
            'avg_response_time_ms' => round(RequestLog::avg('response_time') ?? 0, 1),
            'active_servers' => $servers->where('is_online', true)->count(),
            'servers' => $servers->map(fn($s) => [
                'name' => $s->name,
                'is_online' => $s->is_online,
                'ip_address' => $s->ip_address,
                'port' => $s->port,
                'request_count' => $s->request_count,
                'active_connections' => $s->active_connections,
                'last_heartbeat' => $s->last_heartbeat?->diffForHumans() ?? 'Never',
            ])->toArray(),
        ];

        $logs = $recentLogs->map(fn($log) => [
            'server' => $log->server?->name ?? 'N/A',
            'method' => $log->method,
            'path' => $log->path,
            'ip' => $log->client_ip,
            'response_time' => $log->response_time,
            'status_code' => $log->status_code,
            'time' => $log->created_at?->diffForHumans() ?? '-',
        ])->toArray();

        return view('metrics', compact('metrics', 'logs'));
    }

    /**
     * Get real-time metrics as JSON.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function realtime(): JsonResponse
    {
        $servers = ServerInstance::online()->get();

        return response()->json([
            'timestamp' => now()->toIso8601String(),
            'servers' => $servers->map(fn ($server) => [
                'name' => $server->name,
                'is_online' => $server->is_online,
                'request_count' => $server->request_count,
                'active_connections' => $server->active_connections,
                'last_heartbeat' => $server->last_heartbeat?->toIso8601String(),
            ]),
            'total_servers' => $servers->count(),
        ]);
    }

    /**
     * Get performance comparison between algorithms.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function algorithmComparison(): JsonResponse
    {
        $algorithms = ['round_robin', 'least_connections', 'ip_hash', 'weighted_round_robin'];
        $comparison = [];

        foreach ($algorithms as $algorithm) {
            $metrics = LoadMetric::where('algorithm', $algorithm)->get();
            
            $comparison[$algorithm] = [
                'total_requests' => $metrics->sum('total_requests'),
                'avg_response_time' => $metrics->avg('avg_response_time'),
                'success_rate' => $this->calculateAlgorithmSuccessRate($metrics),
                'peak_load' => $metrics->max('peak_load'),
            ];
        }

        return response()->json([
            'comparison' => $comparison,
            'best_algorithm' => $this->getBestAlgorithm($comparison),
        ]);
    }

    /**
     * Get response time monitoring data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function responseTimeMonitoring(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 100);
        $serverId = $request->input('server_id');

        $query = RequestLog::query();
        
        if ($serverId) {
            $query->where('server_instance_id', $serverId);
        }

        $logs = $query->latest()->limit($limit)->get();

        $responseTimes = $logs->pluck('response_time')->filter();
        $avgResponseTime = $responseTimes->avg();
        $maxResponseTime = $responseTimes->max();
        $minResponseTime = $responseTimes->min();

        return response()->json([
            'average_response_time' => $avgResponseTime,
            'max_response_time' => $maxResponseTime,
            'min_response_time' => $minResponseTime,
            'total_requests' => $logs->count(),
            'recent_logs' => $logs->take(20),
        ]);
    }

    /**
     * Get peak load identification.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function peakLoadIdentification(): JsonResponse
    {
        $servers = ServerInstance::with('loadMetrics')->get();
        $peakLoads = [];

        foreach ($servers as $server) {
            $peakMetric = $server->loadMetrics->sortByDesc('peak_load')->first();
            
            if ($peakMetric) {
                $peakLoads[] = [
                    'server' => $server->name,
                    'peak_load' => $peakMetric->peak_load,
                    'peak_time' => $peakMetric->peak_load_time?->toIso8601String(),
                    'algorithm' => $peakMetric->algorithm,
                ];
            }
        }

        // Sort by peak load descending
        usort($peakLoads, fn ($a, $b) => $b['peak_load'] <=> $a['peak_load']);

        return response()->json([
            'peak_loads' => $peakLoads,
            'overall_peak' => $peakLoads[0] ?? null,
        ]);
    }

    /**
     * Get comprehensive metrics summary.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function summary(): JsonResponse
    {
        $servers = ServerInstance::all();
        $totalRequests = RequestLog::count();
        $successfulRequests = RequestLog::successful()->count();
        $failedRequests = RequestLog::failed()->count();

        return response()->json([
            'servers' => [
                'total' => $servers->count(),
                'online' => $servers->where('is_online', true)->count(),
                'offline' => $servers->where('is_online', false)->count(),
            ],
            'requests' => [
                'total' => $totalRequests,
                'successful' => $successfulRequests,
                'failed' => $failedRequests,
                'success_rate' => $totalRequests > 0 ? round(($successfulRequests / $totalRequests) * 100, 2) : 0,
            ],
            'performance' => [
                'avg_response_time' => RequestLog::avg('response_time'),
                'total_active_connections' => $servers->sum('active_connections'),
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Calculate overall success rate.
     *
     * @return float
     */
    protected function calculateSuccessRate(): float
    {
        $total = RequestLog::count();
        $successful = RequestLog::successful()->count();

        return $total > 0 ? round(($successful / $total) * 100, 2) : 0;
    }

    /**
     * Calculate success rate for specific algorithm metrics.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $metrics
     * @return float
     */
    protected function calculateAlgorithmSuccessRate($metrics): float
    {
        $totalSuccess = $metrics->sum('success_count');
        $totalErrors = $metrics->sum('error_count');
        $total = $totalSuccess + $totalErrors;

        return $total > 0 ? round(($totalSuccess / $total) * 100, 2) : 0;
    }

    /**
     * Get requests per server distribution.
     *
     * @return array
     */
    protected function getRequestsPerServer(): array
    {
        $servers = ServerInstance::withCount('requestLogs')->get();
        
        return $servers->mapWithKeys(fn ($server) => [
            $server->name => $server->request_logs_count,
        ])->toArray();
    }

    /**
     * Determine the best performing algorithm.
     *
     * @param  array  $comparison
     * @return string|null
     */
    protected function getBestAlgorithm(array $comparison): ?string
    {
        $bestAlgorithm = null;
        $bestScore = -1;

        foreach ($comparison as $algorithm => $metrics) {
            // Score based on success rate and response time
            $score = ($metrics['success_rate'] * 0.6) + ((100 - $metrics['avg_response_time']) * 0.4);
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestAlgorithm = $algorithm;
            }
        }

        return $bestAlgorithm;
    }
}
