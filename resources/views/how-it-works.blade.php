@extends('layouts.app')
@section('title', 'How It Works')

@section('content')
<div class="max-w-5xl mx-auto space-y-8">

    <h1 class="text-2xl font-bold text-orange-400">📖 How Does This Project Work?</h1>

    {{-- Architecture --}}
    <div class="bg-gray-900 border border-gray-800 rounded-lg p-6">
        <h2 class="text-lg font-semibold text-white mb-4">🏗️ Real World Architecture</h2>
        <div class="font-mono text-sm bg-gray-950 rounded p-4 text-green-400 leading-relaxed">
            <p class="text-gray-400 mb-2"># Real Production Setup</p>
            <p>Internet Traffic (1000s of users)</p>
            <p class="text-yellow-400 ml-4">↓</p>
            <p class="text-yellow-400">[Load Balancer] ← Nginx / AWS ALB / HAProxy</p>
            <p class="text-yellow-400 ml-4">↓ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;↓ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;↓ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;↓</p>
            <p>[Server-1] [Server-2] [Server-3] [Server-4]</p>
            <p class="text-blue-400">:8001 &nbsp;&nbsp;&nbsp;&nbsp;:8002 &nbsp;&nbsp;&nbsp;&nbsp;:8003 &nbsp;&nbsp;&nbsp;&nbsp;:8004</p>
            <p class="text-yellow-400 ml-4">↓ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;↓ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;↓ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;↓</p>
            <p class="text-purple-400">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[Same MySQL Database]</p>
        </div>
    </div>

    {{-- 4 Servers Explanation --}}
    <div class="bg-gray-900 border border-gray-800 rounded-lg p-6">
        <h2 class="text-lg font-semibold text-white mb-4">🖥️ How to Run 4 Servers in Real Environment?</h2>

        <div class="bg-yellow-950 border border-yellow-800 rounded p-3 mb-4 text-sm text-yellow-300">
            ⚠️ Currently there are 4 server records in the database but they are not actually running. Below is explained how to set it up for real.
        </div>

        <p class="text-gray-300 text-sm mb-4">
            You can run Laravel on <strong class="text-white">one computer</strong> in 4 different terminals on 4 different ports:
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
            @foreach($servers as $server)
            <div class="bg-gray-950 border {{ $server->is_online ? 'border-green-800' : 'border-red-900' }} rounded p-3">
                <div class="flex justify-between items-center mb-2">
                    <span class="font-semibold text-sm text-white">{{ $server->name }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $server->is_online ? 'bg-green-900 text-green-300' : 'bg-red-900 text-red-300' }}">
                        {{ $server->is_online ? 'ONLINE' : 'OFFLINE' }}
                    </span>
                </div>
                <p class="text-xs text-gray-400 mb-2">Access URL: <span class="text-blue-400 font-mono">http://{{ $server->ip_address }}:{{ $server->port }}</span></p>
                <div class="bg-black rounded p-2">
                    <p class="text-xs text-gray-500 mb-1"># Run in Terminal {{ $loop->iteration }}:</p>
                    <p class="text-xs text-green-400 font-mono">php artisan serve --port={{ $server->port }}</p>
                </div>
            </div>
            @endforeach
        </div>

        <div class="bg-gray-950 border border-gray-700 rounded p-4">
            <p class="text-xs text-gray-400 mb-2">📁 Open 4 different terminals in the project folder and run:</p>
            <div class="space-y-1 font-mono text-xs">
                <p><span class="text-gray-500">Terminal 1:</span> <span class="text-green-400">php artisan serve --port=8001</span></p>
                <p><span class="text-gray-500">Terminal 2:</span> <span class="text-green-400">php artisan serve --port=8002</span></p>
                <p><span class="text-gray-500">Terminal 3:</span> <span class="text-green-400">php artisan serve --port=8003</span></p>
                <p><span class="text-gray-500">Terminal 4:</span> <span class="text-green-400">php artisan serve --port=8004</span></p>
                <p class="mt-2"><span class="text-gray-500">Dashboard:</span> <span class="text-orange-400">php artisan serve --port=8000</span></p>
            </div>
        </div>
    </div>

    {{-- Algorithms --}}
    <div class="bg-gray-900 border border-gray-800 rounded-lg p-6">
        <h2 class="text-lg font-semibold text-white mb-4">⚖️ Load Balancing Algorithms - What's the Difference?</h2>
        <div class="space-y-4">

            <div class="border border-gray-700 rounded p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="bg-blue-900 text-blue-300 text-xs px-2 py-0.5 rounded font-mono">Round Robin</span>
                </div>
                <p class="text-sm text-gray-300 mb-2">Distributes requests <strong class="text-white">in order</strong>. 1→2→3→1→2→3...</p>
                <div class="font-mono text-xs bg-gray-950 rounded p-2 text-green-400">
                    Request 1 → Server-1<br>
                    Request 2 → Server-2<br>
                    Request 3 → Server-3<br>
                    Request 4 → Server-1 (repeat)
                </div>
                <p class="text-xs text-gray-500 mt-2">✅ Best for: Same capacity servers, stateless apps</p>
            </div>

            <div class="border border-gray-700 rounded p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="bg-green-900 text-green-300 text-xs px-2 py-0.5 rounded font-mono">Least Connections</span>
                </div>
                <p class="text-sm text-gray-300 mb-2">Routes request to server with <strong class="text-white">fewest connections</strong>.</p>
                <div class="font-mono text-xs bg-gray-950 rounded p-2 text-green-400">
                    Server-1: 10 connections<br>
                    Server-2: 3 connections  ← Request comes here<br>
                    Server-3: 7 connections
                </div>
                <p class="text-xs text-gray-500 mt-2">✅ Best for: Long-running requests, unequal load</p>
            </div>

            <div class="border border-gray-700 rounded p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="bg-purple-900 text-purple-300 text-xs px-2 py-0.5 rounded font-mono">IP Hash</span>
                </div>
                <p class="text-sm text-gray-300 mb-2">Decides which server based on user's <strong class="text-white">IP address</strong>. Same IP = Same server <strong class="text-white">always</strong>.</p>
                <div class="font-mono text-xs bg-gray-950 rounded p-2 text-green-400">
                    IP: 192.168.1.5  → hash → Server-2 (always)<br>
                    IP: 192.168.1.10 → hash → Server-1 (always)<br>
                    IP: 192.168.1.15 → hash → Server-3 (always)
                </div>
                <p class="text-xs text-gray-500 mt-2">✅ Best for: Session-based apps, shopping carts</p>
            </div>

            <div class="border border-gray-700 rounded p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="bg-orange-900 text-orange-300 text-xs px-2 py-0.5 rounded font-mono">Weighted Round Robin</span>
                </div>
                <p class="text-sm text-gray-300 mb-2">Gives <strong class="text-white">more requests</strong> to powerful servers. Weight = capacity.</p>
                <div class="font-mono text-xs bg-gray-950 rounded p-2 text-green-400">
                    Server-1 (weight=3): Requests 1,2,3,7,8,9...<br>
                    Server-2 (weight=2): Requests 4,5,10,11...<br>
                    Server-3 (weight=1): Requests 6,12...
                </div>
                <p class="text-xs text-gray-500 mt-2">✅ Best for: Mixed capacity servers</p>
            </div>
        </div>
    </div>

    {{-- Features --}}
    <div class="bg-gray-900 border border-gray-800 rounded-lg p-6">
        <h2 class="text-lg font-semibold text-white mb-4">🔧 Project Features - What's Included?</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
            @foreach([
                ['/dashboard', '📊 Dashboard', 'Server stats, request logs, real-time metrics'],
                ['/load-balancer', '⚖️ Algorithm Demo', 'Simulate requests through different algorithms'],
                ['/health', '🏥 Health Check', 'Server health, memory, DB status'],
                ['/api/metrics', '📡 Metrics UI', 'Real-time server metrics with auto-refresh'],
                ['/health/live', '💚 Liveness Probe', 'Kubernetes liveness check - is app alive?'],
                ['/health/ready', '✅ Readiness Probe', 'Kubernetes readiness - ready to serve traffic?'],
                ['/api/servers', '🔌 API: Servers', 'JSON list of all server instances'],
                ['/api/requests/log', '📋 API: Request Log', 'JSON request history'],
            ] as [$url, $name, $desc])
            <a href="{{ $url }}" class="flex items-start gap-3 p-3 bg-gray-950 border border-gray-800 rounded hover:border-orange-700 transition">
                <div>
                    <p class="font-medium text-white">{{ $name }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $desc }}</p>
                    <p class="text-xs text-orange-400 font-mono mt-1">{{ $url }}</p>
                </div>
            </a>
            @endforeach
        </div>
    </div>

    {{-- Sticky Sessions --}}
    <div class="bg-gray-900 border border-gray-800 rounded-lg p-6">
        <h2 class="text-lg font-semibold text-white mb-4">🔒 What Are Sticky Sessions?</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-red-950 border border-red-900 rounded p-4">
                <p class="font-semibold text-red-300 mb-2">❌ Without Sticky Sessions</p>
                <p class="text-sm text-gray-300">User logs in on Server-1. Next request goes to Server-2. Server-2 doesn't know about session → User gets logged out!</p>
            </div>
            <div class="bg-green-950 border border-green-900 rounded p-4">
                <p class="font-semibold text-green-300 mb-2">✅ With Sticky Sessions</p>
                <p class="text-sm text-gray-300">User logs in on Server-1. All subsequent requests go to Server-1. Session stays safe!</p>
            </div>
        </div>
    </div>

    {{-- Health Checks --}}
    <div class="bg-gray-900 border border-gray-800 rounded-lg p-6">
        <h2 class="text-lg font-semibold text-white mb-4">🏥 How to Use Health Checks in Kubernetes/Docker?</h2>
        <div class="font-mono text-xs bg-gray-950 rounded p-4 text-green-400 leading-relaxed">
            <p class="text-gray-400"># In Kubernetes deployment.yaml:</p>
            <p class="text-yellow-400">livenessProbe:</p>
            <p class="ml-4">httpGet:</p>
            <p class="ml-8">path: <span class="text-green-400">/health/live</span></p>
            <p class="ml-8">port: 8000</p>
            <p class="ml-4">initialDelaySeconds: 10</p>
            <br>
            <p class="text-yellow-400">readinessProbe:</p>
            <p class="ml-4">httpGet:</p>
            <p class="ml-8">path: <span class="text-green-400">/health/ready</span></p>
            <p class="ml-8">port: 8000</p>
            <p class="ml-4">initialDelaySeconds: 5</p>
        </div>
        <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
            <div class="bg-gray-950 border border-gray-700 rounded p-3">
                <p class="text-blue-400 font-semibold mb-1">/health/live - Liveness</p>
                <p class="text-gray-400 text-xs">Is the app running? 200 = alive, fail = restart</p>
            </div>
            <div class="bg-gray-950 border border-gray-700 rounded p-3">
                <p class="text-green-400 font-semibold mb-1">/health/ready - Readiness</p>
                <p class="text-gray-400 text-xs">Is DB connected? Ready to serve traffic? 503 = don't serve traffic</p>
            </div>
        </div>
    </div>

</div>
@endsection
