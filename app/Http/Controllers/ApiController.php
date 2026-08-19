<?php

namespace App\Http\Controllers;

use App\Models\LoadMetric;
use App\Models\RequestLog;
use App\Models\ServerInstance;

class ApiController extends Controller
{
    public function servers()
    {
        return response()->json(ServerInstance::all());
    }

    public function metrics()
    {
        $servers = ServerInstance::withCount('requestLogs')->get()->map(function ($server) {
            return [
                'id' => $server->id,
                'name' => $server->name,
                'host' => $server->ip_address . ':' . $server->port,
                'is_online' => $server->is_online,
                'request_count' => $server->request_count,
                'active_connections' => $server->active_connections,
                'last_heartbeat' => $server->last_heartbeat?->diffForHumans(),
            ];
        });

        $metricsData = [
            'servers' => $servers,
            'total_requests' => RequestLog::count(),
            'avg_response_time' => round(RequestLog::avg('response_time'), 2),
            'active_servers' => ServerInstance::online()->count(),
            'timestamp' => now()->toIso8601String(),
        ];

        if (request()->wantsJson()) {
            return response()->json($metricsData);
        }

        $metrics = $metricsData;
        $logs = $this->getLogsData();

        return view('metrics', compact('metrics', 'logs'));
    }

    public function requestLog()
    {
        $logs = $this->getLogsData();
        return response()->json($logs);
    }

    private function getLogsData(): array
    {
        return RequestLog::with('server')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn($log) => [
                'id' => $log->id,
                'server' => $log->server?->name,
                'method' => $log->method,
                'path' => $log->path,
                'ip' => $log->client_ip,
                'response_time' => $log->response_time,
                'status_code' => $log->status_code,
                'time' => $log->created_at->diffForHumans(),
            ])->toArray();
    }
}
