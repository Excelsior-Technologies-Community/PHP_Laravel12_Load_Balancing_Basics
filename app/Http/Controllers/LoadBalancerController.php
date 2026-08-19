<?php

namespace App\Http\Controllers;

use App\Models\RequestLog;
use App\Models\ServerInstance;
use App\Services\LoadBalancerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Load Balancer Controller
 * 
 * Handles load balancing demonstrations, server management,
 * and algorithm simulations.
 */
class LoadBalancerController extends Controller
{
    protected LoadBalancerService $loadBalancerService;

    public function __construct(LoadBalancerService $loadBalancerService)
    {
        $this->loadBalancerService = $loadBalancerService;
    }

    /**
     * Display the load balancing demo page.
     *
     * @return \Illuminate\View\View
     */
    public function demo()
    {
        $servers = ServerInstance::all();
        $currentAlgorithm = Cache::get('current_algorithm', 'round_robin');
        $logs = RequestLog::with('server')->latest()->limit(15)->get();
        $algorithms = LoadBalancerService::getAvailableAlgorithms();

        return view('load-balancer', compact('servers', 'currentAlgorithm', 'logs', 'algorithms'));
    }

    /**
     * Simulate a request through the load balancer.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function simulate(Request $request): JsonResponse
    {
        $algorithm = $request->input('algorithm', 'round_robin');
        Cache::put('current_algorithm', $algorithm, 3600);

        $server = $this->loadBalancerService->selectServer($algorithm, $request->ip());

        if (!$server) {
            return response()->json(['error' => 'No online servers available'], 503);
        }

        // Simulate realistic response time based on server load
        $baseTime = rand(20, 100);
        $loadPenalty = $server->active_connections * 2;
        $responseTime = $baseTime + $loadPenalty;

        // Increment active connections during processing
        $server->increment('active_connections');

        // Update request count
        $server->incrementRequestCount();
        $server->updateHeartbeat();

        // Log the request
        RequestLog::create([
            'server_instance_id' => $server->id,
            'method' => 'GET',
            'path' => '/simulated-request',
            'client_ip' => $request->ip(),
            'response_time' => $responseTime,
            'status_code' => 200,
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        // Decrement after response
        $server->decrement('active_connections');

        return response()->json([
            'routed_to' => $server->name,
            'host' => $server->ip_address . ':' . $server->port,
            'algorithm' => $algorithm,
            'response_time_ms' => $responseTime,
            'server_total_requests' => $server->fresh()->request_count,
            'active_connections' => max(0, $server->fresh()->active_connections),
        ]);
    }

    /**
     * Toggle server online/offline status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ServerInstance  $server
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleServer(Request $request, ServerInstance $server): JsonResponse
    {
        $server->update(['is_online' => !$server->is_online]);

        // Reset connections if going offline
        if (!$server->is_online) {
            $server->update(['active_connections' => 0]);
        }

        return response()->json([
            'server' => $server->name,
            'is_online' => $server->is_online,
            'message' => $server->is_online
                ? "{$server->name} is back ONLINE"
                : "{$server->name} taken OFFLINE - failover triggered",
        ]);
    }

    /**
     * Demonstrate sticky session behavior.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function stickySession(Request $request): JsonResponse
    {
        $sessionId = $request->session()->getId();
        $sticky = $request->boolean('sticky', true);

        if ($sticky) {
            $cacheKey = 'sticky_' . $sessionId;
            $serverId = Cache::get($cacheKey);
            $server = $serverId ? ServerInstance::online()->find($serverId) : null;

            if (!$server) {
                $server = ServerInstance::online()->inRandomOrder()->first();
                if ($server) {
                    Cache::put($cacheKey, $server->id, 3600);
                }
            }
        } else {
            $server = $this->loadBalancerService->selectServer('round_robin', $request->ip());
        }

        if (!$server) {
            return response()->json(['error' => 'No online servers'], 503);
        }

        return response()->json([
            'session_id' => substr($sessionId, 0, 8) . '...',
            'routed_to' => $server->name,
            'host' => $server->ip_address . ':' . $server->port,
            'sticky' => $sticky,
            'message' => $sticky
                ? "Session locked to {$server->name} - same server every time"
                : "No sticky - any server can handle request",
        ]);
    }

    /**
     * Get all servers as JSON.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        $servers = ServerInstance::all();
        return response()->json($servers);
    }

    /**
     * Get real-time metrics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function metrics(): JsonResponse
    {
        $servers = ServerInstance::all();

        return response()->json([
            'total_requests' => RequestLog::count(),
            'avg_response_time' => round(RequestLog::avg('response_time') ?? 0, 1),
            'active_servers' => $servers->where('is_online', true)->count(),
        ]);
    }

    /**
     * Get request logs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function requestLogs(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 50);
        $logs = RequestLog::with('server')->latest('created_at')->limit($limit)->get();

        return response()->json($logs->map(fn($log) => [
            'server' => $log->server?->name ?? 'N/A',
            'method' => $log->method,
            'path' => $log->path,
            'ip' => $log->client_ip,
            'response_time' => $log->response_time,
            'status_code' => $log->status_code,
            'time' => $log->created_at?->diffForHumans() ?? '-',
        ]));
    }

    /**
     * Simulate routing through different algorithms.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function simulateRouting(Request $request): JsonResponse
    {
        $algorithm = $request->input('algorithm', 'round_robin');
        $requestCount = $request->input('request_count', 10);
        $clientIp = $request->input('client_ip');

        $result = $this->loadBalancerService->simulateRouting($algorithm, $requestCount, $clientIp);

        return response()->json($result);
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
}
