<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Cache Demo Controller
 * 
 * Demonstrates caching mechanisms using Redis or file cache
 * for performance optimization and reduced database load.
 */
class CacheDemoController extends Controller
{
    /**
     * Display cache demo page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $cachedData = Cache::get('demo_cached_data');
        $cachedTime = Cache::get('demo_cached_time');
        $cacheKeys = collect(Cache::get('demo_keys', []));

        return view('cache-demo.index', compact('cachedData', 'cachedTime', 'cacheKeys'));
    }

    /**
     * Set a value in cache.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function set(Request $request)
    {
        $request->validate([
            'key' => 'required|string|max:100',
            'value' => 'required|string|max:500',
            'ttl' => 'nullable|integer|min:10|max:3600',
        ]);

        $key = 'demo_' . $request->key;
        $ttl = (int) ($request->ttl ?? 300);

        Cache::put($key, $request->value, $ttl);

        $this->updateKeysList($key);

        return back()->with('success', "Cached '{$request->key}' for {$ttl} seconds!");
    }

    /**
     * Get a value from cache.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function get(Request $request)
    {
        $request->validate([
            'key' => 'required|string|max:100',
        ]);

        $key = 'demo_' . $request->key;
        $value = Cache::get($key);

        if ($value === null) {
            return back()->with('error', "Key '{$request->key}' not found in cache!");
        }

        return back()->with('success', "Retrieved: " . $value);
    }

    /**
     * Delete a value from cache.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete(Request $request)
    {
        $request->validate([
            'key' => 'required|string|max:100',
        ]);

        $key = 'demo_' . $request->key;
        $deleted = Cache::forget($key);

        if ($deleted) {
            $this->removeKeyFromList($key);
            return back()->with('success', "Deleted '{$request->key}' from cache!");
        }

        return back()->with('error', "Key '{$request->key}' not found!");
    }

    /**
     * Clear all demo cache entries.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clear(Request $request)
    {
        $keys = Cache::get('demo_keys', []);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        Cache::forget('demo_keys');

        return back()->with('success', 'All demo cache cleared!');
    }

    /**
     * Get cache statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats(): JsonResponse
    {
        $keys = Cache::get('demo_keys', []);
        
        return response()->json([
            'cache_driver' => config('cache.default'),
            'total_keys' => count($keys),
            'keys' => $keys,
            'cache_status' => 'connected',
        ]);
    }

    /**
     * Update the list of cache keys.
     *
     * @param  string  $key
     * @return void
     */
    private function updateKeysList(string $key): void
    {
        $keys = Cache::get('demo_keys', []);
        if (!in_array($key, $keys)) {
            $keys[] = $key;
            Cache::put('demo_keys', $keys, 7200);
        }
    }

    /**
     * Remove a key from the list.
     *
     * @param  string  $key
     * @return void
     */
    private function removeKeyFromList(string $key): void
    {
        $keys = Cache::get('demo_keys', []);
        $keys = array_values(array_filter($keys, fn($k) => $k !== $key));
        Cache::put('demo_keys', $keys, 7200);
    }
}
