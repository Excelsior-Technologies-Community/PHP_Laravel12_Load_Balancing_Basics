<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RateLimitController extends Controller
{
    public function index()
    {
        return view('rate-limit-demo.index');
    }

    public function test(Request $request)
    {
        $key = 'rate_limit_test_' . $request->ip();
        $attempts = Cache::get($key, 0);
        $newAttempts = $attempts + 1;
        $limit = 10;
        $remaining = max(0, $limit - $newAttempts);

        Cache::put($key, $newAttempts, now()->addMinute());

        if ($newAttempts > $limit) {
            return response()->json([
                'success' => false,
                'message' => 'Rate limit exceeded! Try again in 1 minute.',
                'attempts' => $newAttempts,
                'remaining' => 0,
            ], 429);
        }

        return response()->json([
            'success' => true,
            'message' => "Request #{$newAttempts} successful!",
            'attempts' => $newAttempts,
            'remaining' => $remaining,
        ]);
    }
}
