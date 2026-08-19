@extends('layouts.app')
@section('title', 'Rate Limiting Demo')

@section('content')
<div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold mb-6 text-orange-400">🚦 Rate Limiting Demo</h1>

    <div class="bg-yellow-950 border border-yellow-800 rounded-lg p-4 mb-6">
        <p class="text-sm text-yellow-300">
            ⚠️ This endpoint uses <strong>throttle:10,1</strong> middleware - maximum 10 requests per minute per IP.
            Try clicking the button multiple times to see rate limiting in action!
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-gray-900 border border-gray-800 rounded-lg p-6">
            <h2 class="text-lg font-semibold text-white mb-4">Test Rate Limit</h2>
            <button onclick="testRateLimit()" class="w-full bg-orange-500 hover:bg-orange-600 text-white py-3 rounded font-medium transition mb-4">
                🚀 Send Request
            </button>
            <div id="result" class="text-sm space-y-2"></div>
        </div>

        <div class="bg-gray-900 border border-gray-800 rounded-lg p-6">
            <h2 class="text-lg font-semibold text-white mb-4">How It Works</h2>
            <div class="space-y-3 text-sm text-gray-300">
                <p>Laravel's <code class="bg-gray-950 px-1 rounded text-orange-400">throttle</code> middleware limits the number of requests a user can make in a given time period.</p>
                <p>In this demo: <strong class="text-white">10 requests per minute</strong></p>
                <div class="bg-gray-950 rounded p-3 font-mono text-xs text-green-400">
                    Route::get('/rate-limit-demo')<br>
                    &nbsp;&nbsp;->middleware('throttle:10,1');
                </div>
                <p class="text-xs text-gray-500">When limit exceeded, Laravel returns HTTP 429 (Too Many Requests).</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
async function testRateLimit() {
    const result = document.getElementById('result');
    try {
        const res = await fetch('/rate-limit-demo/test', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        });
        const data = await res.json();

        if (res.ok) {
            result.innerHTML = `
                <div class="bg-green-900/30 border border-green-700 text-green-300 px-3 py-2 rounded">
                    ✅ ${data.message}
                </div>
                <div class="text-xs text-gray-400 mt-2">
                    Attempts: ${data.attempts} | Remaining: ${data.remaining}
                </div>
            `;
        } else {
            result.innerHTML = `
                <div class="bg-red-900/30 border border-red-700 text-red-300 px-3 py-2 rounded">
                    ❌ ${data.message || data.error} — Retry after: ${data.retry_after}
                </div>
                <div class="text-xs text-gray-400 mt-2">
                    Status: 429 Too Many Requests
                </div>
            `;
        }
    } catch (e) {
        result.innerHTML = `<div class="bg-red-900/30 border border-red-700 text-red-300 px-3 py-2 rounded">Error: ${e.message}</div>`;
    }
}
</script>
@endsection
