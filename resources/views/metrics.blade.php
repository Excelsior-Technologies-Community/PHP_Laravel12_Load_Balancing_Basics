@extends('layouts.app')
@section('title', 'API Metrics')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-orange-400">📡 API Metrics</h1>
        <button onclick="refreshMetrics()" class="text-xs bg-gray-800 hover:bg-gray-700 text-orange-400 border border-gray-700 px-3 py-1.5 rounded transition">
            ↻ Auto Refresh <span id="countdown" class="text-gray-500">(5s)</span>
        </button>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gray-900 border border-gray-800 rounded-lg p-4">
            <p class="text-xs text-gray-400 mb-1">Total Requests</p>
            <p class="text-2xl font-bold text-white" id="total-requests">{{ number_format($metrics['total_requests']) }}</p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-lg p-4">
            <p class="text-xs text-gray-400 mb-1">Avg Response Time</p>
            <p class="text-2xl font-bold text-green-400" id="avg-response">{{ round($metrics['avg_response_time_ms'], 1) }}ms</p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-lg p-4">
            <p class="text-xs text-gray-400 mb-1">Active Servers</p>
            <p class="text-2xl font-bold text-blue-400" id="active-servers">{{ $metrics['active_servers'] }}</p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-lg p-4">
            <p class="text-xs text-gray-400 mb-1">Total Servers</p>
            <p class="text-2xl font-bold text-purple-400">{{ count($metrics['servers']) }}</p>
        </div>
    </div>

    {{-- Server Cards --}}
    <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Server Instances</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6" id="servers-grid">
        @foreach($metrics['servers'] as $server)
        <div class="bg-gray-900 border {{ $server['is_online'] ? 'border-green-800' : 'border-red-900' }} rounded-lg p-4">
            <div class="flex justify-between items-center mb-3">
                <span class="font-semibold text-sm">{{ $server['name'] }}</span>
                <span class="text-xs px-2 py-0.5 rounded-full {{ $server['is_online'] ? 'bg-green-900 text-green-300' : 'bg-red-900 text-red-300' }}">
                    {{ $server['is_online'] ? 'ONLINE' : 'OFFLINE' }}
                </span>
            </div>
            <p class="text-xs text-gray-500 font-mono mb-3">{{ $server['ip_address'] }}:{{ $server['port'] }}</p>

            {{-- Load Bar --}}
            @php $loadPct = min(100, ($server['active_connections'] / 50) * 100) @endphp
            <div class="mb-3">
                <div class="flex justify-between text-xs text-gray-400 mb-1">
                    <span>Load</span>
                    <span>{{ $server['active_connections'] }} conn</span>
                </div>
                <div class="w-full bg-gray-800 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full {{ $loadPct > 70 ? 'bg-red-500' : ($loadPct > 40 ? 'bg-yellow-500' : 'bg-green-500') }}"
                        style="width: {{ $loadPct }}%"></div>
                </div>
            </div>

            <div class="space-y-1.5 text-xs">
                <div class="flex justify-between">
                    <span class="text-gray-400">Total Requests</span>
                    <span class="text-white font-mono">{{ number_format($server['request_count']) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Last Heartbeat</span>
                    <span class="text-gray-300">{{ $server['last_heartbeat'] }}</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- API Endpoints Reference --}}
    <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">API Endpoints</h2>
    <div class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden mb-6">
        <table class="w-full text-sm">
            <thead class="bg-gray-800 text-xs text-gray-400">
                <tr>
                    <th class="px-4 py-2 text-left">Method</th>
                    <th class="px-4 py-2 text-left">Endpoint</th>
                    <th class="px-4 py-2 text-left">Description</th>
                    <th class="px-4 py-2 text-left">Try</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800">
                @foreach([
                    ['GET', '/health', 'Full health status', '/health'],
                    ['GET', '/health/live', 'Liveness probe', '/health/live'],
                    ['GET', '/health/ready', 'Readiness probe', '/health/ready'],
                    ['GET', '/api/servers', 'List all servers', '/api/servers'],
                    ['GET', '/api/metrics', 'Real-time metrics', '/api/metrics'],
                    ['GET', '/api/requests/log', 'Request history', '/api/requests/log'],
                ] as [$method, $endpoint, $desc, $link])
                <tr class="hover:bg-gray-800/50">
                    <td class="px-4 py-2.5">
                        <span class="text-xs px-2 py-0.5 rounded font-mono bg-blue-900 text-blue-300">{{ $method }}</span>
                    </td>
                    <td class="px-4 py-2.5 font-mono text-xs text-gray-300">{{ $endpoint }}</td>
                    <td class="px-4 py-2.5 text-xs text-gray-400">{{ $desc }}</td>
                    <td class="px-4 py-2.5">
                        <a href="{{ $link }}" target="_blank" class="text-xs text-orange-400 hover:text-orange-300">Open ↗</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Recent Request Log --}}
    <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Recent Request Log</h2>
    <div class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden">
        <table class="w-full text-xs">
            <thead class="bg-gray-800 text-gray-400">
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
            <tbody id="logs-body" class="divide-y divide-gray-800">
                @foreach($logs as $log)
                <tr class="hover:bg-gray-800/40">
                    <td class="px-4 py-2 text-orange-400">{{ $log['server'] ?? 'N/A' }}</td>
                    <td class="px-4 py-2">
                        <span class="px-1.5 py-0.5 rounded font-mono {{ $log['method'] === 'GET' ? 'bg-blue-900 text-blue-300' : 'bg-green-900 text-green-300' }}">
                            {{ $log['method'] }}
                        </span>
                    </td>
                    <td class="px-4 py-2 font-mono text-gray-300">{{ $log['path'] }}</td>
                    <td class="px-4 py-2 text-gray-400">{{ $log['ip'] }}</td>
                    <td class="px-4 py-2 {{ $log['response_time'] > 200 ? 'text-red-400' : 'text-green-400' }}">
                        {{ $log['response_time'] }}ms
                    </td>
                    <td class="px-4 py-2 {{ $log['status_code'] >= 400 ? 'text-red-400' : 'text-green-400' }}">
                        {{ $log['status_code'] }}
                    </td>
                    <td class="px-4 py-2 text-gray-500">-</td>
                    <td class="px-4 py-2 text-gray-500">{{ $log['time'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
let countdown = 5;
const timer = setInterval(() => {
    countdown--;
    document.getElementById('countdown').textContent = `(${countdown}s)`;
    if (countdown <= 0) {
        countdown = 5;
        refreshMetrics();
    }
}, 1000);

async function refreshMetrics() {
    const res = await fetch('/api/metrics');
    const data = await res.json();
    document.getElementById('total-requests').textContent = data.total_requests.toLocaleString();
    document.getElementById('avg-response').textContent = Math.round(data.avg_response_time * 10) / 10 + 'ms';
    document.getElementById('active-servers').textContent = data.active_servers;

    const logsRes = await fetch('/api/requests/log');
    const logs = await logsRes.json();
    document.getElementById('logs-body').innerHTML = logs.slice(0, 20).map(l => `
        <tr class="hover:bg-gray-800/40">
            <td class="px-4 py-2 text-orange-400">${l.server || 'N/A'}</td>
            <td class="px-4 py-2"><span class="px-1.5 py-0.5 rounded font-mono ${l.method === 'GET' ? 'bg-blue-900 text-blue-300' : 'bg-green-900 text-green-300'}">${l.method}</span></td>
            <td class="px-4 py-2 font-mono text-gray-300">${l.path}</td>
            <td class="px-4 py-2 text-gray-400">${l.ip}</td>
            <td class="px-4 py-2 ${l.response_time > 200 ? 'text-red-400' : 'text-green-400'}">${l.response_time}ms</td>
            <td class="px-4 py-2 ${l.status_code >= 400 ? 'text-red-400' : 'text-green-400'}">${l.status_code}</td>
            <td class="px-4 py-2 text-gray-500">-</td>
            <td class="px-4 py-2 text-gray-500">${l.time}</td>
        </tr>
    `).join('');
}
</script>
@endsection
