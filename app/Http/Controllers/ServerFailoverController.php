<?php

namespace App\Http\Controllers;

use App\Models\ServerInstance;
use App\Services\LoadBalancerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Server Failover Controller
 * 
 * Handles server failover simulation and circuit breaker pattern
 * for automatic failure detection and recovery.
 */
class ServerFailoverController extends Controller
{
    protected LoadBalancerService $loadBalancerService;

    public function __construct(LoadBalancerService $loadBalancerService)
    {
        $this->loadBalancerService = $loadBalancerService;
    }

    /**
     * Simulate server failure.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ServerInstance  $server
     * @return \Illuminate\Http\JsonResponse
     */
    public function simulateFailure(Request $request, ServerInstance $server): JsonResponse
    {
        $server->update([
            'is_online' => false,
            'active_connections' => 0,
        ]);

        // Trigger circuit breaker
        $this->triggerCircuitBreaker($server->id);

        return response()->json([
            'message' => "Server {$server->name} failure simulated",
            'server' => $server->fresh(),
            'circuit_breaker_status' => $this->getCircuitBreakerStatus($server->id),
        ]);
    }

    /**
     * Simulate server recovery.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ServerInstance  $server
     * @return \Illuminate\Http\JsonResponse
     */
    public function simulateRecovery(Request $request, ServerInstance $server): JsonResponse
    {
        $server->update([
            'is_online' => true,
            'last_heartbeat' => now(),
        ]);

        // Reset circuit breaker
        $this->resetCircuitBreaker($server->id);

        return response()->json([
            'message' => "Server {$server->name} recovery simulated",
            'server' => $server->fresh(),
            'circuit_breaker_status' => $this->getCircuitBreakerStatus($server->id),
        ]);
    }

    /**
     * Get circuit breaker status for a server.
     *
     * @param  \App\Models\ServerInstance  $server
     * @return \Illuminate\Http\JsonResponse
     */
    public function circuitBreakerStatus(ServerInstance $server): JsonResponse
    {
        return response()->json([
            'server' => $server->name,
            'circuit_breaker' => $this->getCircuitBreakerStatus($server->id),
        ]);
    }

    /**
     * Get all circuit breaker statuses.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function allCircuitBreakerStatuses(): JsonResponse
    {
        $servers = ServerInstance::all();
        $statuses = [];

        foreach ($servers as $server) {
            $statuses[$server->name] = $this->getCircuitBreakerStatus($server->id);
        }

        return response()->json([
            'circuit_breakers' => $statuses,
        ]);
    }

    /**
     * Trigger circuit breaker for a server.
     *
     * @param  int  $serverId
     * @return void
     */
    protected function triggerCircuitBreaker(int $serverId): void
    {
        $cacheKey = "circuit_breaker_{$serverId}";
        
        Cache::put($cacheKey, [
            'state' => 'open',
            'failure_count' => Cache::get("{$cacheKey}_failures", 0) + 1,
            'last_failure' => now()->toIso8601String(),
            'opened_at' => now()->toIso8601String(),
        ], 300); // 5 minutes timeout
    }

    /**
     * Reset circuit breaker for a server.
     *
     * @param  int  $serverId
     * @return void
     */
    protected function resetCircuitBreaker(int $serverId): void
    {
        $cacheKey = "circuit_breaker_{$serverId}";
        
        Cache::put($cacheKey, [
            'state' => 'closed',
            'failure_count' => 0,
            'last_failure' => null,
            'opened_at' => null,
            'reset_at' => now()->toIso8601String(),
        ], 300);
    }

    /**
     * Get circuit breaker status for a server.
     *
     * @param  int  $serverId
     * @return array
     */
    protected function getCircuitBreakerStatus(int $serverId): array
    {
        $cacheKey = "circuit_breaker_{$serverId}";
        $status = Cache::get($cacheKey, [
            'state' => 'closed',
            'failure_count' => 0,
            'last_failure' => null,
            'opened_at' => null,
            'reset_at' => null,
        ]);

        return $status;
    }

    /**
     * Test failover behavior.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testFailover(Request $request): JsonResponse
    {
        $algorithm = $request->input('algorithm', 'round_robin');
        $clientIp = $request->ip();

        // Get current server
        $currentServer = $this->loadBalancerService->selectServer($algorithm, $clientIp);

        if (!$currentServer) {
            return response()->json(['error' => 'No online servers available'], 503);
        }

        // Simulate failure of current server
        $currentServer->update(['is_online' => false]);
        $this->triggerCircuitBreaker($currentServer->id);

        // Get next server (failover)
        $failoverServer = $this->loadBalancerService->selectServer($algorithm, $clientIp);

        // Restore original server for demo
        $currentServer->update(['is_online' => true]);

        return response()->json([
            'original_server' => $currentServer->name,
            'failover_server' => $failoverServer?->name ?? 'No backup available',
            'algorithm' => $algorithm,
            'failover_successful' => $failoverServer !== null,
            'circuit_breaker_triggered' => true,
        ]);
    }

    /**
     * Get failover statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function failoverStats(): JsonResponse
    {
        $servers = ServerInstance::all();
        $onlineServers = $servers->where('is_online', true);
        $offlineServers = $servers->where('is_online', false);

        $circuitBreakersOpen = 0;
        foreach ($servers as $server) {
            $status = $this->getCircuitBreakerStatus($server->id);
            if ($status['state'] === 'open') {
                $circuitBreakersOpen++;
            }
        }

        return response()->json([
            'total_servers' => $servers->count(),
            'online_servers' => $onlineServers->count(),
            'offline_servers' => $offlineServers->count(),
            'circuit_breakers_open' => $circuitBreakersOpen,
            'circuit_breakers_closed' => $servers->count() - $circuitBreakersOpen,
            'failover_capacity' => $onlineServers->count() > 1,
            'can_withstand_failure' => $onlineServers->count() > 1,
        ]);
    }

    /**
     * Configure circuit breaker threshold.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function configureCircuitBreaker(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'failure_threshold' => 'integer|min:1|max:10',
            'timeout_seconds' => 'integer|min:30|max:3600',
        ]);

        Cache::put('circuit_breaker_config', $validated, 3600);

        return response()->json([
            'message' => 'Circuit breaker configuration updated',
            'config' => $validated,
        ]);
    }

    /**
     * Get circuit breaker configuration.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCircuitBreakerConfig(): JsonResponse
    {
        $config = Cache::get('circuit_breaker_config', [
            'failure_threshold' => 3,
            'timeout_seconds' => 300,
        ]);

        return response()->json($config);
    }
}
