<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Load Balancer Demo')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen">
    <nav class="bg-gray-900 border-b border-gray-800 px-6 py-3 flex items-center gap-6">
        <span class="font-bold text-orange-400 text-lg">⚖️ LB Demo</span>
        <a href="/dashboard" class="text-sm hover:text-orange-400 transition">Dashboard</a>
        <a href="/load-balancer" class="text-sm hover:text-orange-400 transition">Algorithms</a>
        <a href="/metrics" class="text-sm hover:text-orange-400 transition">Metrics</a>
        <a href="/health" class="text-sm hover:text-orange-400 transition">Health</a>
        <a href="/how-it-works" class="text-sm hover:text-orange-400 transition">How It Works</a>
        <a href="/rate-limit-demo" class="text-sm hover:text-orange-400 transition">Rate Limit</a>
        <a href="/cache-demo" class="text-sm hover:text-orange-400 transition">Cache Demo</a>
    </nav>
    <main class="p-6">
        @yield('content')
    </main>
    @yield('scripts')
</body>
</html>
