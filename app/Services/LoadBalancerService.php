<?php

namespace App\Services;

use App\Models\ServerInstance;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Load Balancer Service
 * 
 * Implements various load balancing algorithms to distribute
 * incoming requests across multiple server instances.
 */
class LoadBalancerService
{
    /**
     * Available load balancing algorithms.
     */
    const ALGORITHM_ROUND_ROBIN = 'round_robin';
    const ALGORITHM_LEAST_CONNECTIONS = 'least_connections';
    const ALGORITHM_IP_HASH = 'ip_hash';
    const ALGORITHM_WEIGHTED_ROUND_ROBIN = 'weighted_round_robin';

    /**
     * Select a server instance based on the specified algorithm.
     *
     * @param  string  $algorithm
     * @param  string|null  $clientIp
     * @return \App\Models\ServerInstance|null
     */
    public function selectServer(string $algorithm = self::ALGORITHM_ROUND_ROBIN, ?string $clientIp = null): ?ServerInstance
    {
        $servers = ServerInstance::online()->get();

        if ($servers->isEmpty()) {
            Log::warning('No online servers available for load balancing');
            return null;
        }

        return match ($algorithm) {
            self::ALGORITHM_ROUND_ROBIN => $this->roundRobin($servers),
            self::ALGORITHM_LEAST_CONNECTIONS => $this->leastConnections($servers),
            self::ALGORITHM_IP_HASH => $this->ipHash($servers, $clientIp),
            self::ALGORITHM_WEIGHTED_ROUND_ROBIN => $this->weightedRoundRobin($servers),
            default => $this->roundRobin($servers),
        };
    }

    /**
     * Round Robin algorithm - distributes requests sequentially.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $servers
     * @return \App\Models\ServerInstance|null
     */
    protected function roundRobin($servers): ?ServerInstance
    {
        $currentIndex = Cache::get('load_balancer.round_robin_index', 0);
        $server = $servers[$currentIndex % $servers->count()];
        
        Cache::put('load_balancer.round_robin_index', $currentIndex + 1);
        
        return $server;
    }

    /**
     * Least Connections algorithm - selects server with fewest active connections.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $servers
     * @return \App\Models\ServerInstance|null
     */
    protected function leastConnections($servers): ?ServerInstance
    {
        return $servers->sortBy('active_connections')->first();
    }

    /**
     * IP Hash algorithm - selects server based on client IP hash.
     * Provides sticky sessions for the same client IP.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $servers
     * @param  string|null  $clientIp
     * @return \App\Models\ServerInstance|null
     */
    protected function ipHash($servers, ?string $clientIp): ?ServerInstance
    {
        if (!$clientIp) {
            return $this->roundRobin($servers);
        }

        $hash = crc32($clientIp);
        $index = abs($hash) % $servers->count();
        
        return $servers[$index];
    }

    /**
     * Weighted Round Robin algorithm - distributes requests based on server weights.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $servers
     * @return \App\Models\ServerInstance|null
     */
    protected function weightedRoundRobin($servers): ?ServerInstance
    {
        $weightedServers = [];
        
        foreach ($servers as $server) {
            $weight = max(1, $server->weight);
            for ($i = 0; $i < $weight; $i++) {
                $weightedServers[] = $server;
            }
        }

        if (empty($weightedServers)) {
            return $servers->first();
        }

        $currentIndex = Cache::get('load_balancer.weighted_round_robin_index', 0);
        $server = $weightedServers[$currentIndex % count($weightedServers)];
        
        Cache::put('load_balancer.weighted_round_robin_index', $currentIndex + 1);
        
        return $server;
    }

    /**
     * Get all available algorithms.
     *
     * @return array
     */
    public static function getAvailableAlgorithms(): array
    {
        return [
            self::ALGORITHM_ROUND_ROBIN => 'Round Robin',
            self::ALGORITHM_LEAST_CONNECTIONS => 'Least Connections',
            self::ALGORITHM_IP_HASH => 'IP Hash (Sticky Sessions)',
            self::ALGORITHM_WEIGHTED_ROUND_ROBIN => 'Weighted Round Robin',
        ];
    }

    /**
     * Simulate request routing through the specified algorithm.
     *
     * @param  string  $algorithm
     * @param  int  $requestCount
     * @param  string|null  $clientIp
     * @return array
     */
    public function simulateRouting(string $algorithm, int $requestCount = 10, ?string $clientIp = null): array
    {
        $distribution = [];
        $servers = ServerInstance::online()->get();
        
        foreach ($servers as $server) {
            $distribution[$server->name] = 0;
        }

        for ($i = 0; $i < $requestCount; $i++) {
            $server = $this->selectServer($algorithm, $clientIp);
            if ($server) {
                $distribution[$server->name]++;
            }
        }

        return [
            'algorithm' => $algorithm,
            'total_requests' => $requestCount,
            'distribution' => $distribution,
            'percentage' => $this->calculatePercentage($distribution, $requestCount),
        ];
    }

    /**
     * Calculate percentage distribution.
     *
     * @param  array  $distribution
     * @param  int  $total
     * @return array
     */
    protected function calculatePercentage(array $distribution, int $total): array
    {
        $percentage = [];
        
        foreach ($distribution as $server => $count) {
            $percentage[$server] = $total > 0 ? round(($count / $total) * 100, 2) : 0;
        }
        
        return $percentage;
    }

    /**
     * Update server heartbeat and check health.
     *
     * @param  int  $serverId
     * @return bool
     */
    public function updateHeartbeat(int $serverId): bool
    {
        $server = ServerInstance::find($serverId);
        
        if (!$server) {
            return false;
        }

        $server->updateHeartbeat();
        
        return true;
    }

    /**
     * Remove unhealthy servers (service discovery).
     *
     * @return int
     */
    public function removeUnhealthyServers(): int
    {
        $unhealthyServers = ServerInstance::online()
            ->where('last_heartbeat', '<', now()->subSeconds(30))
            ->get();

        $count = 0;
        
        foreach ($unhealthyServers as $server) {
            $server->update(['is_online' => false]);
            $count++;
            Log::info("Server {$server->name} marked as unhealthy");
        }

        return $count;
    }
}
