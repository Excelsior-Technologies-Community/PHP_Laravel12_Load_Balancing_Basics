<?php

namespace App\Http\Middleware;

use App\Models\RequestLog;
use App\Models\ServerInstance;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Request Tracking Middleware
 * 
 * Tracks all incoming requests and logs them to the database
 * for load balancing analytics and monitoring.
 */
class RequestTrackingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $server = $this->getSelectedServer($request);

        $response = $next($request);

        $responseTime = (int) ((microtime(true) - $startTime) * 1000);

        $this->logRequest($request, $response, $responseTime, $server);

        if ($server) {
            $response->headers->set('X-Server-Instance', $server->name);
            $server->incrementRequestCount();
        }

        return $response;
    }

    /**
     * Get the selected server instance for this request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Models\ServerInstance|null
     */
    protected function getSelectedServer(Request $request): ?ServerInstance
    {
        $serverId = $request->header('X-Server-Instance-ID');
        
        if ($serverId) {
            return ServerInstance::find($serverId);
        }

        // For demo purposes, select a random online server
        return ServerInstance::online()->inRandomOrder()->first();
    }

    /**
     * Log the request details to the database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  int  $responseTime
     * @param  \App\Models\ServerInstance|null  $server
     * @return void
     */
    protected function logRequest(
        Request $request,
        Response $response,
        int $responseTime,
        ?ServerInstance $server
    ): void {
        try {
            RequestLog::create([
                'server_instance_id' => $server?->id,
                'method' => $request->method(),
                'path' => $request->path(),
                'client_ip' => $request->ip(),
                'response_time' => $responseTime,
                'status_code' => $response->getStatusCode(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log request: ' . $e->getMessage());
        }
    }
}
