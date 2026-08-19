@extends('layouts.app')
@section('title', 'Health Check')

@section('content')
<div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold mb-6 text-orange-400">🏥 Health Check</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <a href="/health" class="bg-gray-900 border border-green-700 rounded-lg p-4 hover:border-green-500 transition">
            <p class="text-xs text-gray-400 mb-1">Overall Health</p>
            <p class="text-lg font-bold text-green-400">GET /health</p>
            <p class="text-xs text-gray-500 mt-1">Full status check</p>
        </a>
        <a href="/health/live" class="bg-gray-900 border border-blue-700 rounded-lg p-4 hover:border-blue-500 transition">
            <p class="text-xs text-gray-400 mb-1">Liveness Probe</p>
            <p class="text-lg font-bold text-blue-400">GET /health/live</p>
            <p class="text-xs text-gray-500 mt-1">Is app alive?</p>
        </a>
        <a href="/health/ready" class="bg-gray-900 border border-purple-700 rounded-lg p-4 hover:border-purple-500 transition">
            <p class="text-xs text-gray-400 mb-1">Readiness Probe</p>
            <p class="text-lg font-bold text-purple-400">GET /health/ready</p>
            <p class="text-xs text-gray-500 mt-1">Ready to serve?</p>
        </a>
    </div>

    {{-- Main Status Card --}}
    <div class="bg-gray-900 border border-gray-800 rounded-lg p-6 mb-4">
        <div class="flex items-center gap-3 mb-6">
            <span class="w-3 h-3 rounded-full bg-green-400 animate-pulse"></span>
            <span class="text-xl font-bold text-green-400">{{ strtoupper($health['status']) }}</span>
            <span class="ml-auto text-xs text-gray-500">{{ $health['timestamp'] }}</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Server Info --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-400 mb-3 uppercase tracking-wider">Server Info</h3>
                <div class="space-y-2">
                    @foreach([
                        'Hostname' => $health['server']['hostname'],
                        'IP Address' => $health['server']['ip'],
                        'Port' => $health['server']['port'],
                        'PHP Version' => $health['server']['php_version'],
                    ] as $label => $value)
                    <div class="flex justify-between items-center py-1.5 border-b border-gray-800">
                        <span class="text-xs text-gray-400">{{ $label }}</span>
                        <span class="text-xs font-mono text-white">{{ $value }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Memory & Uptime --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-400 mb-3 uppercase tracking-wider">Resources</h3>
                <div class="space-y-2">
                    @foreach([
                        'Uptime' => $health['uptime_seconds'] . 's',
                        'Memory Used' => $health['memory']['used'],
                        'Memory Peak' => $health['memory']['peak'],
                        'DB Status' => $health['database']['status'],
                        'Cache Status' => $health['cache']['status'],
                        'Server Instances' => $health['database']['server_instances'] ?? 0,
                    ] as $label => $value)
                    <div class="flex justify-between items-center py-1.5 border-b border-gray-800">
                        <span class="text-xs text-gray-400">{{ $label }}</span>
                        <span class="text-xs font-mono {{ in_array($value, ['ok', 'healthy']) ? 'text-green-400' : 'text-white' }}">
                            {{ $value }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Probe Results --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-gray-900 border border-gray-800 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-gray-400 mb-3 uppercase tracking-wider">Liveness</h3>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                <span class="text-blue-400 font-bold">{{ strtoupper($live['status']) }}</span>
            </div>
            <p class="text-xs text-gray-500 mt-2">{{ $live['timestamp'] }}</p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-gray-400 mb-3 uppercase tracking-wider">Readiness</h3>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full {{ $ready['status'] === 'ready' ? 'bg-green-400' : 'bg-red-400' }}"></span>
                <span class="{{ $ready['status'] === 'ready' ? 'text-green-400' : 'text-red-400' }} font-bold">
                    {{ strtoupper($ready['status']) }}
                </span>
            </div>
            <div class="mt-2 space-y-1">
                @foreach($ready['checks'] as $check => $result)
                <div class="flex justify-between text-xs">
                    <span class="text-gray-400">{{ ucfirst($check) }}</span>
                    <span class="{{ $result['status'] === 'ok' ? 'text-green-400' : 'text-red-400' }}">{{ $result['status'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
