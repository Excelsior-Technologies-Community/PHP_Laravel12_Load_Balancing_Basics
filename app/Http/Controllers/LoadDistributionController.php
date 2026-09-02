<?php

namespace App\Http\Controllers;

use App\Models\RequestLog;
use App\Models\ServerInstance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoadDistributionController extends Controller
{
    /**
     * Dynamic weight management page.
     */
    public function weights()
    {
        $servers = ServerInstance::orderBy('id')->get();

        return view('load-distribution.weights', compact('servers'));
    }

    /**
     * Update server weight.
     */
public function updateWeight(Request $request, ServerInstance $server)
{
    $validated = $request->validate([
        'weight' => ['required', 'integer', 'min:1', 'max:100'],
    ]);

    $server->update([
        'weight' => $validated['weight'],
    ]);

    // Reset Weighted Round Robin position so the new
    // weight configuration starts cleanly.
    cache()->forget('load_balancer.weighted_round_robin_index');

    return response()->json([
        'success' => true,
        'message' => "{$server->name} weight updated successfully.",
        'server' => [
            'id' => $server->id,
            'name' => $server->name,
            'weight' => $server->weight,
        ],
    ]);
}

    /**
     * Get current weight configuration.
     */
    public function weightConfiguration()
    {
        $servers = ServerInstance::orderBy('id')->get();

        $onlineServers = $servers->where('is_online', true);
        $totalWeight = $onlineServers->sum('weight');

        $data = $servers->map(function ($server) use ($totalWeight) {
            $expectedPercentage = 0;

            if ($server->is_online && $totalWeight > 0) {
                $expectedPercentage = round(
                    ($server->weight / $totalWeight) * 100,
                    2
                );
            }

            return [
                'id' => $server->id,
                'name' => $server->name,
                'weight' => $server->weight,
                'is_online' => $server->is_online,
                'expected_percentage' => $expectedPercentage,
            ];
        });

        return response()->json([
            'servers' => $data,
            'total_weight' => $totalWeight,
        ]);
    }

    /**
     * Simulate weighted traffic using the currently configured weights.
     */
    public function simulateWeightedTraffic(Request $request)
    {
        $validated = $request->validate([
            'requests' => ['required', 'integer', 'min:1', 'max:10000'],
        ]);

        $requestCount = $validated['requests'];

        $servers = ServerInstance::online()
            ->orderBy('id')
            ->get();

        if ($servers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No online servers are available.',
            ], 422);
        }

        $totalWeight = $servers->sum('weight');

        if ($totalWeight <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Total server weight must be greater than zero.',
            ], 422);
        }

        /*
         * Build weighted server pool.
         *
         * Example:
         * Server-1 = 3
         * Server-2 = 2
         * Server-3 = 1
         *
         * Pool:
         * [1, 1, 1, 2, 2, 3]
         */
        $weightedPool = [];

        foreach ($servers as $server) {
            for ($i = 0; $i < $server->weight; $i++) {
                $weightedPool[] = $server;
            }
        }

        $distribution = [];

        foreach ($servers as $server) {
            $distribution[$server->id] = 0;
        }

        for ($i = 0; $i < $requestCount; $i++) {
            $selectedServer = $weightedPool[$i % count($weightedPool)];

            $distribution[$selectedServer->id]++;
        }

        $results = $servers->map(function ($server) use (
            $distribution,
            $requestCount,
            $totalWeight
        ) {
            $actualRequests = $distribution[$server->id] ?? 0;

            $expectedPercentage = round(
                ($server->weight / $totalWeight) * 100,
                2
            );

            $actualPercentage = $requestCount > 0
                ? round(($actualRequests / $requestCount) * 100, 2)
                : 0;

            $deviation = round(
                abs($actualPercentage - $expectedPercentage),
                2
            );

            return [
                'id' => $server->id,
                'name' => $server->name,
                'weight' => $server->weight,
                'requests' => $actualRequests,
                'expected_percentage' => $expectedPercentage,
                'actual_percentage' => $actualPercentage,
                'deviation' => $deviation,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'total_requests' => $requestCount,
            'total_weight' => $totalWeight,
            'servers' => $results,
        ]);
    }

    /**
     * Load distribution analyzer page.
     */
    public function analyzer()
    {
        $servers = ServerInstance::orderBy('id')->get();

        return view('load-distribution.analyzer', compact('servers'));
    }

    /**
     * Analyze current request distribution.
     */
    public function analyze(Request $request)
    {
        $days = (int) $request->input('days', 1);

        $days = max(1, min($days, 30));

        $servers = ServerInstance::orderBy('id')->get();

        $from = now()->subDays($days);

        $requestCounts = RequestLog::query()
            ->where('created_at', '>=', $from)
            ->select('server_instance_id', DB::raw('COUNT(*) as total'))
            ->groupBy('server_instance_id')
            ->pluck('total', 'server_instance_id');

        $responseTimes = RequestLog::query()
            ->where('created_at', '>=', $from)
            ->select(
                'server_instance_id',
                DB::raw('AVG(response_time) as avg_response_time')
            )
            ->groupBy('server_instance_id')
            ->pluck('avg_response_time', 'server_instance_id');

        $totalRequests = $requestCounts->sum();

        $onlineServers = $servers->where('is_online', true);
        $totalWeight = $onlineServers->sum('weight');

        $distribution = $servers->map(function ($server) use (
            $requestCounts,
            $responseTimes,
            $totalRequests,
            $totalWeight
        ) {
            $requests = (int) ($requestCounts[$server->id] ?? 0);

            $actualPercentage = $totalRequests > 0
                ? round(($requests / $totalRequests) * 100, 2)
                : 0;

            $expectedPercentage = 0;

            if ($server->is_online && $totalWeight > 0) {
                $expectedPercentage = round(
                    ($server->weight / $totalWeight) * 100,
                    2
                );
            }

            $deviation = round(
                abs($actualPercentage - $expectedPercentage),
                2
            );

            return [
                'id' => $server->id,
                'name' => $server->name,
                'weight' => $server->weight,
                'is_online' => $server->is_online,
                'requests' => $requests,
                'actual_percentage' => $actualPercentage,
                'expected_percentage' => $expectedPercentage,
                'deviation' => $deviation,
                'active_connections' => $server->active_connections,
                'avg_response_time' => round(
                    (float) ($responseTimes[$server->id] ?? 0),
                    2
                ),
            ];
        })->values();

        /*
         * Calculate weighted distribution accuracy.
         *
         * Every server's deviation is compared with the expected
         * percentage. Lower deviation = better balance.
         */
        $averageDeviation = $distribution->count()
            ? round($distribution->avg('deviation'), 2)
            : 0;

        /*
         * Maximum score = 100.
         *
         * A 0% average deviation gives 100.
         * Deviation >= 100 gives 0.
         */
        $balanceScore = max(
            0,
            round(100 - $averageDeviation, 2)
        );

        if ($balanceScore >= 90) {
            $balanceStatus = 'Excellent';
        } elseif ($balanceScore >= 75) {
            $balanceStatus = 'Well Balanced';
        } elseif ($balanceScore >= 60) {
            $balanceStatus = 'Moderate Imbalance';
        } else {
            $balanceStatus = 'Highly Imbalanced';
        }

        $onlineDistribution = $distribution
            ->where('is_online', true)
            ->sortByDesc('actual_percentage')
            ->values();

        $mostUtilized = $onlineDistribution->first();
        $leastUtilized = $onlineDistribution->last();

        $warnings = [];

        foreach ($distribution as $server) {
            if (!$server['is_online']) {
                $warnings[] = "{$server['name']} is currently offline.";
                continue;
            }

            if ($server['deviation'] >= 15) {
                $warnings[] =
                    "{$server['name']} has a {$server['deviation']}% traffic deviation.";
            }
        }

        if ($distribution->where('is_online', true)->count() === 0) {
            $warnings[] = 'No online servers are available.';
        }

        return response()->json([
            'success' => true,
            'period_days' => $days,
            'from' => $from->toDateTimeString(),
            'to' => now()->toDateTimeString(),

            'summary' => [
                'total_requests' => $totalRequests,
                'online_servers' => $onlineServers->count(),
                'offline_servers' => $servers->count() - $onlineServers->count(),
                'average_deviation' => $averageDeviation,
                'balance_score' => $balanceScore,
                'balance_status' => $balanceStatus,
            ],

            'most_utilized' => $mostUtilized,
            'least_utilized' => $leastUtilized,

            'warnings' => $warnings,

            'servers' => $distribution,
        ]);
    }
}