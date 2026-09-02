<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Load Balancer Demo - Laravel 12</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-950 text-gray-100 min-h-screen">
    <nav class="bg-gray-900 border-b border-gray-800 px-6 py-3 flex items-center gap-6">
        <span class="font-bold text-orange-400 text-lg">⚖️ LB Demo</span>
        <a href="/dashboard" class="text-sm hover:text-orange-400 transition">Dashboard</a>
        <a href="/load-balancer" class="text-sm hover:text-orange-400 transition">Algorithms</a>
        <a href="/api/metrics" class="text-sm hover:text-orange-400 transition">Metrics</a>
        <a href="/health" class="text-sm hover:text-orange-400 transition">Health</a>
        <a href="/how-it-works" class="text-sm hover:text-orange-400 transition">How It Works</a>
        <a href="/tasks" class="text-sm hover:text-orange-400 transition">Tasks CRUD</a>
        <a href="/rate-limit-demo" class="text-sm hover:text-orange-400 transition">Rate Limit</a>
        <a href="/cache-demo" class="text-sm hover:text-orange-400 transition">Cache Demo</a>
    </nav>

    <main class="p-6">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold mb-4 text-orange-400">⚖️ Laravel 12 Load Balancer Demo</h1>
                <p class="text-gray-400 text-lg max-w-2xl mx-auto">
                    Learn how load balancing works with multiple server instances, health checks, and different routing algorithms.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <a href="/dashboard" class="bg-gray-900 border border-gray-800 rounded-lg p-6 hover:border-orange-700 transition">
                    <h3 class="text-lg font-semibold text-white mb-2">📊 Dashboard</h3>
                    <p class="text-sm text-gray-400">Real-time server stats, request logs, and active connections monitoring.</p>
                </a>

                <a href="/load-balancer" class="bg-gray-900 border border-gray-800 rounded-lg p-6 hover:border-orange-700 transition">
                    <h3 class="text-lg font-semibold text-white mb-2">⚖️ Algorithm Demo</h3>
                    <p class="text-sm text-gray-400">Simulate requests through Round Robin, Least Connections, IP Hash, and Weighted Round Robin.</p>
                </a>

                <!-- Dynamic Server Weights -->
                <a href="{{ route('load-distribution.weights') }}"
                    class="bg-gray-900 border border-gray-800 rounded-lg p-6 hover:border-orange-700 transition">

                    <h3 class="text-lg font-semibold text-white mb-2">
                        ⚖️ Dynamic Server Weights
                    </h3>

                    <p class="text-sm text-gray-400">
                        Dynamically configure server weights and simulate
                        Weighted Round Robin traffic distribution.
                    </p>

                </a>

                <!-- Load Distribution Analyzer -->
                <a href="{{ route('load-distribution.analyzer') }}"
                    class="bg-gray-900 border border-gray-800 rounded-lg p-6 hover:border-orange-700 transition">

                    <h3 class="text-lg font-semibold text-white mb-2">
                        📊 Load Distribution Analyzer
                    </h3>

                    <p class="text-sm text-gray-400">
                        Analyze traffic distribution, server utilization,
                        deviation, and overall load balance score.
                    </p>

                </a>

                <a href="/health" class="bg-gray-900 border border-gray-800 rounded-lg p-6 hover:border-orange-700 transition">
                    <h3 class="text-lg font-semibold text-white mb-2">🏥 Health Check</h3>
                    <p class="text-sm text-gray-400">Server health, memory usage, database status, and Kubernetes probes.</p>
                </a>

                <a href="/api/metrics" class="bg-gray-900 border border-gray-800 rounded-lg p-6 hover:border-orange-700 transition">
                    <h3 class="text-lg font-semibold text-white mb-2">📡 Metrics API</h3>
                    <p class="text-sm text-gray-400">JSON API endpoints for servers, metrics, and request history.</p>
                </a>

                <a href="/how-it-works" class="bg-gray-900 border border-gray-800 rounded-lg p-6 hover:border-orange-700 transition">
                    <h3 class="text-lg font-semibold text-white mb-2">📖 How It Works</h3>
                    <p class="text-sm text-gray-400">Learn about real-world load balancing architecture and algorithms.</p>
                </a>

                <a href="/tasks" class="bg-gray-900 border border-gray-800 rounded-lg p-6 hover:border-orange-700 transition">
                    <h3 class="text-lg font-semibold text-white mb-2">📝 Tasks CRUD</h3>
                    <p class="text-sm text-gray-400">Create, read, update, and delete tasks - basic Laravel CRUD example.</p>
                </a>

                <a href="/rate-limit-demo" class="bg-gray-900 border border-gray-800 rounded-lg p-6 hover:border-orange-700 transition">
                    <h3 class="text-lg font-semibold text-white mb-2">🚦 Rate Limiting</h3>
                    <p class="text-sm text-gray-400">Demo Laravel throttle middleware - limit requests per minute.</p>
                </a>

                <a href="/cache-demo" class="bg-gray-900 border border-gray-800 rounded-lg p-6 hover:border-orange-700 transition">
                    <h3 class="text-lg font-semibold text-white mb-2">💾 Cache Demo</h3>
                    <p class="text-sm text-gray-400">Demonstrate file cache with set, get, and delete operations.</p>
                </a>

                <a href="http://127.0.0.1:8001" class="bg-gray-900 border border-gray-800 rounded-lg p-6 hover:border-orange-700 transition">
                    <h3 class="text-lg font-semibold text-white mb-2">🖥️ Server-1</h3>
                    <p class="text-sm text-gray-400">Direct access to Server-1 on port 8001</p>
                </a>
            </div>

            <div class="mt-12 bg-gray-900 border border-gray-800 rounded-lg p-6">
                <h2 class="text-lg font-semibold text-white mb-4">🖥️ Running Multiple Servers</h2>
                <p class="text-sm text-gray-400 mb-4">
                    To see real load balancing, run multiple server instances in separate terminals:
                </p>
                <div class="space-y-2 font-mono text-sm">
                    <div class="flex items-center gap-3">
                        <span class="text-gray-500 w-24">Terminal 1:</span>
                        <span class="text-green-400">php artisan serve --port=8001</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-gray-500 w-24">Terminal 2:</span>
                        <span class="text-green-400">php artisan serve --port=8002</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-gray-500 w-24">Terminal 3:</span>
                        <span class="text-green-400">php artisan serve --port=8003</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-gray-500 w-24">Terminal 4:</span>
                        <span class="text-green-400">php artisan serve --port=8004</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-gray-500 w-24">Dashboard:</span>
                        <span class="text-orange-400">php artisan serve --port=8000</span>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>

</html>