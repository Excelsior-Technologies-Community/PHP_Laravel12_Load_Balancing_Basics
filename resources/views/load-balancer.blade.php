@extends('layouts.app')
@section('title', 'Load Balancer Algorithms')

@section('content')
<div class="max-w-7xl mx-auto">
    <h1 class="text-2xl font-bold mb-6 text-orange-400">⚖️ Load Balancer Algorithm Demo</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: Algorithm Selector + Simulate --}}
        <div class="lg:col-span-1 space-y-4">

            {{-- Algorithm Selector --}}
            <div class="bg-gray-900 rounded-lg p-4 border border-gray-800">
                <h2 class="font-semibold mb-3 text-gray-300">Select Algorithm</h2>
                <div class="space-y-2">
                    @foreach(['round_robin' => 'Round Robin', 'least_connections' => 'Least Connections', 'ip_hash' => 'IP Hash (Sticky)', 'weighted_round_robin' => 'Weighted Round Robin'] as $key => $label)
                    <label class="flex items-center gap-3 p-2 rounded cursor-pointer hover:bg-gray-800 transition">
                        <input type="radio" name="algorithm" value="{{ $key }}"
                            {{ $currentAlgorithm === $key ? 'checked' : '' }}
                            class="accent-orange-400">
                        <div>
                            <p class="text-sm font-medium">{{ $label }}</p>
                            <p class="text-xs text-gray-500">
                                @switch($key)
                                    @case('round_robin') Distributes requests equally in order @break
                                    @case('least_connections') Routes to server with fewest connections @break
                                    @case('ip_hash') Same IP always goes to same server @break
                                    @case('weighted_round_robin') Higher weight = more requests @break
                                @endswitch
                            </p>
                        </div>
                    </label>
                    @endforeach
                </div>
                <button onclick="simulate()" class="mt-4 w-full bg-orange-500 hover:bg-orange-600 text-white py-2 rounded font-medium transition">
                    🚀 Simulate Request
                </button>
            </div>

            {{-- Sticky Session Demo --}}
            <div class="bg-gray-900 rounded-lg p-4 border border-gray-800">
                <h2 class="font-semibold mb-3 text-gray-300">Sticky Session Demo</h2>
                <div class="flex gap-2">
                    <button onclick="testSticky(true)" class="flex-1 bg-blue-700 hover:bg-blue-600 text-white py-2 rounded text-sm transition">
                        🔒 Sticky ON
                    </button>
                    <button onclick="testSticky(false)" class="flex-1 bg-gray-700 hover:bg-gray-600 text-white py-2 rounded text-sm transition">
                        🔀 Sticky OFF
                    </button>
                </div>
                <div id="sticky-result" class="mt-3 text-xs text-gray-400 hidden"></div>
            </div>

            {{-- Simulate Result --}}
            <div id="result-box" class="bg-gray-900 rounded-lg p-4 border border-gray-700 hidden">
                <h2 class="font-semibold mb-2 text-gray-300">Last Request Result</h2>
                <div id="result-content" class="text-sm space-y-1"></div>
            </div>
        </div>

        {{-- Right: Server Status + Logs --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Server Status with Failover Toggle --}}
            <div class="bg-gray-900 rounded-lg p-4 border border-gray-800">
                <h2 class="font-semibold mb-3 text-gray-300">Server Instances (Click to Toggle Failover)</h2>
                <div class="grid grid-cols-2 gap-3" id="servers-grid">
                    @foreach($servers as $server)
                    <div id="server-{{ $server->id }}" class="p-3 rounded-lg border cursor-pointer transition
                        {{ $server->is_online ? 'border-green-700 bg-green-950/30' : 'border-red-800 bg-red-950/30' }}"
                        onclick="toggleServer({{ $server->id }})">
                        <div class="flex justify-between items-center mb-1">
                            <span class="font-medium text-sm">{{ $server->name }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full server-status-{{ $server->id }}
                                {{ $server->is_online ? 'bg-green-900 text-green-300' : 'bg-red-900 text-red-300' }}">
                                {{ $server->is_online ? 'ONLINE' : 'OFFLINE' }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-400">{{ $server->ip_address }}:{{ $server->port }}</p>
                        <div class="mt-2 grid grid-cols-2 gap-1 text-xs">
                            <span class="text-gray-400">Connections: <span class="text-yellow-400">{{ $server->active_connections }}</span></span>
                            <span class="text-gray-400">Weight: <span class="text-blue-400">{{ $server->weight }}</span></span>
                            <span class="text-gray-400">Requests: <span class="text-white">{{ number_format($server->request_count) }}</span></span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Recent Logs --}}
            <div class="bg-gray-900 rounded-lg p-4 border border-gray-800">
                <div class="flex justify-between items-center mb-3">
                    <h2 class="font-semibold text-gray-300">Live Request Log</h2>
                    <button onclick="refreshLogs()" class="text-xs text-orange-400 hover:text-orange-300">↻ Refresh</button>
                </div>
                <div id="logs-table" class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="text-gray-500">
                            <tr>
                                <th class="text-left pb-2">Server</th>
                                <th class="text-left pb-2">Path</th>
                                <th class="text-left pb-2">Response</th>
                                <th class="text-left pb-2">Algorithm</th>
                                <th class="text-left pb-2">Time</th>
                            </tr>
                        </thead>
                        <tbody id="logs-body" class="divide-y divide-gray-800">
                            @foreach($logs as $log)
                            <tr>
                                <td class="py-1.5 text-orange-400">{{ $log->server?->name }}</td>
                                <td class="py-1.5 font-mono text-gray-300">{{ $log->path }}</td>
                                <td class="py-1.5 {{ $log->response_time > 200 ? 'text-red-400' : 'text-green-400' }}">{{ $log->response_time }}ms</td>
                                <td class="py-1.5 text-gray-500">-</td>
                                <td class="py-1.5 text-gray-500">{{ $log->created_at->diffForHumans() }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;

async function simulate() {
    const algorithm = document.querySelector('input[name="algorithm"]:checked')?.value || 'round_robin';
    const res = await fetch('/load-balancer/simulate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ algorithm })
    });
    const data = await res.json();

    const box = document.getElementById('result-box');
    const content = document.getElementById('result-content');
    box.classList.remove('hidden');
    content.innerHTML = `
        <div class="flex justify-between"><span class="text-gray-400">Routed To</span><span class="text-orange-400 font-bold">${data.routed_to}</span></div>
        <div class="flex justify-between"><span class="text-gray-400">Host</span><span class="text-white">${data.host}</span></div>
        <div class="flex justify-between"><span class="text-gray-400">Algorithm</span><span class="text-purple-400">${data.algorithm}</span></div>
        <div class="flex justify-between"><span class="text-gray-400">Response Time</span><span class="text-green-400">${data.response_time_ms}ms</span></div>
        <div class="flex justify-between"><span class="text-gray-400">Server Total Requests</span><span class="text-blue-400">${data.server_total_requests}</span></div>
    `;
    setTimeout(refreshLogs, 300);
}

async function toggleServer(id) {
    const res = await fetch(`/load-balancer/servers/${id}/toggle`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf }
    });
    const data = await res.json();
    const card = document.getElementById(`server-${id}`);
    const badge = card.querySelector(`.server-status-${id}`);
    if (data.is_online) {
        card.className = card.className.replace('border-red-800 bg-red-950/30', 'border-green-700 bg-green-950/30');
        badge.className = badge.className.replace('bg-red-900 text-red-300', 'bg-green-900 text-green-300');
        badge.textContent = 'ONLINE';
    } else {
        card.className = card.className.replace('border-green-700 bg-green-950/30', 'border-red-800 bg-red-950/30');
        badge.className = badge.className.replace('bg-green-900 text-green-300', 'bg-red-900 text-red-300');
        badge.textContent = 'OFFLINE';
    }
}

async function testSticky(sticky) {
    const res = await fetch('/load-balancer/sticky-session', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ sticky })
    });
    const data = await res.json();
    const el = document.getElementById('sticky-result');
    el.classList.remove('hidden');
    el.innerHTML = `<span class="text-white">→ ${data.routed_to}</span> | ${data.message}`;
}

async function refreshLogs() {
    const res = await fetch('/api/requests/log');
    const logs = await res.json();
    const tbody = document.getElementById('logs-body');
    tbody.innerHTML = logs.slice(0, 15).map(l => `
        <tr>
            <td class="py-1.5 text-orange-400">${l.server || 'N/A'}</td>
            <td class="py-1.5 font-mono text-gray-300">${l.path}</td>
            <td class="py-1.5 ${l.response_time_ms > 200 ? 'text-red-400' : 'text-green-400'}">${l.response_time_ms}ms</td>
            <td class="py-1.5 text-purple-400">${l.algorithm || '-'}</td>
            <td class="py-1.5 text-gray-500">${l.time}</td>
        </tr>
    `).join('');
}
</script>
@endsection
