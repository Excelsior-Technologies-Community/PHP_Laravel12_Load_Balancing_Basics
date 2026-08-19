@extends('layouts.app')
@section('title', 'Cache Demo')

@section('content')
<div class="max-w-5xl mx-auto">
    <h1 class="text-2xl font-bold mb-6 text-orange-400">💾 Cache Demo</h1>

    <div class="bg-blue-950 border border-blue-800 rounded-lg p-4 mb-6">
        <p class="text-sm text-blue-300">
            ℹ️ This demo uses Laravel's file cache driver. All data is stored in <code class="bg-gray-950 px-1 rounded text-blue-400">storage/framework/cache</code>.
        </p>
    </div>

    @if(session('success'))
        <div class="bg-green-900 border border-green-700 text-green-300 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-900 border border-red-700 text-red-300 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-gray-900 border border-gray-800 rounded-lg p-6">
            <h2 class="text-lg font-semibold text-white mb-4">Set Cache</h2>
            <form method="POST" action="{{ route('cache.set') }}">
                @csrf
                <div class="mb-3">
                    <label class="block text-xs text-gray-400 mb-1">Key</label>
                    <input type="text" name="key" required
                        class="w-full bg-gray-950 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:border-orange-500 focus:outline-none">
                </div>
                <div class="mb-3">
                    <label class="block text-xs text-gray-400 mb-1">Value</label>
                    <input type="text" name="value" required
                        class="w-full bg-gray-950 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:border-orange-500 focus:outline-none">
                </div>
                <div class="mb-4">
                    <label class="block text-xs text-gray-400 mb-1">TTL (seconds)</label>
                    <input type="number" name="ttl" value="300" min="10" max="3600"
                        class="w-full bg-gray-950 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:border-orange-500 focus:outline-none">
                </div>
                <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white py-2 rounded text-sm transition">
                    Save to Cache
                </button>
            </form>
        </div>

        <div class="bg-gray-900 border border-gray-800 rounded-lg p-6">
            <h2 class="text-lg font-semibold text-white mb-4">Get / Delete Cache</h2>
            <form method="POST" action="{{ route('cache.get') }}" class="mb-3">
                @csrf
                <div class="flex gap-2">
                    <input type="text" name="key" placeholder="Enter key..." required
                        class="flex-1 bg-gray-950 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:border-orange-500 focus:outline-none">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-sm transition">
                        Get
                    </button>
                </div>
            </form>
            <form method="POST" action="{{ route('cache.delete') }}" class="mb-3">
                @csrf
                <div class="flex gap-2">
                    <input type="text" name="key" placeholder="Enter key..." required
                        class="flex-1 bg-gray-950 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:border-orange-500 focus:outline-none">
                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded text-sm transition">
                        Delete
                    </button>
                </div>
            </form>
            <form method="POST" action="{{ route('cache.clear') }}" onsubmit="return confirm('Clear all demo cache?')">
                @csrf
                <button type="submit" class="w-full bg-gray-700 hover:bg-gray-600 text-white py-2 rounded text-sm transition">
                    Clear All Demo Cache
                </button>
            </form>
        </div>
    </div>

    <div class="bg-gray-900 border border-gray-800 rounded-lg p-6">
        <h2 class="text-lg font-semibold text-white mb-4">Cached Keys</h2>
        @if($cacheKeys->isEmpty())
            <p class="text-gray-500 text-sm">No cached keys yet.</p>
        @else
            <div class="flex flex-wrap gap-2">
                @foreach($cacheKeys as $key)
                <span class="bg-gray-950 border border-gray-700 text-gray-300 text-xs px-3 py-1 rounded-full">
                    {{ $key }}
                </span>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
