<?php

use App\Http\Controllers\CacheDemoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\LoadBalancerController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\RateLimitDemoController;
use App\Http\Controllers\ServerFailoverController;
use App\Http\Controllers\ServiceDiscoveryController;
use App\Http\Controllers\SessionPersistenceController;
use Illuminate\Support\Facades\Route;

// Welcome
Route::get('/', fn() => view('welcome'));

// How It Works
Route::get('/how-it-works', function () {
    $servers = \App\Models\ServerInstance::all();
    return view('how-it-works', compact('servers'));
});
Route::get('/hello', function () {
    return "Hello World!";
});

// Health Check Endpoints
Route::prefix('health')->group(function () {
    Route::get('/', [HealthCheckController::class, 'index']);
    Route::get('/live', [HealthCheckController::class, 'live']);
    Route::get('/ready', [HealthCheckController::class, 'ready']);
});

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index']);

// Metrics
Route::get('/metrics', [MetricsController::class, 'index']);
Route::get('/metrics/realtime', [MetricsController::class, 'realtime']);
Route::get('/metrics/algorithm-comparison', [MetricsController::class, 'algorithmComparison']);
Route::get('/metrics/response-time', [MetricsController::class, 'responseTimeMonitoring']);
Route::get('/metrics/peak-load', [MetricsController::class, 'peakLoadIdentification']);
Route::get('/metrics/summary', [MetricsController::class, 'summary']);

// Load Balancer Demo
Route::prefix('load-balancer')->group(function () {
    Route::get('/', [LoadBalancerController::class, 'demo']);
    Route::post('/simulate', [LoadBalancerController::class, 'simulate']);
    Route::post('/servers/{server}/toggle', [LoadBalancerController::class, 'toggleServer']);
    Route::post('/sticky-session', [LoadBalancerController::class, 'stickySession']);
    Route::post('/simulate-routing', [LoadBalancerController::class, 'simulateRouting']);
});

// Service Discovery
Route::prefix('service-discovery')->group(function () {
    Route::get('/', [ServiceDiscoveryController::class, 'index']);
    Route::post('/register', [ServiceDiscoveryController::class, 'register']);
    Route::post('/heartbeat/{server}', [ServiceDiscoveryController::class, 'heartbeat']);
    Route::post('/remove-unhealthy', [ServiceDiscoveryController::class, 'removeUnhealthy']);
    Route::delete('/deregister/{server}', [ServiceDiscoveryController::class, 'deregister']);
    Route::get('/stats', [ServiceDiscoveryController::class, 'stats']);
});

// Server Failover
Route::prefix('failover')->group(function () {
    Route::post('/simulate-failure/{server}', [ServerFailoverController::class, 'simulateFailure']);
    Route::post('/simulate-recovery/{server}', [ServerFailoverController::class, 'simulateRecovery']);
    Route::get('/circuit-breaker/{server}', [ServerFailoverController::class, 'circuitBreakerStatus']);
    Route::get('/circuit-breakers', [ServerFailoverController::class, 'allCircuitBreakerStatuses']);
    Route::post('/test-failover', [ServerFailoverController::class, 'testFailover']);
    Route::get('/stats', [ServerFailoverController::class, 'failoverStats']);
    Route::post('/configure-circuit-breaker', [ServerFailoverController::class, 'configureCircuitBreaker']);
    Route::get('/circuit-breaker-config', [ServerFailoverController::class, 'getCircuitBreakerConfig']);
});

// Session Persistence
Route::prefix('session-persistence')->group(function () {
    Route::post('/test', [SessionPersistenceController::class, 'testStickySession']);
    Route::post('/clear', [SessionPersistenceController::class, 'clearStickySession']);
    Route::post('/simulate-multiple', [SessionPersistenceController::class, 'simulateMultipleRequests']);
    Route::get('/stats', [SessionPersistenceController::class, 'getStatistics']);
    Route::post('/compare', [SessionPersistenceController::class, 'compare']);
    Route::post('/set-sticky-server', [SessionPersistenceController::class, 'setStickyServer']);
});

// API Endpoints for Load Balancer
Route::prefix('api')->group(function () {
    Route::get('/servers', [LoadBalancerController::class, 'index']);
    Route::post('/servers/{server}/toggle', [LoadBalancerController::class, 'toggleServer']);
    Route::get('/metrics', [LoadBalancerController::class, 'metrics']);
    Route::get('/requests/log', [LoadBalancerController::class, 'requestLogs']);
});


// Rate Limiting Demo
Route::prefix('rate-limit-demo')->group(function () {
    Route::get('/', [RateLimitDemoController::class, 'index'])->name('rate-limit.index');
    Route::post('/test', [RateLimitDemoController::class, 'test'])->middleware('throttle:10,1')->name('rate-limit.test');
    Route::get('/status', [RateLimitDemoController::class, 'status']);
    Route::post('/reset', [RateLimitDemoController::class, 'reset']);
    Route::post('/test-strategies', [RateLimitDemoController::class, 'testStrategies']);
    Route::get('/statistics', [RateLimitDemoController::class, 'statistics']);
});

// Cache Demo
Route::prefix('cache-demo')->group(function () {
    Route::get('/', [CacheDemoController::class, 'index'])->name('cache.index');
    Route::post('/set', [CacheDemoController::class, 'set'])->name('cache.set');
    Route::post('/get', [CacheDemoController::class, 'get'])->name('cache.get');
    Route::post('/delete', [CacheDemoController::class, 'delete'])->name('cache.delete');
    Route::post('/clear', [CacheDemoController::class, 'clear'])->name('cache.clear');
    Route::get('/stats', [CacheDemoController::class, 'stats']);
});
