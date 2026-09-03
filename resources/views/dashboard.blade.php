@extends('layouts.app')

@section('title', 'Server Dashboard')

@section('content')

<div class="max-w-7xl mx-auto space-y-6">

    {{-- ============================================================
         HEADER
    ============================================================ --}}

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

        <div>
            <h1 class="text-2xl font-bold text-orange-400">
                📊 Server Dashboard
            </h1>

            <p class="text-sm text-gray-500 mt-1">
                Load balancer server monitoring and analytics
            </p>
        </div>

        <div class="flex gap-2">

            {{-- Auto Refresh --}}
            <button
                onclick="toggleAutoRefresh()"
                id="auto-refresh-btn"
                class="px-4 py-2 rounded bg-green-700 hover:bg-green-600 text-white text-sm">
                🔄 Auto Refresh: ON
            </button>

            {{-- Export --}}
            <a
                href="{{ route('dashboard.export-logs') }}"
                class="px-4 py-2 rounded bg-blue-700 hover:bg-blue-600 text-white text-sm">
                📥 Export CSV
            </a>

            {{-- Add Server --}}
            <button
                onclick="openAddServerModal()"
                class="px-4 py-2 rounded bg-orange-500 hover:bg-orange-600 text-white text-sm">
                ➕ Add Server
            </button>

        </div>

    </div>


    {{-- ============================================================
         STATISTICS
    ============================================================ --}}

    <div
        id="stats-container"
        class="grid grid-cols-2 md:grid-cols-4 gap-4">

        <div class="bg-gray-900 rounded-lg p-4 border border-gray-800">

            <p class="text-gray-400 text-xs">
                Total Requests
            </p>

            <p
                id="total-requests"
                class="text-2xl font-bold text-white">
                {{ number_format($totalRequests) }}
            </p>

        </div>


        <div class="bg-gray-900 rounded-lg p-4 border border-gray-800">

            <p class="text-gray-400 text-xs">
                Avg Response Time
            </p>

            <p
                id="avg-response-time"
                class="text-2xl font-bold text-green-400">
                {{ round($avgResponseTime, 1) }}ms
            </p>

        </div>


        <div class="bg-gray-900 rounded-lg p-4 border border-gray-800">

            <p class="text-gray-400 text-xs">
                Active Servers
            </p>

            <p
                id="active-servers"
                class="text-2xl font-bold text-blue-400">
                {{ $onlineServers }}/{{ $totalServers }}
            </p>

        </div>


        <div class="bg-gray-900 rounded-lg p-4 border border-gray-800">

            <p class="text-gray-400 text-xs">
                Algorithm
            </p>

            <p class="text-lg font-bold text-orange-400">
                {{ ucwords(str_replace('_', ' ', $currentAlgorithm)) }}
            </p>

        </div>

    </div>


    {{-- ============================================================
         SEARCH + STATUS FILTER
    ============================================================ --}}

    <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">

        <form
            method="GET"
            action="{{ route('dashboard') }}"
            class="grid grid-cols-1 md:grid-cols-3 gap-3">

            {{-- Search --}}
            <div>

                <label class="text-xs text-gray-400">
                    Search Server
                </label>

                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Name, IP, hostname or port..."
                    class="mt-1 w-full bg-gray-950 border border-gray-700 rounded px-3 py-2 text-sm text-white focus:border-orange-500 focus:outline-none">

            </div>


            {{-- Status --}}
            <div>

                <label class="text-xs text-gray-400">
                    Server Status
                </label>

                <select
                    name="status"
                    class="mt-1 w-full bg-gray-950 border border-gray-700 rounded px-3 py-2 text-sm text-white">

                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>
                        All Servers
                    </option>

                    <option value="online" {{ $status === 'online' ? 'selected' : '' }}>
                        Online
                    </option>

                    <option value="offline" {{ $status === 'offline' ? 'selected' : '' }}>
                        Offline
                    </option>

                </select>

            </div>


            {{-- Buttons --}}
            <div class="flex items-end gap-2">

                <button
                    type="submit"
                    class="flex-1 bg-orange-500 hover:bg-orange-600 text-white rounded px-4 py-2 text-sm">
                    🔎 Search
                </button>

                <a
                    href="{{ route('dashboard') }}"
                    class="bg-gray-700 hover:bg-gray-600 text-white rounded px-4 py-2 text-sm">
                    Reset
                </a>

            </div>

        </form>

    </div>


    {{-- ============================================================
         SERVER INSTANCES
    ============================================================ --}}

    <div>

        <div class="flex justify-between items-center mb-3">

            <h2 class="text-lg font-semibold text-gray-300">
                Server Instances
            </h2>

            <span class="text-xs text-gray-500">
                {{ $servers->total() }} servers found
            </span>

        </div>


        <div
            id="servers-grid"
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

            @forelse($servers as $server)

                <div
                    id="server-card-{{ $server->id }}"
                    class="bg-gray-900 rounded-lg p-4 border
                    {{ $server->is_online
                        ? 'border-green-700'
                        : 'border-red-800' }}">

                    {{-- Header --}}
                    <div class="flex justify-between items-start mb-2">

                        <span class="font-semibold text-white">
                            {{ $server->name }}
                        </span>

                        <span
                            id="status-badge-{{ $server->id }}"
                            class="text-xs px-2 py-0.5 rounded-full
                            {{ $server->is_online
                                ? 'bg-green-900 text-green-300'
                                : 'bg-red-900 text-red-300' }}">

                            {{ $server->is_online ? 'ONLINE' : 'OFFLINE' }}

                        </span>

                    </div>


                    {{-- Server --}}
                    <p class="text-xs text-gray-400 mb-3">
                        {{ $server->ip_address }}:{{ $server->port }}
                    </p>


                    {{-- Details --}}
                    <div class="space-y-2 text-xs">

                        <div class="flex justify-between">
                            <span class="text-gray-400">
                                Requests
                            </span>

                            <span
                                id="requests-{{ $server->id }}"
                                class="text-white">
                                {{ number_format($server->request_count) }}
                            </span>
                        </div>


                        <div class="flex justify-between">

                            <span class="text-gray-400">
                                Connections
                            </span>

                            <span
                                id="connections-{{ $server->id }}"
                                class="text-yellow-400">
                                {{ $server->active_connections }}
                            </span>

                        </div>


                        <div class="flex justify-between">

                            <span class="text-gray-400">
                                Weight
                            </span>

                            <span class="text-blue-400">
                                {{ $server->weight }}
                            </span>

                        </div>


                        <div class="flex justify-between">

                            <span class="text-gray-400">
                                Heartbeat
                            </span>

                            <span class="text-gray-300">
                                {{ $server->last_heartbeat?->diffForHumans() ?? 'Never' }}
                            </span>

                        </div>

                    </div>


                    {{-- Actions --}}
                    <div class="grid grid-cols-3 gap-2 mt-4">

                        <button
                            onclick="toggleServer({{ $server->id }})"
                            class="bg-gray-800 hover:bg-gray-700 text-white rounded py-1.5 text-xs">

                            {{ $server->is_online ? 'Offline' : 'Online' }}

                        </button>


                        <button
                            onclick="showPerformance({{ $server->id }}, '{{ addslashes($server->name) }}')"
                            class="bg-blue-900 hover:bg-blue-800 text-blue-300 rounded py-1.5 text-xs">

                            📈 History

                        </button>


                        <button
                            onclick="deleteServer({{ $server->id }}, '{{ addslashes($server->name) }}')"
                            class="bg-red-900 hover:bg-red-800 text-red-300 rounded py-1.5 text-xs">

                            🗑️ Delete

                        </button>

                    </div>

                </div>

            @empty

                <div class="col-span-full bg-gray-900 border border-gray-800 rounded-lg p-8 text-center">

                    <p class="text-gray-400">
                        No servers found.
                    </p>

                </div>

            @endforelse

        </div>


        {{-- Pagination --}}
        @if($servers->hasPages())

            <div class="mt-5">

                {{ $servers->links() }}

            </div>

        @endif

    </div>


    {{-- ============================================================
         SERVER-WISE ANALYTICS
    ============================================================ --}}

    <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">

        <div class="flex justify-between items-center mb-4">

            <h2 class="text-lg font-semibold text-gray-300">
                📊 Server-wise Request Analytics
            </h2>

            <button
                onclick="loadAnalytics()"
                class="text-xs text-orange-400 hover:text-orange-300">

                ↻ Refresh

            </button>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full text-sm">

                <thead class="text-xs text-gray-500 border-b border-gray-800">

                    <tr>

                        <th class="text-left py-2">
                            Server
                        </th>

                        <th class="text-left py-2">
                            Port
                        </th>

                        <th class="text-left py-2">
                            Requests
                        </th>

                        <th class="text-left py-2">
                            Successful
                        </th>

                        <th class="text-left py-2">
                            Failed
                        </th>

                        <th class="text-left py-2">
                            Avg Response
                        </th>

                        <th class="text-left py-2">
                            Success Rate
                        </th>

                    </tr>

                </thead>


                <tbody
                    id="analytics-body"
                    class="divide-y divide-gray-800">

                    @foreach($serverAnalytics as $server)

                        @php

                            $serverLogs = \App\Models\RequestLog::where(
                                'server_instance_id',
                                $server->id
                            );

                            $total = $serverLogs->count();

                            $success = (clone $serverLogs)
                                ->where('status_code', '<', 400)
                                ->count();

                            $failed = $total - $success;

                            $avg = (clone $serverLogs)
                                ->avg('response_time');

                            $rate = $total > 0
                                ? round(($success / $total) * 100, 2)
                                : 0;

                        @endphp

                        <tr>

                            <td class="py-2 text-orange-400">
                                {{ $server->name }}
                            </td>

                            <td class="py-2 text-gray-400">
                                {{ $server->port }}
                            </td>

                            <td class="py-2 text-white">
                                {{ number_format($total) }}
                            </td>

                            <td class="py-2 text-green-400">
                                {{ number_format($success) }}
                            </td>

                            <td class="py-2 text-red-400">
                                {{ number_format($failed) }}
                            </td>

                            <td class="py-2 text-yellow-400">
                                {{ round($avg ?? 0, 1) }}ms
                            </td>

                            <td class="py-2 text-blue-400">
                                {{ $rate }}%
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>


    {{-- ============================================================
         RECENT REQUEST LOGS
    ============================================================ --}}

    <div class="bg-gray-900 rounded-lg border border-gray-800 overflow-hidden">

        <div class="flex justify-between items-center p-4">

            <h2 class="text-lg font-semibold text-gray-300">
                Recent Requests
            </h2>

            <button
                onclick="refreshDashboard()"
                class="text-xs text-orange-400 hover:text-orange-300">

                ↻ Refresh

            </button>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full text-sm">

                <thead class="bg-gray-800 text-gray-400 text-xs">

                    <tr>

                        <th class="px-4 py-2 text-left">
                            Server
                        </th>

                        <th class="px-4 py-2 text-left">
                            Method
                        </th>

                        <th class="px-4 py-2 text-left">
                            Path
                        </th>

                        <th class="px-4 py-2 text-left">
                            IP
                        </th>

                        <th class="px-4 py-2 text-left">
                            Response
                        </th>

                        <th class="px-4 py-2 text-left">
                            Status
                        </th>

                        <th class="px-4 py-2 text-left">
                            Time
                        </th>

                    </tr>

                </thead>


                <tbody
                    id="logs-body"
                    class="divide-y divide-gray-800">

                    @foreach($recentLogs as $log)

                        <tr class="hover:bg-gray-800/50">

                            <td class="px-4 py-2 text-orange-400">
                                {{ $log->server?->name ?? 'N/A' }}
                            </td>

                            <td class="px-4 py-2">

                                <span class="px-1.5 py-0.5 rounded text-xs font-mono
                                    {{ $log->method === 'GET'
                                        ? 'bg-blue-900 text-blue-300'
                                        : 'bg-green-900 text-green-300' }}">

                                    {{ $log->method }}

                                </span>

                            </td>

                            <td class="px-4 py-2 text-gray-300 font-mono text-xs">
                                {{ $log->path }}
                            </td>

                            <td class="px-4 py-2 text-gray-400 text-xs">
                                {{ $log->client_ip }}
                            </td>

                            <td class="px-4 py-2
                                {{ $log->response_time > 200
                                    ? 'text-red-400'
                                    : 'text-green-400' }}">

                                {{ $log->response_time }}ms

                            </td>

                            <td class="px-4 py-2">

                                <span class="text-xs
                                    {{ $log->status_code >= 400
                                        ? 'text-red-400'
                                        : 'text-green-400' }}">

                                    {{ $log->status_code }}

                                </span>

                            </td>

                            <td class="px-4 py-2 text-xs text-gray-500">

                                {{ $log->created_at?->diffForHumans() }}

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

</div>


{{-- ================================================================
     ADD SERVER MODAL
================================================================ --}}

<div
    id="add-server-modal"
    class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50">

    <div class="bg-gray-900 border border-gray-700 rounded-lg p-6 w-full max-w-lg">

        <div class="flex justify-between items-center mb-5">

            <h2 class="text-lg font-bold text-white">
                ➕ Add Server
            </h2>

            <button
                onclick="closeAddServerModal()"
                class="text-gray-400 hover:text-white">

                ✕

            </button>

        </div>


        <form
            id="add-server-form"
            onsubmit="addServer(event)">

            @csrf

            <div class="space-y-4">

                <input
                    id="server-name"
                    name="name"
                    required
                    placeholder="Server Name"
                    class="w-full bg-gray-950 border border-gray-700 rounded px-3 py-2 text-sm text-white">

                <input
                    id="server-ip"
                    name="ip_address"
                    required
                    placeholder="IP Address e.g. 127.0.0.1"
                    class="w-full bg-gray-950 border border-gray-700 rounded px-3 py-2 text-sm text-white">

                <input
                    id="server-port"
                    name="port"
                    type="number"
                    required
                    min="1"
                    max="65535"
                    placeholder="Port"
                    class="w-full bg-gray-950 border border-gray-700 rounded px-3 py-2 text-sm text-white">

                <input
                    id="server-hostname"
                    name="hostname"
                    required
                    placeholder="Hostname"
                    class="w-full bg-gray-950 border border-gray-700 rounded px-3 py-2 text-sm text-white">


                <div class="grid grid-cols-2 gap-3">

                    <input
                        name="weight"
                        type="number"
                        min="1"
                        max="10"
                        value="1"
                        placeholder="Weight"
                        class="bg-gray-950 border border-gray-700 rounded px-3 py-2 text-sm text-white">


                    <select
                        name="algorithm"
                        class="bg-gray-950 border border-gray-700 rounded px-3 py-2 text-sm text-white">

                        <option value="round_robin">
                            Round Robin
                        </option>

                        <option value="least_connections">
                            Least Connections
                        </option>

                        <option value="ip_hash">
                            IP Hash
                        </option>

                        <option value="weighted_round_robin">
                            Weighted Round Robin
                        </option>

                    </select>

                </div>


                <div
                    id="add-server-error"
                    class="hidden text-sm text-red-400">
                </div>


                <button
                    type="submit"
                    class="w-full bg-orange-500 hover:bg-orange-600 text-white py-2 rounded font-medium">

                    Add Server

                </button>

            </div>

        </form>

    </div>

</div>


{{-- ================================================================
     PERFORMANCE MODAL
================================================================ --}}

<div
    id="performance-modal"
    class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50">

    <div class="bg-gray-900 border border-gray-700 rounded-lg p-6 w-full max-w-4xl">

        <div class="flex justify-between items-center mb-5">

            <h2
                id="performance-title"
                class="text-lg font-bold text-orange-400">
                📈 Performance History
            </h2>

            <button
                onclick="closePerformance()"
                class="text-gray-400 hover:text-white">

                ✕

            </button>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full text-sm">

                <thead class="text-gray-500 text-xs">

                    <tr>

                        <th class="text-left py-2">
                            Time
                        </th>

                        <th class="text-left py-2">
                            Requests
                        </th>

                        <th class="text-left py-2">
                            Connections
                        </th>

                        <th class="text-left py-2">
                            Response
                        </th>

                        <th class="text-left py-2">
                            Success Rate
                        </th>

                    </tr>

                </thead>

                <tbody
                    id="performance-body"
                    class="divide-y divide-gray-800">

                </tbody>

            </table>

        </div>

    </div>

</div>


@endsection


@section('scripts')

<script>

const csrf =
    document.querySelector('meta[name="csrf-token"]')?.content;

let autoRefresh = true;

let refreshInterval = null;


/*
|--------------------------------------------------------------------------
| Add Server Modal
|--------------------------------------------------------------------------
*/

function openAddServerModal()
{
    const modal =
        document.getElementById('add-server-modal');

    modal.classList.remove('hidden');

    modal.classList.add('flex');
}


function closeAddServerModal()
{
    const modal =
        document.getElementById('add-server-modal');

    modal.classList.add('hidden');

    modal.classList.remove('flex');
}


/*
|--------------------------------------------------------------------------
| Add Server
|--------------------------------------------------------------------------
*/

async function addServer(event)
{
    event.preventDefault();

    const form =
        document.getElementById('add-server-form');

    const formData =
        new FormData(form);

    const errorBox =
        document.getElementById('add-server-error');

    errorBox.classList.add('hidden');

    try {

        const response =
            await fetch(
                "{{ route('dashboard.servers.store') }}",
                {
                    method: 'POST',

                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    },

                    body: formData
                }
            );

        const data =
            await response.json();

        if (!response.ok) {

            let message =
                data.message || 'Unable to add server.';

            if (data.errors) {

                message =
                    Object.values(data.errors)
                        .flat()
                        .join('<br>');
            }

            errorBox.innerHTML = message;

            errorBox.classList.remove('hidden');

            return;
        }

        alert(data.message);

        closeAddServerModal();

        form.reset();

        location.reload();

    } catch (error) {

        errorBox.innerHTML =
            error.message;

        errorBox.classList.remove('hidden');
    }
}


/*
|--------------------------------------------------------------------------
| Delete Server
|--------------------------------------------------------------------------
*/

async function deleteServer(id, name)
{
    if (!confirm(
        `Are you sure you want to delete ${name}?`
    )) {
        return;
    }

    try {

        const response =
            await fetch(
                `/dashboard/servers/${id}`,
                {
                    method: 'DELETE',

                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    }
                }
            );

        const data =
            await response.json();

        if (!response.ok) {

            alert(
                data.message ||
                'Unable to delete server.'
            );

            return;
        }

        document
            .getElementById(`server-card-${id}`)
            ?.remove();

        alert(data.message);

        refreshDashboard();

    } catch (error) {

        alert(error.message);
    }
}


/*
|--------------------------------------------------------------------------
| Toggle Server
|--------------------------------------------------------------------------
*/

async function toggleServer(id)
{
    try {

        const response =
            await fetch(
                `/dashboard/servers/${id}/toggle`,
                {
                    method: 'POST',

                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    }
                }
            );

        const data =
            await response.json();

        if (!response.ok) {

            alert(
                data.message ||
                'Unable to update server.'
            );

            return;
        }

        location.reload();

    } catch (error) {

        alert(error.message);
    }
}


/*
|--------------------------------------------------------------------------
| Performance History
|--------------------------------------------------------------------------
*/

async function showPerformance(id, name)
{
    const modal =
        document.getElementById('performance-modal');

    const title =
        document.getElementById('performance-title');

    const body =
        document.getElementById('performance-body');

    title.innerText =
        `📈 ${name} - Performance History`;

    body.innerHTML = `
        <tr>
            <td colspan="5"
                class="text-center py-5 text-gray-500">
                Loading...
            </td>
        </tr>
    `;

    modal.classList.remove('hidden');

    modal.classList.add('flex');

    try {

        const response =
            await fetch(
                `/dashboard/servers/${id}/performance`
            );

        const data =
            await response.json();

        if (!data.history.length) {

            body.innerHTML = `
                <tr>
                    <td colspan="5"
                        class="text-center py-5 text-gray-500">
                        No performance history available.
                    </td>
                </tr>
            `;

            return;
        }

        body.innerHTML =
            data.history.map(item => `

                <tr>

                    <td class="py-2 text-gray-400">
                        ${item.time ?? '-'}
                    </td>

                    <td class="py-2 text-white">
                        ${item.requests}
                    </td>

                    <td class="py-2 text-yellow-400">
                        ${item.connections}
                    </td>

                    <td class="py-2 text-green-400">
                        ${item.response_time}ms
                    </td>

                    <td class="py-2 text-blue-400">
                        ${item.success_rate}%
                    </td>

                </tr>

            `).join('');

    } catch (error) {

        body.innerHTML = `
            <tr>
                <td colspan="5"
                    class="text-center py-5 text-red-400">
                    ${error.message}
                </td>
            </tr>
        `;
    }
}


function closePerformance()
{
    const modal =
        document.getElementById('performance-modal');

    modal.classList.add('hidden');

    modal.classList.remove('flex');
}


/*
|--------------------------------------------------------------------------
| Analytics
|--------------------------------------------------------------------------
*/

async function loadAnalytics()
{
    try {

        const response =
            await fetch(
                "{{ route('dashboard.analytics') }}"
            );

        const data =
            await response.json();

        const body =
            document.getElementById('analytics-body');

        body.innerHTML =
            data.analytics.map(server => `

                <tr>

                    <td class="py-2 text-orange-400">
                        ${server.name}
                    </td>

                    <td class="py-2 text-gray-400">
                        ${server.port}
                    </td>

                    <td class="py-2 text-white">
                        ${server.requests}
                    </td>

                    <td class="py-2 text-green-400">
                        ${server.successful}
                    </td>

                    <td class="py-2 text-red-400">
                        ${server.failed}
                    </td>

                    <td class="py-2 text-yellow-400">
                        ${server.avg_response_time}ms
                    </td>

                    <td class="py-2 text-blue-400">
                        ${server.success_rate}%
                    </td>

                </tr>

            `).join('');

    } catch (error) {

        console.error(error);
    }
}


/*
|--------------------------------------------------------------------------
| Real-time Dashboard
|--------------------------------------------------------------------------
*/

async function refreshDashboard()
{
    if (!autoRefresh) {
        return;
    }

    try {

        const response =
            await fetch(
                "{{ route('dashboard.realtime') }}"
            );

        const data =
            await response.json();


        /*
        |--------------------------------------------------------------------------
        | Stats
        |--------------------------------------------------------------------------
        */

        document
            .getElementById('total-requests')
            .innerText =
                Number(
                    data.stats.total_requests
                ).toLocaleString();


        document
            .getElementById('avg-response-time')
            .innerText =
                `${data.stats.avg_response_time}ms`;


        document
            .getElementById('active-servers')
            .innerText =
                `${data.stats.online_servers}/${data.stats.total_servers}`;


        /*
        |--------------------------------------------------------------------------
        | Server cards
        |--------------------------------------------------------------------------
        */

        data.servers.forEach(server => {

            const requests =
                document.getElementById(
                    `requests-${server.id}`
                );

            const connections =
                document.getElementById(
                    `connections-${server.id}`
                );

            const badge =
                document.getElementById(
                    `status-badge-${server.id}`
                );

            const card =
                document.getElementById(
                    `server-card-${server.id}`
                );


            if (requests) {

                requests.innerText =
                    Number(
                        server.request_count
                    ).toLocaleString();
            }


            if (connections) {

                connections.innerText =
                    server.active_connections;
            }


            if (badge) {

                if (server.is_online) {

                    badge.innerText =
                        'ONLINE';

                    badge.className =
                        'text-xs px-2 py-0.5 rounded-full bg-green-900 text-green-300';

                } else {

                    badge.innerText =
                        'OFFLINE';

                    badge.className =
                        'text-xs px-2 py-0.5 rounded-full bg-red-900 text-red-300';
                }
            }


            if (card) {

                if (server.is_online) {

                    card.classList.remove(
                        'border-red-800'
                    );

                    card.classList.add(
                        'border-green-700'
                    );

                } else {

                    card.classList.remove(
                        'border-green-700'
                    );

                    card.classList.add(
                        'border-red-800'
                    );
                }
            }

        });


        /*
        |--------------------------------------------------------------------------
        | Request logs
        |--------------------------------------------------------------------------
        */

        const logsBody =
            document.getElementById(
                'logs-body'
            );

        if (logsBody && data.logs) {

            logsBody.innerHTML =
                data.logs.map(log => `

                    <tr class="hover:bg-gray-800/50">

                        <td class="px-4 py-2 text-orange-400">
                            ${log.server}
                        </td>

                        <td class="px-4 py-2 text-gray-300 font-mono text-xs">
                            ${log.path}
                        </td>

                        <td class="px-4 py-2
                            ${
                                log.response_time > 200
                                    ? 'text-red-400'
                                    : 'text-green-400'
                            }">

                            ${log.response_time}ms

                        </td>

                        <td class="px-4 py-2 text-gray-400">
                            ${log.status_code}
                        </td>

                        <td class="px-4 py-2 text-xs text-gray-500">
                            ${log.time ?? '-'}
                        </td>

                    </tr>

                `).join('');
        }

    } catch (error) {

        console.error(
            'Dashboard refresh failed:',
            error
        );
    }
}


/*
|--------------------------------------------------------------------------
| Auto Refresh
|--------------------------------------------------------------------------
*/

function toggleAutoRefresh()
{
    autoRefresh =
        !autoRefresh;

    const button =
        document.getElementById(
            'auto-refresh-btn'
        );

    if (autoRefresh) {

        button.innerText =
            '🔄 Auto Refresh: ON';

        button.className =
            'px-4 py-2 rounded bg-green-700 hover:bg-green-600 text-white text-sm';

        startAutoRefresh();

    } else {

        button.innerText =
            '⏸ Auto Refresh: OFF';

        button.className =
            'px-4 py-2 rounded bg-gray-700 hover:bg-gray-600 text-white text-sm';

        stopAutoRefresh();
    }
}


function startAutoRefresh()
{
    stopAutoRefresh();

    refreshInterval =
        setInterval(
            () => {

                refreshDashboard();

                loadAnalytics();

            },
            5000
        );
}


function stopAutoRefresh()
{
    if (refreshInterval) {

        clearInterval(
            refreshInterval
        );

        refreshInterval = null;
    }
}


/*
|--------------------------------------------------------------------------
| Start
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    function () {

        startAutoRefresh();

    }
);

</script>

@endsection

