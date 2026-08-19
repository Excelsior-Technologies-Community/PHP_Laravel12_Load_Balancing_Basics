<?php

namespace App\Http\Middleware;

use App\Models\RequestLog;
use App\Models\ServerInstance;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RequestTracker
{
    // Skip logging for these paths to avoid duplicates
    private array $skipPaths = [
        'health', 'health/live', 'health/ready',
        'api/metrics', 'api/servers', 'api/requests/log',
        'load-balancer/simulate', 'load-balancer/sticky-session',
        'favicon.ico', 'up',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        $response = $next($request);
        $responseTimeMs = (int) ((microtime(true) - $start) * 1000);

        // Skip logging for specified paths
        if ($this->shouldSkip($request)) {
            return $response;
        }

        $server = ServerInstance::online()->inRandomOrder()->first();

        if ($server) {
            // Increment request count
            $server->incrementRequestCount();
            $server->updateHeartbeat();

            RequestLog::create([
                'server_instance_id' => $server->id,
                'method' => $request->method(),
                'path' => '/' . $request->path(),
                'client_ip' => $request->ip(),
                'response_time' => $responseTimeMs,
                'status_code' => $response->getStatusCode(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            $response->headers->set('X-Server-Instance', $server->name);
            $response->headers->set('X-Response-Time', $responseTimeMs . 'ms');
        }

        return $response;
    }

    private function shouldSkip(Request $request): bool
    {
        $path = $request->path();
        foreach ($this->skipPaths as $skip) {
            if ($path === $skip || str_starts_with($path, $skip)) {
                return true;
            }
        }
        return false;
    }
}
