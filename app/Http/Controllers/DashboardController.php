<?php

namespace App\Http\Controllers;

use App\Models\RequestLog;
use App\Models\ServerInstance;
use App\Models\ServerPerformanceHistory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    /**
     * Dashboard
     *
     * Search + filter + pagination.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status', 'all');

        $serversQuery = ServerInstance::withCount('requestLogs');

        /*
        |--------------------------------------------------------------------------
        | Server Search
        |--------------------------------------------------------------------------
        */

        if ($search) {
            $serversQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('hostname', 'like', "%{$search}%")
                    ->orWhere('port', 'like', "%{$search}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Server Status Filter
        |--------------------------------------------------------------------------
        */

        if ($status === 'online') {
            $serversQuery->where('is_online', true);
        }

        if ($status === 'offline') {
            $serversQuery->where('is_online', false);
        }

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $servers = $serversQuery
            ->oldest()
            ->paginate(5)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Dashboard Statistics
        |--------------------------------------------------------------------------
        */

        $recentLogs = RequestLog::with('server')
            ->oldest()
            ->limit(5)
            ->get();

        $totalRequests = RequestLog::count();

        $avgResponseTime = RequestLog::avg('response_time') ?? 0;

        $currentAlgorithm = Cache::get(
            'current_algorithm',
            'round_robin'
        );

        $onlineServers = ServerInstance::where(
            'is_online',
            true
        )->count();

        $offlineServers = ServerInstance::where(
            'is_online',
            false
        )->count();

        $totalServers = ServerInstance::count();

        /*
        |--------------------------------------------------------------------------
        | Server Analytics
        |--------------------------------------------------------------------------
        */

        $serverAnalytics = ServerInstance::withCount('requestLogs')
            ->orderByDesc('request_count')
            ->get();

        return view('dashboard', compact(
            'servers',
            'recentLogs',
            'totalRequests',
            'avgResponseTime',
            'currentAlgorithm',
            'onlineServers',
            'offlineServers',
            'totalServers',
            'serverAnalytics',
            'search',
            'status'
        ));
    }

    /**
     * Store new server.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'port' => 'required|integer|min:1|max:65535',
            'hostname' => 'required|string|max:255',
            'weight' => 'nullable|integer|min:1|max:10',
            'algorithm' => 'nullable|string|in:round_robin,least_connections,ip_hash,weighted_round_robin',
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
                'registered_from' => 'dashboard',
                'registered_at' => now()->toIso8601String(),
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Server added successfully.',
            'server' => $server,
        ], 201);
    }

    /**
     * Delete / deregister server.
     */
    public function destroy(ServerInstance $server): JsonResponse
    {
        $serverName = $server->name;

        $server->delete();

        return response()->json([
            'success' => true,
            'message' => "{$serverName} deregistered successfully."
        ]);
    }

    /**
     * Toggle server.
     */
    public function toggle(ServerInstance $server): JsonResponse
    {
        $server->update([
            'is_online' => !$server->is_online,
        ]);

        if (!$server->is_online) {
            $server->update([
                'active_connections' => 0,
            ]);
        }

        return response()->json([
            'success' => true,
            'server' => $server->fresh(),
        ]);
    }

    /**
     * Server performance history.
     */
    public function performance(
        ServerInstance $server
    ): JsonResponse {

        $history = ServerPerformanceHistory::where(
            'server_instance_id',
            $server->id
        )
        ->latest('recorded_at')
        ->limit(30)
        ->get()
        ->reverse()
        ->values();

        return response()->json([
            'server' => [
                'id' => $server->id,
                'name' => $server->name,
            ],
            'history' => $history->map(function ($item) {
                return [
                    'time' => $item->recorded_at?->format('H:i:s'),
                    'requests' => $item->request_count,
                    'connections' => $item->active_connections,
                    'response_time' => (float) $item->avg_response_time,
                    'success_rate' => $item->success_rate,
                ];
            }),
        ]);
    }

    /**
     * Server-wise analytics.
     */
    public function analytics(): JsonResponse
    {
        $servers = ServerInstance::withCount('requestLogs')
            ->get();

        $data = $servers->map(function ($server) {

            $logs = RequestLog::where(
                'server_instance_id',
                $server->id
            );

            $total = $logs->count();

            $successful = (clone $logs)
                ->where('status_code', '<', 400)
                ->count();

            $avgResponse = (clone $logs)
                ->avg('response_time');

            return [
                'id' => $server->id,
                'name' => $server->name,
                'port' => $server->port,
                'requests' => $total,
                'successful' => $successful,
                'failed' => $total - $successful,
                'avg_response_time' => round(
                    $avgResponse ?? 0,
                    2
                ),
                'success_rate' => $total > 0
                    ? round(($successful / $total) * 100, 2)
                    : 0,
            ];
        });

        return response()->json([
            'analytics' => $data,
        ]);
    }

    /**
     * Export request logs CSV.
     */
    public function exportLogs(): StreamedResponse
    {
        $fileName =
            'request-logs-' .
            now()->format('Y-m-d-H-i-s') .
            '.csv';

        $logs = RequestLog::with('server')
            ->latest('created_at')
            ->get();

        return response()->streamDownload(
            function () use ($logs) {

                $handle = fopen('php://output', 'w');

                fputcsv($handle, [
                    'ID',
                    'Server',
                    'IP Address',
                    'Method',
                    'Path',
                    'Response Time (ms)',
                    'Status Code',
                    'User Agent',
                    'Created At',
                ]);

                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->id,
                        $log->server?->name ?? 'N/A',
                        $log->client_ip,
                        $log->method,
                        $log->path,
                        $log->response_time,
                        $log->status_code,
                        $log->user_agent,
                        $log->created_at,
                    ]);
                }

                fclose($handle);
            },
            $fileName,
            [
                'Content-Type' => 'text/csv',
            ]
        );
    }

    /**
     * AJAX dashboard data.
     */
    public function realtime(): JsonResponse
    {
        $servers = ServerInstance::withCount('requestLogs')
            ->latest()
            ->get();

        $logs = RequestLog::with('server')
            ->latest('created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'stats' => [
                'total_requests' => RequestLog::count(),

                'avg_response_time' => round(
                    RequestLog::avg('response_time') ?? 0,
                    1
                ),

                'online_servers' =>
                    ServerInstance::where(
                        'is_online',
                        true
                    )->count(),

                'total_servers' =>
                    ServerInstance::count(),
            ],

            'servers' => $servers->map(function ($server) {
                return [
                    'id' => $server->id,
                    'name' => $server->name,
                    'port' => $server->port,
                    'is_online' => $server->is_online,
                    'request_count' => $server->request_count,
                    'active_connections' =>
                        $server->active_connections,
                    'weight' => $server->weight,
                ];
            }),

            'logs' => $logs->map(function ($log) {
                return [
                    'server' =>
                        $log->server?->name ?? 'N/A',
                    'path' => $log->path,
                    'response_time' =>
                        $log->response_time,
                    'status_code' =>
                        $log->status_code,
                    'time' =>
                        $log->created_at?->diffForHumans(),
                ];
            }),
        ]);
    }
}
