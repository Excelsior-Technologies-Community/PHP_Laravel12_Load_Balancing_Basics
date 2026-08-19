<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Rate Limiting Demo Controller
 * 
 * Demonstrates rate limiting middleware and API throttling
 * to protect against abuse and ensure fair usage.
 */
class RateLimitDemoController extends Controller
{
    /**
     * Display rate limiting demo page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('rate-limit-demo.index');
    }

    /**
     * Test rate limiting with throttle middleware.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function test(Request $request): JsonResponse
    {
        $key = 'rate_limit_demo:' . $request->ip();
        
        $executed = RateLimiter::attempt(
            $key,
            $maxAttempts = 10,
            function () use ($request) {
                return true;
            },
            $decaySeconds = 60
        );

        if (!$executed) {
            $seconds = RateLimiter::availableIn($key);
            
            return response()->json([
                'error' => 'Too many requests',
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => $seconds . ' seconds',
                'limit' => 10,
                'remaining' => 0,
            ], 429);
        }

        $remaining = RateLimiter::remaining($key, $maxAttempts);
        $used = $maxAttempts - $remaining;

        return response()->json([
            'success' => true,
            'message' => 'Request successful! (' . $used . '/' . $maxAttempts . ' used)',
            'attempts' => $used,
            'remaining' => $remaining,
            'timestamp' => now()->toIso8601String(),
            'client_ip' => $request->ip(),
        ]);
    }

    /**
     * Get current rate limit status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function status(Request $request): JsonResponse
    {
        $key = 'rate_limit_demo:' . $request->ip();
        $maxAttempts = 10;
        $decaySeconds = 60;

        $remaining = RateLimiter::remaining($key, $maxAttempts);
        $availableIn = RateLimiter::availableIn($key);

        return response()->json([
            'client_ip' => $request->ip(),
            'rate_limit' => [
                'limit' => $maxAttempts,
                'remaining' => $remaining,
                'reset' => now()->addSeconds($availableIn)->toIso8601String(),
                'retry_after' => $availableIn > 0 ? $availableIn . ' seconds' : 'Available now',
            ],
            'status' => $remaining > 0 ? 'available' : 'exceeded',
        ]);
    }

    /**
     * Reset rate limit counter (demo only).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reset(Request $request): JsonResponse
    {
        $key = 'rate_limit_demo:' . $request->ip();
        RateLimiter::clear($key);

        return response()->json([
            'message' => 'Rate limit counter reset',
            'client_ip' => $request->ip(),
            'note' => 'This is for demo purposes only. In production, rate limits reset automatically.',
        ]);
    }

    /**
     * Test different rate limit strategies.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testStrategies(Request $request): JsonResponse
    {
        $strategy = $request->input('strategy', 'standard');
        $clientIp = $request->ip();

        $limits = match ($strategy) {
            'strict' => ['max' => 5, 'decay' => 60],
            'standard' => ['max' => 10, 'decay' => 60],
            'lenient' => ['max' => 20, 'decay' => 60],
            'burst' => ['max' => 30, 'decay' => 60],
            default => ['max' => 10, 'decay' => 60],
        };

        $key = "rate_limit_{$strategy}:{$clientIp}";
        
        $executed = RateLimiter::attempt(
            $key,
            $limits['max'],
            function () {
                return true;
            },
            $limits['decay']
        );

        if (!$executed) {
            $seconds = RateLimiter::availableIn($key);
            
            return response()->json([
                'error' => 'Too many requests',
                'strategy' => $strategy,
                'retry_after' => $seconds . ' seconds',
            ], 429);
        }

        $remaining = RateLimiter::remaining($key, $limits['max']);

        return response()->json([
            'message' => 'Request successful',
            'strategy' => $strategy,
            'limits' => $limits,
            'remaining' => $remaining,
        ]);
    }

    /**
     * Get rate limit statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics(): JsonResponse
    {
        return response()->json([
            'strategies' => [
                'strict' => ['max' => 5, 'decay' => 60, 'description' => '5 requests per minute'],
                'standard' => ['max' => 10, 'decay' => 60, 'description' => '10 requests per minute'],
                'lenient' => ['max' => 20, 'decay' => 60, 'description' => '20 requests per minute'],
                'burst' => ['max' => 30, 'decay' => 60, 'description' => '30 requests per minute'],
            ],
            'recommended' => [
                'public_api' => 'standard',
                'authenticated_api' => 'lenient',
                'admin_api' => 'burst',
                'sensitive_operations' => 'strict',
            ],
        ]);
    }
}
