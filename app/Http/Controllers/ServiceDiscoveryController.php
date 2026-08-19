<?php

namespace App\Http\Controllers;

use App\Models\ServerInstance;
use App\Services\LoadBalancerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Service Discovery Controller
 * 
 * Handles service registration, heartbeat mechanism,
 * and automatic removal of unhealthy servers.
 */
class ServiceDiscoveryController extends Controller
{
    protected LoadBalancerService $loadBalancerService;

    public function __construct(LoadBalancerService $loadBalancerService)
    {
        $this->loadBalancerService = $loadBalancerService;
    }

    /**
     * Register a new server instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'port' => 'required|integer|min:1|max:65535',
            'hostname' => 'required|string',
            'weight' => 'integer|min:1|max:10',
            'algorithm' => 'string|in:round_robin,least_connections,ip_hash,weighted_round_robin',
        ]);

        $server = ServerInstance::create([
            'name' => $validated['name'],
            'ip_address' => $validated['ip_address'],
            'port' => $validated['port'],
            'hostname' => $validated['hostname'],
            'weight' => $validated['weight'] ?? 1,
            'algorithm' => $validated['algorithm'] ?? 'round_robin',
            'is_online' => true,
            'request_count' => 0,
            'active_connections' => 0,
            'last_heartbeat' => now(),
            'metadata' => [
                'registered_at' => now()->toIso8601String(),
                'region' => $request->input('region', 'default'),
            ],
        ]);

        return response()->json([
            'message' => 'Server registered successfully',
            'server' => $server,
        ], 201);
    }

    /**
     * Send heartbeat for a server instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ServerInstance  $server
     * @return \Illuminate\Http\JsonResponse
     */
    public function heartbeat(Request $request, ServerInstance $server): JsonResponse
    {
        $success = $this->loadBalancerService->updateHeartbeat($server->id);

        if (!$success) {
            return response()->json(['error' => 'Server not found'], 404);
        }

        // Update server status if it was offline
        if (!$server->is_online) {
            $server->update(['is_online' => true]);
        }

        return response()->json([
            'message' => 'Heartbeat received',
            'server' => $server->fresh(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Remove unhealthy servers (service discovery).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeUnhealthy(): JsonResponse
    {
        $removedCount = $this->loadBalancerService->removeUnhealthyServers();

        return response()->json([
            'message' => "Removed {$removedCount} unhealthy servers",
            'removed_count' => $removedCount,
        ]);
    }

    /**
     * Get all registered services.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        $servers = ServerInstance::all();

        $healthyServers = $servers->filter(fn ($server) => $server->isHealthy());
        $unhealthyServers = $servers->filter(fn ($server) => !$server->isHealthy());

        return response()->json([
            'total' => $servers->count(),
            'healthy' => $healthyServers->count(),
            'unhealthy' => $unhealthyServers->count(),
            'servers' => $servers->map(fn ($server) => [
                'id' => $server->id,
                'name' => $server->name,
                'ip_address' => $server->ip_address,
                'port' => $server->port,
                'is_online' => $server->is_online,
                'is_healthy' => $server->isHealthy(),
                'last_heartbeat' => $server->last_heartbeat?->toIso8601String(),
                'request_count' => $server->request_count,
                'active_connections' => $server->active_connections,
            ]),
        ]);
    }

    /**
     * Deregister a server instance.
     *
     * @param  \App\Models\ServerInstance  $server
     * @return \Illuminate\Http\JsonResponse
     */
    public function deregister(ServerInstance $server): JsonResponse
    {
        $serverName = $server->name;
        $server->delete();

        return response()->json([
            'message' => "Server {$serverName} deregistered successfully",
        ]);
    }

    /**
     * Get service discovery statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats(): JsonResponse
    {
        $servers = ServerInstance::all();

        return response()->json([
            'total_servers' => $servers->count(),
            'online_servers' => $servers->where('is_online', true)->count(),
            'offline_servers' => $servers->where('is_online', false)->count(),
            'healthy_servers' => $servers->filter(fn ($server) => $server->isHealthy())->count(),
            'total_requests' => $servers->sum('request_count'),
            'total_active_connections' => $servers->sum('active_connections'),
            'algorithms_in_use' => $servers->pluck('algorithm')->unique()->values(),
        ]);
    }
}
