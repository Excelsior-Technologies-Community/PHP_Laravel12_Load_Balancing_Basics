<?php

namespace App\Http\Controllers;

use App\Models\ServerInstance;
use App\Services\LoadBalancerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Session Persistence Controller
 * 
 * Demonstrates sticky session behavior where a client
 * is consistently routed to the same server instance.
 */
class SessionPersistenceController extends Controller
{
    protected LoadBalancerService $loadBalancerService;

    public function __construct(LoadBalancerService $loadBalancerService)
    {
        $this->loadBalancerService = $loadBalancerService;
    }

    /**
     * Test sticky session routing.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testStickySession(Request $request): JsonResponse
    {
        $sessionId = $request->session()->getId();
        $sticky = $request->boolean('sticky', true);
        $algorithm = $request->input('algorithm', 'round_robin');

        if ($sticky) {
            $server = $this->getStickyServer($sessionId, $request->ip(), $algorithm);
            $routingType = 'sticky';
        } else {
            $server = $this->loadBalancerService->selectServer($algorithm, $request->ip());
            $routingType = 'load_balanced';
        }

        if (!$server) {
            return response()->json(['error' => 'No online servers available'], 503);
        }

        return response()->json([
            'session_id' => substr($sessionId, 0, 8) . '...',
            'routed_to' => $server->name,
            'host' => $server->ip_address . ':' . $server->port,
            'routing_type' => $routingType,
            'sticky_enabled' => $sticky,
            'algorithm' => $algorithm,
            'message' => $sticky
                ? "Session locked to {$server->name} - same server for this session"
                : "Load balanced - any server can handle this request",
        ]);
    }

    /**
     * Get sticky server for session.
     *
     * @param  string  $sessionId
     * @param  string  $clientIp
     * @param  string  $algorithm
     * @return \App\Models\ServerInstance|null
     */
    protected function getStickyServer(string $sessionId, string $clientIp, string $algorithm): ?ServerInstance
    {
        $cacheKey = "sticky_session_{$sessionId}";
        $serverId = Cache::get($cacheKey);
        
        $server = $serverId ? ServerInstance::online()->find($serverId) : null;

        if (!$server) {
            $server = $this->loadBalancerService->selectServer($algorithm, $clientIp);
            if ($server) {
                Cache::put($cacheKey, $server->id, 3600); // 1 hour
            }
        }

        return $server;
    }

    /**
     * Clear sticky session mapping.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearStickySession(Request $request): JsonResponse
    {
        $sessionId = $request->session()->getId();
        $cacheKey = "sticky_session_{$sessionId}";
        
        Cache::forget($cacheKey);

        return response()->json([
            'message' => 'Sticky session mapping cleared',
            'session_id' => substr($sessionId, 0, 8) . '...',
        ]);
    }

    /**
     * Simulate multiple requests to demonstrate sticky vs non-sticky behavior.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function simulateMultipleRequests(Request $request): JsonResponse
    {
        $requestCount = $request->input('request_count', 10);
        $sticky = $request->boolean('sticky', true);
        $algorithm = $request->input('algorithm', 'round_robin');
        $sessionId = $request->session()->getId();
        $clientIp = $request->ip();

        $results = [];
        $serverDistribution = [];

        for ($i = 0; $i < $requestCount; $i++) {
            if ($sticky) {
                $server = $this->getStickyServer($sessionId, $clientIp, $algorithm);
            } else {
                $server = $this->loadBalancerService->selectServer($algorithm, $clientIp);
            }

            if ($server) {
                $results[] = [
                    'request_number' => $i + 1,
                    'routed_to' => $server->name,
                ];

                if (!isset($serverDistribution[$server->name])) {
                    $serverDistribution[$server->name] = 0;
                }
                $serverDistribution[$server->name]++;
            }
        }

        // Calculate consistency
        $uniqueServers = count($serverDistribution);
        $consistency = $uniqueServers === 1 ? 100 : round((1 / $uniqueServers) * 100, 2);

        return response()->json([
            'session_id' => substr($sessionId, 0, 8) . '...',
            'sticky_enabled' => $sticky,
            'algorithm' => $algorithm,
            'total_requests' => $requestCount,
            'unique_servers_used' => $uniqueServers,
            'session_consistency' => $consistency . '%',
            'server_distribution' => $serverDistribution,
            'request_results' => $results,
        ]);
    }

    /**
     * Get session persistence statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatistics(): JsonResponse
    {
        $servers = ServerInstance::all();
        $totalStickySessions = 0;

        foreach ($servers as $server) {
            // Count active sticky sessions for this server
            $server->sticky_session_count = 0;
        }

        return response()->json([
            'total_servers' => $servers->count(),
            'online_servers' => $servers->where('is_online', true)->count(),
            'total_sticky_sessions' => $totalStickySessions,
            'servers' => $servers->map(fn ($server) => [
                'name' => $server->name,
                'is_online' => $server->is_online,
                'request_count' => $server->request_count,
            ]),
        ]);
    }

    /**
     * Compare sticky vs non-sticky session distribution.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function compare(Request $request): JsonResponse
    {
        $requestCount = $request->input('request_count', 20);
        $algorithm = $request->input('algorithm', 'round_robin');
        $sessionId = $request->session()->getId();
        $clientIp = $request->ip();

        // Test with sticky sessions
        $stickyResults = [];
        $stickyDistribution = [];
        
        for ($i = 0; $i < $requestCount; $i++) {
            $server = $this->getStickyServer($sessionId, $clientIp, $algorithm);
            if ($server) {
                $stickyResults[] = $server->name;
                $stickyDistribution[$server->name] = ($stickyDistribution[$server->name] ?? 0) + 1;
            }
        }

        // Clear sticky session for fair comparison
        Cache::forget("sticky_session_{$sessionId}");

        // Test without sticky sessions
        $nonStickyResults = [];
        $nonStickyDistribution = [];
        
        for ($i = 0; $i < $requestCount; $i++) {
            $server = $this->loadBalancerService->selectServer($algorithm, $clientIp);
            if ($server) {
                $nonStickyResults[] = $server->name;
                $nonStickyDistribution[$server->name] = ($nonStickyDistribution[$server->name] ?? 0) + 1;
            }
        }

        return response()->json([
            'algorithm' => $algorithm,
            'request_count' => $requestCount,
            'sticky_sessions' => [
                'unique_servers' => count($stickyDistribution),
                'distribution' => $stickyDistribution,
                'consistency' => count($stickyDistribution) === 1 ? '100%' : 'Variable',
            ],
            'non_sticky_sessions' => [
                'unique_servers' => count($nonStickyDistribution),
                'distribution' => $nonStickyDistribution,
                'consistency' => count($nonStickyDistribution) === 1 ? '100%' : 'Load Balanced',
            ],
        ]);
    }

    /**
     * Set sticky session for a specific server.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setStickyServer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'server_id' => 'required|exists:server_instances,id',
        ]);

        $sessionId = $request->session()->getId();
        $cacheKey = "sticky_session_{$sessionId}";
        
        $server = ServerInstance::find($validated['server_id']);
        
        if (!$server || !$server->is_online) {
            return response()->json(['error' => 'Server not found or offline'], 404);
        }

        Cache::put($cacheKey, $server->id, 3600);

        return response()->json([
            'message' => "Sticky session set to {$server->name}",
            'server' => $server->name,
            'session_id' => substr($sessionId, 0, 8) . '...',
        ]);
    }
}
