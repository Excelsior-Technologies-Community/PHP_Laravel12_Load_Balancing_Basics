@extends('layouts.app')
@section('title', 'Server Dashboard')

@section('content')
<div class="max-w-7xl mx-auto">
    <h1 class="text-2xl font-bold mb-6 text-orange-400">📊 Server Dashboard</h1>

    {{-- Stats Row --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-gray-900 rounded-lg p-4 border border-gray-800">
            <p class="text-gray-400 text-xs">Total Requests</p>
            <p class="text-2xl font-bold text-white">{{ number_format($totalRequests) }}</p>
        </div>
        <div class="bg-gray-900 rounded-lg p-4 border border-gray-800">
            <p class="text-gray-400 text-xs">Avg Response Time</p>
            <p class="text-2xl font-bold text-green-400">{{ round($avgResponseTime, 1) }}ms</p>
        </div>
        <div class="bg-gray-900 rounded-lg p-4 border border-gray-800">
            <p class="text-gray-400 text-xs">Active Servers</p>
            <p class="text-2xl font-bold text-blue-400">{{ $servers->where('is_online', true)->count() }}/{{ $servers->count() }}</p>
        </div>
        <div class="bg-gray-900 rounded-lg p-4 border border-gray-800">
            <p class="text-gray-400 text-xs">Algorithm</p>
            <p class="text-lg font-bold text-orange-400">{{ str_replace('_', ' ', $currentAlgorithm) }}</p>
        </div>
    </div>

    {{-- Server Instances --}}
    <h2 class="text-lg font-semibold mb-3 text-gray-300">Server Instances</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @foreach($servers as $server)
        <div class="bg-gray-900 rounded-lg p-4 border {{ $server->is_online ? 'border-green-700' : 'border-red-800' }}">
            <div class="flex justify-between items-start mb-2">
                <span class="font-semibold">{{ $server->name }}</span>
                <span class="text-xs px-2 py-0.5 rounded-full {{ $server->is_online ? 'bg-green-900 text-green-300' : 'bg-red-900 text-red-300' }}">
                    {{ $server->is_online ? 'ONLINE' : 'OFFLINE' }}
                </span>
            </div>
            <p class="text-xs text-gray-400 mb-3">{{ $server->ip_address }}:{{ $server->port }}</p>
            <div class="space-y-1 text-xs">
                <div class="flex justify-between">
                    <span class="text-gray-400">Total Requests</span>
                    <span class="text-white">{{ number_format($server->request_count) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Active Connections</span>
                    <span class="text-yellow-400">{{ $server->active_connections }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Weight</span>
                    <span class="text-blue-400">{{ $server->weight }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Last Heartbeat</span>
                    <span class="text-gray-300">{{ $server->last_heartbeat?->diffForHumans() ?? 'Never' }}</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Recent Request Logs --}}
    <h2 class="text-lg font-semibold mb-3 text-gray-300">Recent Requests</h2>
    <div class="bg-gray-900 rounded-lg border border-gray-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-800 text-gray-400 text-xs">
                <tr>
                    <th class="px-4 py-2 text-left">Server</th>
                    <th class="px-4 py-2 text-left">Method</th>
                    <th class="px-4 py-2 text-left">Path</th>
                    <th class="px-4 py-2 text-left">IP</th>
                    <th class="px-4 py-2 text-left">Response</th>
                    <th class="px-4 py-2 text-left">Status</th>
                    <th class="px-4 py-2 text-left">Algorithm</th>
                    <th class="px-4 py-2 text-left">Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800">
                @foreach($recentLogs as $log)
                <tr class="hover:bg-gray-800/50">
                    <td class="px-4 py-2 text-orange-400">{{ $log->server?->name ?? 'N/A' }}</td>
                    <td class="px-4 py-2">
                        <span class="px-1.5 py-0.5 rounded text-xs font-mono
                            {{ $log->method === 'GET' ? 'bg-blue-900 text-blue-300' : 'bg-green-900 text-green-300' }}">
                            {{ $log->method }}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-gray-300 font-mono text-xs">{{ $log->path }}</td>
                    <td class="px-4 py-2 text-gray-400 text-xs">{{ $log->ip }}</td>
                    <td class="px-4 py-2 {{ $log->response_time > 200 ? 'text-red-400' : 'text-green-400' }}">
                        {{ $log->response_time }}ms
                    </td>
                    <td class="px-4 py-2">
                        <span class="text-xs {{ $log->status_code >= 400 ? 'text-red-400' : 'text-green-400' }}">
                            {{ $log->status_code }}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-500">-</td>
                    <td class="px-4 py-2 text-xs text-gray-500">{{ $log->created_at->diffForHumans() }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
